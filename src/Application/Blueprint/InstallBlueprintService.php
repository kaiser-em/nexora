<?php

declare(strict_types=1);

namespace Silao\Application\Blueprint;

use Silao\Application\Command\ConfigureBookingModelCommand;
use Silao\Application\DTO\InstallBlueprintResultDTO;
use Silao\Application\Exception\ApplicationException;
use Silao\Application\Exception\DuplicateSlugException;
use Silao\Application\Transaction\TransactionManagerInterface;
use Silao\Blueprint\Contract\BlueprintRegistryInterface;
use Silao\Domain\Common\ValueObject\Currency;
use Silao\Domain\Common\ValueObject\Money;
use Silao\Domain\Common\ValueObject\Percentage;
use Silao\Domain\Model\BookingModel;
use Silao\Domain\Model\Enum\BookingModelStatus;
use Silao\Domain\Model\Enum\FieldType;
use Silao\Domain\Model\Enum\OptionPricingType;
use Silao\Domain\Model\Enum\PricingCalculationBasis;
use Silao\Domain\Model\Enum\PricingTarget;
use Silao\Domain\Model\Enum\ResourceStrategyType;
use Silao\Domain\Model\Repository\BookingModelRepositoryInterface;
use Silao\Domain\Model\ValueObject\BookingModelId;
use Silao\Domain\Model\ValueObject\Field;
use Silao\Domain\Model\ValueObject\Option;
use Silao\Domain\Model\ValueObject\PricingRule;
use Silao\Domain\Resource\Enum\ResourceStatus;
use Silao\Domain\Resource\Repository\ResourceRepositoryInterface;
use Silao\Domain\Resource\Resource;
use Silao\Domain\Resource\ValueObject\Capacity;
use Silao\Domain\Resource\ValueObject\ResourceId;
use Silao\Domain\Common\ValueObject\TimeOfDay;
use Silao\Domain\Common\ValueObject\ZonedDateTimeRange;
use Silao\Domain\Common\ValueObject\BlackoutPeriod;
use Silao\Domain\Resource\ValueObject\Schedule;
use Silao\Infrastructure\Serializer\ConditionSerializer;

/**
 * Orchestrator acting as a Translator.
 * Strict Boundary: Reads raw array DTO from Registry -> Calls Domain Constructors under Transaction.
 */
final readonly class InstallBlueprintService
{
    public function __construct(
        private BlueprintRegistryInterface $registry,
        private BookingModelRepositoryInterface $modelRepository,
        private ResourceRepositoryInterface $resourceRepository,
        private TransactionManagerInterface $transactionManager
    ) {
    }

    /**
     * @throws ApplicationException
     */
    public function execute(string $blueprintId, string $targetSlug, string $status, bool $createResources = true): InstallBlueprintResultDTO
    {
        // 1. Load Blueprint JSON to Raw Array
        $raw = $this->registry->getRawDefinition($blueprintId);

        $cmd = new ConfigureBookingModelCommand(
            $targetSlug,
            $status,
            $raw['model'],
            $raw['fields'] ?? [],
            $raw['options'] ?? [],
            $raw['pricing_rules'] ?? [],
            $createResources ? ($raw['sample_resources'] ?? []) : []
        );

        // 2. Transact Domain Initialization
        /** @var array{model: BookingModel, count: int} $result */
        $result = $this->transactionManager->transactional(function () use ($cmd, $raw): array {
            // Uniqueness check inside transaction
            if ($this->modelRepository->findBySlug($cmd->targetSlug) !== null) {
                throw new DuplicateSlugException(sprintf('Slug "%s" is already in use.', $cmd->targetSlug));
            }

            $modelId = BookingModelId::fromString('model_' . bin2hex(random_bytes(6)));
            $currency = Currency::of((string) ($cmd->modelData['currency'] ?? 'EUR'));
            $basePrice = Money::of((int) ($cmd->modelData['base_price_amount'] ?? 0), $currency);
            $strategy = ResourceStrategyType::tryFrom((string) ($cmd->modelData['resource_strategy'] ?? 'none')) ?? ResourceStrategyType::None;
            $modStatus = BookingModelStatus::tryFrom($cmd->targetStatus) ?? BookingModelStatus::Draft;

            $model = new BookingModel(
                $modelId,
                $cmd->targetSlug,
                (string) ($raw['name'] ?? 'Model'),
                (string) ($raw['description'] ?? ''),
                $basePrice,
                BookingModelStatus::Draft, // Start as Draft to attach dependencies
                $strategy
            );

            // Translate Fields
            foreach ($cmd->fieldsData as $f) {
                $cond = ConditionSerializer::deserialize($f['visibility_condition'] ?? null);
                $field = new Field(
                    (string) $f['name'],
                    FieldType::tryFrom((string) ($f['type'] ?? 'text')) ?? FieldType::Text,
                    (string) $f['label'],
                    (bool) ($f['is_required'] ?? false),
                    $f['default_value'] ?? null,
                    is_array($f['validation_rules'] ?? null) ? $f['validation_rules'] : [],
                    $cond
                );
                $model->addField($field);
            }

            // Translate Options
            foreach ($cmd->optionsData as $o) {
                $uPrice = Money::of((int) ($o['unit_price_amount'] ?? 0), $currency);
                $cond = ConditionSerializer::deserialize($o['condition'] ?? null);
                $option = new Option(
                    (string) $o['id'],
                    (string) $o['code'],
                    (string) $o['name'],
                    (string) ($o['description'] ?? ''),
                    OptionPricingType::tryFrom((string) ($o['pricing_type'] ?? 'flat')) ?? OptionPricingType::Flat,
                    $uPrice,
                    (int) ($o['min_quantity'] ?? 0),
                    (int) ($o['max_quantity'] ?? 1),
                    (bool) ($o['is_mandatory'] ?? false),
                    (int) ($o['capacity_impact'] ?? 0),
                    $cond
                );
                $model->addOption($option);
            }

            // Translate PricingRules
            foreach ($cmd->rulesData as $r) {
                $cond = ConditionSerializer::deserialize($r['condition'] ?? null);
                if ($cond === null) {
                    throw new ApplicationException('Pricing Rule requires a valid Condition AST.');
                }
                $rule = new PricingRule(
                    (string) $r['id'],
                    (int) $r['priority'],
                    $cond,
                    PricingTarget::tryFrom((string) ($r['target'] ?? 'base_price')) ?? PricingTarget::BasePrice,
                    PricingCalculationBasis::tryFrom((string) ($r['calculation_basis'] ?? 'fixed_amount')) ?? PricingCalculationBasis::FixedAmount,
                    isset($r['adjustment_amount']) ? Money::of((int) $r['adjustment_amount'], $currency) : null,
                    isset($r['adjustment_percentage']) ? Percentage::fromBasisPoints((int) $r['adjustment_percentage']) : null,
                    (bool) ($r['stop_processing'] ?? false)
                );
                $model->addPricingRule($rule);
            }

            // Translate and save Resources
            $resCount = 0;
            foreach ($cmd->resourcesData as $res) {
                $rId = ResourceId::fromString((string) $res['resource_id'] . '_' . bin2hex(random_bytes(3))); // Ensure unique ID per install
                $schedules = [];
                if (is_array($res['schedules'] ?? null)) {
                    foreach ($res['schedules'] as $s) {
                        $schedules[] = new Schedule(
                            (int) $s['day_of_week'],
                            TimeOfDay::fromString((string) $s['start_time']),
                            TimeOfDay::fromString((string) $s['end_time'])
                        );
                    }
                }
                $blackouts = [];
                if (is_array($res['blackouts'] ?? null)) {
                    foreach ($res['blackouts'] as $b) {
                        $range = ZonedDateTimeRange::fromIsoStrings((string) $b['starts_at_utc'], (string) $b['ends_at_utc'], (string) ($b['timezone'] ?? 'UTC'));
                        $blackouts[] = new BlackoutPeriod($range, (string) ($b['reason'] ?? ''));
                    }
                }
                $resource = new Resource(
                    $rId,
                    (string) $res['name'],
                    Capacity::of((int) ($res['capacity'] ?? 1)),
                    ResourceStatus::tryFrom((string) ($res['status'] ?? 'active')) ?? ResourceStatus::Active,
                    $schedules,
                    $blackouts,
                    is_array($res['metadata'] ?? null) ? $res['metadata'] : []
                );
                $this->resourceRepository->save($resource);
                $model->addEligibleResource($rId);
                $resCount++;
            }

            // Finalize status
            if ($modStatus->isPublished()) {
                $model->publish();
            }

            $this->modelRepository->save($model);

            return ['model' => $model, 'count' => $resCount];
        });

        return new InstallBlueprintResultDTO(
            $result['model']->id()->toString(),
            $result['model']->slug(),
            $result['model']->status()->value,
            $blueprintId,
            (string) $raw['version'],
            $result['count']
        );
    }
}
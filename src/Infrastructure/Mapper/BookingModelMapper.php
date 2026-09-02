<?php

declare(strict_types=1);

namespace Silao\Infrastructure\Mapper;

use DateTimeImmutable;
use DateTimeZone;
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
use Silao\Domain\Model\ValueObject\BookingModelId;
use Silao\Domain\Model\ValueObject\Field;
use Silao\Domain\Model\ValueObject\Option;
use Silao\Domain\Model\ValueObject\PricingRule;
use Silao\Domain\Resource\ValueObject\ResourceId;
use Silao\Infrastructure\Exception\PersistenceException;
use Silao\Infrastructure\Serializer\ConditionSerializer;

final class BookingModelMapper
{
    /**
     * @return array<string, mixed>
     */
    public static function toDatabase(BookingModel $model, ?DateTimeImmutable $now = null): array
    {
        $utcNow = ($now ?? new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');

        $resourcesData = array_map(
            static fn(ResourceId $r): string => $r->toString(),
            array_values($model->eligibleResourceIds())
        );

        $fieldsData = [];
        foreach ($model->fields() as $field) {
            $fieldsData[] = [
                'name' => $field->name,
                'type' => $field->type->value,
                'label' => $field->label,
                'is_required' => $field->isRequired,
                'default_value' => $field->defaultValue,
                'validation_rules' => $field->validationRules,
                'visibility_condition' => ConditionSerializer::serialize($field->visibilityCondition),
            ];
        }

        $optionsData = [];
        foreach ($model->options() as $option) {
            $optionsData[] = [
                'id' => $option->id,
                'code' => $option->code,
                'name' => $option->name,
                'description' => $option->description,
                'pricing_type' => $option->pricingType->value,
                'unit_price' => $option->unitPrice->amount,
                'min_quantity' => $option->minQuantity,
                'max_quantity' => $option->maxQuantity,
                'is_mandatory' => $option->isMandatory,
                'capacity_impact' => $option->capacityImpact,
                'condition' => ConditionSerializer::serialize($option->condition),
            ];
        }

        $rulesData = [];
        foreach ($model->pricingRules() as $rule) {
            $rulesData[] = [
                'id' => $rule->id,
                'priority' => $rule->priority,
                'target' => $rule->target->value,
                'calculation_basis' => $rule->calculationBasis->value,
                'adjustment_amount' => $rule->adjustmentAmount?->amount,
                'adjustment_percentage' => $rule->adjustmentPercentage?->toBasisPoints(),
                'stop_processing' => $rule->stopProcessing,
                'condition' => ConditionSerializer::serialize($rule->condition),
            ];
        }

        return [
            'model_id' => $model->id()->toString(),
            'slug' => $model->slug(),
            'name' => $model->name(),
            'description' => $model->description(),
            'status' => $model->status()->value,
            'base_price_amount' => $model->basePrice()->amount,
            'base_price_currency' => $model->basePrice()->currency->code,
            'resource_strategy' => $model->resourceStrategy()->value,
            'eligible_resources_json' => json_encode($resourcesData, JSON_THROW_ON_ERROR),
            'fields_json' => json_encode($fieldsData, JSON_THROW_ON_ERROR),
            'options_json' => json_encode($optionsData, JSON_THROW_ON_ERROR),
            'pricing_rules_json' => json_encode($rulesData, JSON_THROW_ON_ERROR),
            'updated_at_utc' => $utcNow,
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @throws PersistenceException
     */
    public static function toDomain(array $row): BookingModel
    {
        $requiredKeys = ['model_id', 'slug', 'name', 'base_price_amount', 'base_price_currency', 'resource_strategy', 'status'];
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $row)) {
                throw new PersistenceException(sprintf('Corrupted model row: missing column "%s".', $key));
            }
        }

        try {
            $id = BookingModelId::fromString((string) $row['model_id']);
            $slug地理 = (string) $row['slug'];
            $name = (string) $row['name'];
            $description = (string) ($row['description'] ?? '');
            $currency = Currency::of((string) $row['base_price_currency']);
            $basePrice = Money::of((int) $row['base_price_amount'], $currency);
            $status = BookingModelStatus::tryFrom((string) $row['status']) ?? BookingModelStatus::Draft;
            $strategy = ResourceStrategyType::tryFrom((string) $row['resource_strategy']) ?? ResourceStrategyType::None;

            $eligibleResources = [];
            if (isset($row['eligible_resources_json']) && is_string($row['eligible_resources_json'])) {
                $rawRes = json_decode($row['eligible_resources_json'], true, 512, JSON_THROW_ON_ERROR);
                if (is_array($rawRes)) {
                    foreach ($rawRes as $resIdStr) {
                        $eligibleResources[] = ResourceId::fromString((string) $resIdStr);
                    }
                }
            }

            $fields = [];
            if (isset($row['fields_json']) && is_string($row['fields_json'])) {
                $rawFields = json_decode($row['fields_json'], true, 512, JSON_THROW_ON_ERROR);
                if (is_array($rawFields)) {
                    foreach ($rawFields as $f) {
                        $fType = FieldType::tryFrom((string) $f['type']) ?? FieldType::Text;
                        $cond = ConditionSerializer::deserialize($f['visibility_condition'] ?? null);
                        $fields[] = new Field(
                            (string) $f['name'],
                            $fType,
                            (string) $f['label'],
                            (bool) ($f['is_required'] ?? false),
                            $f['default_value'] ?? null,
                            is_array($f['validation_rules'] ?? null) ? $f['validation_rules'] : [],
                            $cond
                        );
                    }
                }
            }

            $options = [];
            if (isset($row['options_json']) && is_string($row['options_json'])) {
                $rawOptions = json_decode($row['options_json'], true, 512, JSON_THROW_ON_ERROR);
                if (is_array($rawOptions)) {
                    foreach ($rawOptions as $o) {
                        $pType = OptionPricingType::tryFrom((string) $o['pricing_type']) ?? OptionPricingType::Flat;
                        $uPrice = Money::of((int) $o['unit_price'], $currency);
                        $cond = ConditionSerializer::deserialize($o['condition'] ?? null);
                        $options[] = new Option(
                            (string) $o['id'],
                            (string) $o['code'],
                            (string) $o['name'],
                            (string) ($o['description'] ?? ''),
                            $pType,
                            $uPrice,
                            (int) ($o['min_quantity'] ?? 0),
                            (int) ($o['max_quantity'] ?? 1),
                            (bool) ($o['is_mandatory'] ?? false),
                            (int) ($o['capacity_impact'] ?? 0),
                            $cond
                        );
                    }
                }
            }

            $rules = [];
            if (isset($row['pricing_rules_json']) && is_string($row['pricing_rules_json'])) {
                $rawRules = json_decode($row['pricing_rules_json'], true, 512, JSON_THROW_ON_ERROR);
                if (is_array($rawRules)) {
                    foreach ($rawRules as $r) {
                        $target = PricingTarget::tryFrom((string) $r['target']) ?? PricingTarget::BasePrice;
                        $basis = PricingCalculationBasis::tryFrom((string) $r['calculation_basis']) ?? PricingCalculationBasis::FixedAmount;
                        $cond = ConditionSerializer::deserialize($r['condition'] ?? null);

                        if ($cond === null) {
                            throw new PersistenceException('Pricing rule must have a valid condition.');
                        }

                        $adjAmount = isset($r['adjustment_amount'])
                            ? Money::of((int) $r['adjustment_amount'], $currency)
                            : null;

                        $adjPercent = isset($r['adjustment_percentage'])
                            ? Percentage::fromBasisPoints((int) $r['adjustment_percentage'])
                            : null;

                        $rules[] = new PricingRule(
                            (string) $r['id'],
                            (int) $r['priority'],
                            $cond,
                            $target,
                            $basis,
                            $adjAmount,
                            $adjPercent,
                            (bool) ($r['stop_processing'] ?? false)
                        );
                    }
                }
            }

            return new BookingModel($id, $slug地理, $name, $description, $basePrice, $status, $strategy, $eligibleResources, $fields, $options, $rules);
        } catch (\Throwable $e) {
            throw new PersistenceException(
                sprintf('Failed to hydrate BookingModel from row ID "%s": %s', (string) ($row['model_id'] ?? 'unknown'), $e->getMessage()),
                0,
                $e
            );
        }
    }
}
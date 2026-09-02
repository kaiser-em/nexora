<?php

declare(strict_types=1);

namespace Silao\REST\Schema;

use Silao\Domain\Common\ValueObject\Currency;
use Silao\Domain\Common\ValueObject\Money;
use Silao\Domain\Model\BookingModel;
use Silao\Domain\Model\Enum\BookingModelStatus;
use Silao\Domain\Model\Enum\FieldType;
use Silao\Domain\Model\Enum\OptionPricingType;
use Silao\Domain\Model\Enum\ResourceStrategyType;
use Silao\Domain\Model\ValueObject\BookingModelId;
use Silao\Domain\Model\ValueObject\Field;
use Silao\Domain\Model\ValueObject\Option;
use Silao\Domain\Resource\ValueObject\ResourceId;
use WP_REST_Request;

final class BookingModelSchema
{
    public static function toBookingModel(WP_REST_Request $request, ?BookingModelId $id = null): BookingModel
    {
        $modelId = $id ?? BookingModelId::fromString((string) $request->get_param('model_id'));
        $slug = (string) $request->get_param('slug');
        $name = (string) $request->get_param('name');
        $description = (string) ($request->get_param('description') ?? '');
        $currency = Currency::of((string) ($request->get_param('currency') ?? 'EUR'));
        $basePrice = Money::of((int) ($request->get_param('base_price') ?? 0), $currency);
        $status = BookingModelStatus::tryFrom((string) ($request->get_param('status') ?? 'draft')) ?? BookingModelStatus::Draft;
        $strategy = ResourceStrategyType::tryFrom((string) ($request->get_param('resource_strategy') ?? 'none')) ?? ResourceStrategyType::None;

        $rawRes = $request->get_param('eligible_resource_ids');
        $eligibleResources = [];
        if (is_array($rawRes)) {
            foreach ($rawRes as $rId) {
                $eligibleResources[] = ResourceId::fromString((string) $rId);
            }
        }

        $rawFields = $request->get_param('fields');
        $fields = [];
        if (is_array($rawFields)) {
            foreach ($rawFields as $f) {
                if (is_array($f) && isset($f['name'], $f['label'])) {
                    $fType = FieldType::tryFrom((string) ($f['type'] ?? 'text')) ?? FieldType::Text;
                    $fields[] = new Field(
                        (string) $f['name'],
                        $fType,
                        (string) $f['label'],
                        (bool) ($f['is_required'] ?? false),
                        $f['default_value'] ?? null,
                        is_array($f['validation_rules'] ?? null) ? $f['validation_rules'] : []
                    );
                }
            }
        }

        $rawOptions = $request->get_param('options');
        $options = [];
        if (is_array($rawOptions)) {
            foreach ($rawOptions as $o) {
                if (is_array($o) && isset($o['id'], $o['code'], $o['name'], $o['unit_price'])) {
                    $pType = OptionPricingType::tryFrom((string) ($o['pricing_type'] ?? 'flat')) ?? OptionPricingType::Flat;
                    $uPrice = Money::of((int) $o['unit_price'], $currency);
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
                        (int) ($o['capacity_impact'] ?? 0)
                    );
                }
            }
        }

        return new BookingModel($modelId, $slug, $name, $description, $basePrice, $status, $strategy, $eligibleResources, $fields, $options, []);
    }
}
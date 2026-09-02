<?php

declare(strict_types=1);

namespace Silao\Tests\Unit\Domain\Engine;

use PHPUnit\Framework\TestCase;
use Silao\Domain\Common\ValueObject\Currency;
use Silao\Domain\Common\ValueObject\Money;
use Silao\Domain\Common\ValueObject\Percentage;
use Silao\Domain\Common\ValueObject\ZonedDateTimeRange;
use Silao\Domain\Condition\Enum\ComparisonOperator;
use Silao\Domain\Condition\ValueObject\SingleCondition;
use Silao\Domain\Engine\PricingEngine;
use Silao\Domain\Model\BookingModel;
use Silao\Domain\Model\Enum\BookingModelStatus;
use Silao\Domain\Model\Enum\OptionPricingType;
use Silao\Domain\Model\Enum\PricingCalculationBasis;
use Silao\Domain\Model\Enum\PricingTarget;
use Silao\Domain\Model\Enum\ResourceStrategyType;
use Silao\Domain\Model\ValueObject\BookingModelId;
use Silao\Domain\Model\ValueObject\Option;
use Silao\Domain\Model\ValueObject\PricingContext;
use Silao\Domain\Model\ValueObject\PricingRule;
use Silao\Domain\Model\ValueObject\SelectedOptionInput;
use Silao\Domain\Resource\ValueObject\ResourceId;

final class PricingEngineTest extends TestCase
{
    private Currency $eur;

    protected function setUp(): void
    {
        $this->eur = Currency::EUR();
    }

    public function testCompletePricingWithDistanceOptionsAndTax(): void
    {
        // 1. Base Model: 50.00 EUR
        $model = new BookingModel(
            BookingModelId::fromString('m1'),
            'transfer',
            'Transfer',
            '',
            Money::of(5000, $this->eur),
            BookingModelStatus::Published,
            ResourceStrategyType::SingleSelect,
            [ResourceId::fromString('r1')],
            [],
            [
                // Option: 10.00 EUR per unit
                new Option('opt_seat', 'seat', 'Child Seat', '', OptionPricingType::PerUnit, Money::of(1000, $this->eur), 0, 3),
            ],
            [
                // Rule 1: Distance 2.00 EUR per km
                new PricingRule('r_dist', 10, new SingleCondition('distance_km', ComparisonOperator::GreaterThan, 0), PricingTarget::BasePrice, PricingCalculationBasis::PerDistance, Money::of(200, $this->eur)),
                // Rule 2: Night surcharge 20% on gross subtotal
                new PricingRule('r_night', 20, new SingleCondition('time.hour', ComparisonOperator::GreaterThan, 20), PricingTarget::Subtotal, PricingCalculationBasis::PercentageOfGrossSubtotal, null, Percentage::fromPercent(20)),
            ]
        );

        // Monday 22:00 (Night!) in Paris, 20 km distance, 2 child seats
        $range = ZonedDateTimeRange::fromIsoStrings('2026-06-15 22:00:00', '2026-06-15 23:00:00', 'Europe/Paris');

        $context = new PricingContext(
            BookingModelId::fromString('m1'),
            ResourceId::fromString('r1'),
            $range,
            [SelectedOptionInput::of('opt_seat', 2)],
            ['distance_km' => 20],
            null,
            $this->eur
        );

        // Tax 20% (2000 bips)
        $quote = PricingEngine::calculate($model, $context, Percentage::fromPercent(20));

        // Base: 5000 + Distance(200 * 20 = 4000) -> BasePrice surcharges
        // Options: 1000 * 2 = 2000
        // GrossSubtotal: 5000 + 2000 = 7000
        // Surcharges: 4000 (distance) + 20% on grossSubtotal(7000 * 20% = 1400) = 5400
        // TaxableBase: 7000 + 5400 = 12400
        // Tax 20%: 12400 * 20% = 2480
        // GrandTotal: 12400 + 2480 = 14880 (148.80 EUR)

        $this->assertSame(14880, $quote->total()->amount);
        $this->assertSame('148.80 €', $quote->total()->format());
    }
}
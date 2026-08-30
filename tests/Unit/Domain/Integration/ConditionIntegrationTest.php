<?php

declare(strict_types=1);

namespace Nexora\Tests\Unit\Domain\Integration;

use Nexora\Domain\Common\ValueObject\Currency;
use Nexora\Domain\Common\ValueObject\ZonedDateTimeRange;
use Nexora\Domain\Condition\Enum\ComparisonOperator;
use Nexora\Domain\Condition\ValueObject\CompositeCondition;
use Nexora\Domain\Condition\ValueObject\SingleCondition;
use Nexora\Domain\Model\Enum\FieldType;
use Nexora\Domain\Model\ValueObject\BookingModelId;
use Nexora\Domain\Model\ValueObject\Field;
use Nexora\Domain\Model\ValueObject\PricingContext;
use Nexora\Domain\Model\ValueObject\SelectedOptionInput;
use PHPUnit\Framework\TestCase;

final class ConditionIntegrationTest extends TestCase
{
    public function testFieldVisibilityDrivenByPricingContextConditions(): void
    {
        // Condition: show child_seat_count only if (passengers > 0 AND options.child_seat.quantity > 0)
        $passengersCondition = new SingleCondition('passengers', ComparisonOperator::GreaterThan, 0);
        $childSeatOptionCondition = new SingleCondition('options.child_seat.quantity', ComparisonOperator::GreaterThan, 0);

        $visibilityCondition = CompositeCondition::and([$passengersCondition, $childSeatOptionCondition]);

        $childSeatField = new Field(
            'child_seat_count',
            FieldType::Number,
            'Number of child seats',
            false,
            null,
            [],
            $visibilityCondition
        );

        $range = ZonedDateTimeRange::fromIsoStrings('2026-06-15 10:00:00', '2026-06-15 12:00:00', 'Europe/Paris');

        // Context 1: Option selected and passengers = 2 -> Field visible
        $contextVisible = new PricingContext(
            BookingModelId::fromString('m1'),
            null,
            $range,
            [SelectedOptionInput::of('child_seat', 1)],
            ['passengers' => 2],
            null,
            Currency::EUR()
        );
        $this->assertTrue($childSeatField->isVisible($contextVisible));

        // Context 2: Option NOT selected -> Field hidden
        $contextHidden = new PricingContext(
            BookingModelId::fromString('m1'),
            null,
            $range,
            [],
            ['passengers' => 2],
            null,
            Currency::EUR()
        );
        $this->assertFalse($childSeatField->isVisible($contextHidden));
    }
}
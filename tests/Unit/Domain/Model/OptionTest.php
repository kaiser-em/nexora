<?php

declare(strict_types=1);

namespace Silao\Tests\Unit\Domain\Model;

use Silao\Domain\Common\ValueObject\Currency;
use Silao\Domain\Common\ValueObject\Money;
use Silao\Domain\Condition\Enum\ComparisonOperator;
use Silao\Domain\Condition\ValueObject\ArrayConditionContext;
use Silao\Domain\Condition\ValueObject\SingleCondition;
use Silao\Domain\Model\Enum\OptionPricingType;
use Silao\Domain\Model\Exception\InvalidOptionException;
use Silao\Domain\Model\ValueObject\Option;
use PHPUnit\Framework\TestCase;

final class OptionTest extends TestCase
{
    private Currency $eur;

    protected function setUp(): void
    {
        $this->eur = Currency::EUR();
    }

    public function testValidOptionCreation(): void
    {
        $option = new Option(
            'opt_baby_seat',
            'baby_seat',
            'Baby Seat',
            'Safe infant seat',
            OptionPricingType::PerUnit,
            Money::of(1000, $this->eur),
            0,
            3,
            false,
            1
        );

        $this->assertSame('opt_baby_seat', $option->id);
        $this->assertSame('baby_seat', $option->code);
        $this->assertSame('Baby Seat', $option->name);
        $this->assertSame(OptionPricingType::PerUnit, $option->pricingType);
        $this->assertSame(1000, $option->unitPrice->amount);
        $this->assertSame(0, $option->minQuantity);
        $this->assertSame(3, $option->maxQuantity);
        $this->assertSame(1, $option->capacityImpact);
    }

    public function testInvalidMinQuantityThrowsException(): void
    {
        $this->expectException(InvalidOptionException::class);
        new Option(
            'opt_1',
            'code',
            'Name',
            'Desc',
            OptionPricingType::Flat,
            Money::of(500, $this->eur),
            -1,
            2
        );
    }

    public function testMaxQuantityLowerThanMinThrowsException(): void
    {
        $this->expectException(InvalidOptionException::class);
        new Option(
            'opt_1',
            'code',
            'Name',
            'Desc',
            OptionPricingType::Flat,
            Money::of(500, $this->eur),
            3,
            2
        );
    }

    public function testNegativeCapacityImpactThrowsException(): void
    {
        $this->expectException(InvalidOptionException::class);
        new Option(
            'opt_1',
            'code',
            'Name',
            'Desc',
            OptionPricingType::Flat,
            Money::of(500, $this->eur),
            0,
            1,
            false,
            -1
        );
    }

    public function testAvailabilityWithCondition(): void
    {
        $condition = new SingleCondition('passengers', ComparisonOperator::GreaterThan, 0);
        $option = new Option(
            'opt_1',
            'code',
            'Name',
            'Desc',
            OptionPricingType::Flat,
            Money::of(500, $this->eur),
            0,
            1,
            false,
            0,
            $condition
        );

        $this->assertTrue($option->isAvailable(new ArrayConditionContext(['passengers' => 2])));
        $this->assertFalse($option->isAvailable(new ArrayConditionContext(['passengers' => 0])));
    }
}
<?php

declare(strict_types=1);

namespace Nexora\Tests\Unit\Domain\Model;

use Nexora\Domain\Common\ValueObject\Currency;
use Nexora\Domain\Common\ValueObject\Money;
use Nexora\Domain\Common\ValueObject\Percentage;
use Nexora\Domain\Condition\Enum\ComparisonOperator;
use Nexora\Domain\Condition\ValueObject\SingleCondition;
use Nexora\Domain\Model\Enum\PricingCalculationBasis;
use Nexora\Domain\Model\Enum\PricingTarget;
use Nexora\Domain\Model\Exception\InvalidPricingRuleException;
use Nexora\Domain\Model\ValueObject\PricingRule;
use PHPUnit\Framework\TestCase;

final class PricingRuleTest extends TestCase
{
    private Currency $eur;
    private SingleCondition $dummyCondition;

    protected function setUp(): void
    {
        $this->eur = Currency::EUR();
        $this->dummyCondition = new SingleCondition('passengers', ComparisonOperator::GreaterThan, 4);
    }

    public function testValidBasePriceRules(): void
    {
        $r1 = new PricingRule('r1', 10, $this->dummyCondition, PricingTarget::BasePrice, PricingCalculationBasis::FixedAmount, Money::of(2000, $this->eur));
        $this->assertSame(PricingTarget::BasePrice, $r1->target);

        $r2 = new PricingRule('r2', 20, $this->dummyCondition, PricingTarget::BasePrice, PricingCalculationBasis::PerDistance, Money::of(250, $this->eur));
        $this->assertSame(PricingCalculationBasis::PerDistance, $r2->calculationBasis);

        $r3 = new PricingRule('r3', 30, $this->dummyCondition, PricingTarget::BasePrice, PricingCalculationBasis::PercentageOfBase, null, Percentage::fromPercent(20));
        $this->assertSame(2000, $r3->adjustmentPercentage?->toBasisPoints());
    }

    public function testBasePriceWithPercentageOfGrossSubtotalThrowsException(): void
    {
        $this->expectException(InvalidPricingRuleException::class);
        new PricingRule('r_invalid', 10, $this->dummyCondition, PricingTarget::BasePrice, PricingCalculationBasis::PercentageOfGrossSubtotal, null, Percentage::fromPercent(10));
    }

    public function testValidSubtotalRules(): void
    {
        $r1 = new PricingRule('r_sub', 10, $this->dummyCondition, PricingTarget::Subtotal, PricingCalculationBasis::FixedAmount, Money::of(1000, $this->eur));
        $this->assertSame(PricingTarget::Subtotal, $r1->target);

        $r2 = new PricingRule('r_sub2', 20, $this->dummyCondition, PricingTarget::Subtotal, PricingCalculationBasis::PercentageOfGrossSubtotal, null, Percentage::fromPercent(15));
        $this->assertSame(1500, $r2->adjustmentPercentage?->toBasisPoints());
    }

    public function testSubtotalWithPerDistanceThrowsException(): void
    {
        $this->expectException(InvalidPricingRuleException::class);
        new PricingRule('r_invalid', 10, $this->dummyCondition, PricingTarget::Subtotal, PricingCalculationBasis::PerDistance, Money::of(100, $this->eur));
    }

    public function testValidFeeRules(): void
    {
        $r1 = new PricingRule('r_fee', 10, $this->dummyCondition, PricingTarget::Fee, PricingCalculationBasis::FixedAmount, Money::of(500, $this->eur));
        $this->assertSame(PricingTarget::Fee, $r1->target);

        $r2 = new PricingRule('r_fee2', 20, $this->dummyCondition, PricingTarget::Fee, PricingCalculationBasis::PercentageOfGrossSubtotal, null, Percentage::fromPercent(5));
        $this->assertSame(500, $r2->adjustmentPercentage?->toBasisPoints());
    }

    public function testValidDiscountRules(): void
    {
        $r1 = new PricingRule('r_d1', 10, $this->dummyCondition, PricingTarget::Discount, PricingCalculationBasis::FixedAmount, Money::of(1000, $this->eur));
        $this->assertSame(PricingTarget::Discount, $r1->target);

        $r2 = new PricingRule('r_d2', 20, $this->dummyCondition, PricingTarget::Discount, PricingCalculationBasis::PercentageOfGrossSubtotal, null, Percentage::fromPercent(10));
        $this->assertSame(1000, $r2->adjustmentPercentage?->toBasisPoints());

        $r3 = new PricingRule('r_d3', 30, $this->dummyCondition, PricingTarget::Discount, PricingCalculationBasis::PercentageOfBase, null, Percentage::fromPercent(15));
        $this->assertSame(1500, $r3->adjustmentPercentage?->toBasisPoints());
    }

    public function testPriorityZeroOrNegativeThrowsException(): void
    {
        $this->expectException(InvalidPricingRuleException::class);
        new PricingRule('r_bad', 0, $this->dummyCondition, PricingTarget::Fee, PricingCalculationBasis::FixedAmount, Money::of(100, $this->eur));
    }

    public function testPercentageBasisWithoutPercentageThrowsException(): void
    {
        $this->expectException(InvalidPricingRuleException::class);
        new PricingRule('r_bad', 10, $this->dummyCondition, PricingTarget::Fee, PricingCalculationBasis::PercentageOfGrossSubtotal, Money::of(100, $this->eur));
    }

    public function testFixedBasisWithoutMoneyThrowsException(): void
    {
        $this->expectException(InvalidPricingRuleException::class);
        new PricingRule('r_bad', 10, $this->dummyCondition, PricingTarget::Fee, PricingCalculationBasis::FixedAmount, null, Percentage::fromPercent(10));
    }
}
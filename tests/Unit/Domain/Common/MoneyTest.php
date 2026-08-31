<?php

declare(strict_types=1);

namespace Silao\Tests\Unit\Domain\Common;

use Silao\Domain\Common\Enum\RoundingMode;
use Silao\Domain\Common\Exception\CurrencyMismatchException;
use Silao\Domain\Common\Exception\InvalidArgumentException;
use Silao\Domain\Common\Exception\MoneyOverflowException;
use Silao\Domain\Common\ValueObject\Currency;
use Silao\Domain\Common\ValueObject\Money;
use Silao\Domain\Common\ValueObject\Percentage;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    private Currency $eur;
    private Currency $usd;

    protected function setUp(): void
    {
        $this->eur = Currency::EUR();
        $this->usd = Currency::USD();
    }

    public function testConstructionWithinSafetyCeiling(): void
    {
        $money = new Money(10000, $this->eur);
        $this->assertSame(10000, $money->amount);
        $this->assertTrue($money->currency->equals($this->eur));

        $maxMoney = new Money(Money::MAX_SAFETY_CEILING, $this->eur);
        $this->assertSame(Money::MAX_SAFETY_CEILING, $maxMoney->amount);

        $minMoney = new Money(-Money::MAX_SAFETY_CEILING, $this->eur);
        $this->assertSame(-Money::MAX_SAFETY_CEILING, $minMoney->amount);
    }

    public function testConstructionExceedingSafetyCeilingThrowsException(): void
    {
        $this->expectException(MoneyOverflowException::class);
        new Money(Money::MAX_SAFETY_CEILING + 1, $this->eur);
    }

    public function testConstructionBelowNegativeSafetyCeilingThrowsException(): void
    {
        $this->expectException(MoneyOverflowException::class);
        new Money(-Money::MAX_SAFETY_CEILING - 1, $this->eur);
    }

    public function testAddAndSubtractNominal(): void
    {
        $m1 = Money::of(10000, $this->eur);
        $m2 = Money::of(2550, $this->eur);

        $sum = $m1->add($m2);
        $this->assertSame(12550, $sum->amount);

        $diff = $m1->subtract($m2);
        $this->assertSame(7450, $diff->amount);
    }

    public function testAddCurrencyMismatchThrowsException(): void
    {
        $m1 = Money::of(10000, $this->eur);
        $m2 = Money::of(10000, $this->usd);

        $this->expectException(CurrencyMismatchException::class);
        $m1->add($m2);
    }

    public function testSubtractCurrencyMismatchThrowsException(): void
    {
        $m1 = Money::of(10000, $this->eur);
        $m2 = Money::of(10000, $this->usd);

        $this->expectException(CurrencyMismatchException::class);
        $m1->subtract($m2);
    }

    public function testAddOverflowExceedingCeilingThrowsException(): void
    {
        $m1 = Money::of(Money::MAX_SAFETY_CEILING, $this->eur);
        $m2 = Money::of(1, $this->eur);

        $this->expectException(MoneyOverflowException::class);
        $m1->add($m2);
    }

    public function testMultiplyNominalAndZero(): void
    {
        $m = Money::of(2500, $this->eur);

        $m2 = $m->multiply(3);
        $this->assertSame(7500, $m2->amount);

        $mZero = $m->multiply(0);
        $this->assertSame(0, $mZero->amount);

        $mNeg = $m->multiply(-2);
        $this->assertSame(-5000, $mNeg->amount);
    }

    public function testMultiplyZeroByPhpIntMinEvaluatesSafelyToZero(): void
    {
        $zero = Money::zero($this->eur);
        $result = $zero->multiply(PHP_INT_MIN);
        $this->assertSame(0, $result->amount);
    }

    public function testMultiplyOverflowProtectedWithoutFloatConversion(): void
    {
        $m = Money::of(100, $this->eur);
        $this->expectException(MoneyOverflowException::class);
        $m->multiply(PHP_INT_MAX);
    }

    public function testMultiplyRatioNominal(): void
    {
        $m = Money::of(10000, $this->eur);
        $res = $m->multiplyRatio(1, 2);
        $this->assertSame(5000, $res->amount);
    }

    public function testMultiplyRatioWithNegativeNumerator(): void
    {
        $m = Money::of(10000, $this->eur);
        $res = $m->multiplyRatio(-1, 2);
        $this->assertSame(-5000, $res->amount);
    }

    public function testMultiplyRatioWithDenominatorZeroOrNegativeThrowsException(): void
    {
        $m = Money::of(10000, $this->eur);

        $this->expectException(InvalidArgumentException::class);
        $m->multiplyRatio(1, 0);
    }

    public function testMultiplyRatioIntermediateOverflowThrowsException(): void
    {
        $large = Money::of(Money::MAX_SAFETY_CEILING, $this->eur);
        $this->expectException(MoneyOverflowException::class);
        $large->multiplyRatio(PHP_INT_MAX, 2);
    }

    /**
     * Formal HalfUp Test Matrix
     */
    public function testHalfUpRoundingMatrix(): void
    {
        $eur = $this->eur;

        // Positive
        $this->assertSame(1, Money::of(5, $eur)->multiplyRatio(1, 10, RoundingMode::HalfUp)->amount);  // 0.5 -> 1
        $this->assertSame(2, Money::of(15, $eur)->multiplyRatio(1, 10, RoundingMode::HalfUp)->amount); // 1.5 -> 2
        $this->assertSame(3, Money::of(25, $eur)->multiplyRatio(1, 10, RoundingMode::HalfUp)->amount); // 2.5 -> 3
        $this->assertSame(4, Money::of(35, $eur)->multiplyRatio(1, 10, RoundingMode::HalfUp)->amount); // 3.5 -> 4
        $this->assertSame(5, Money::of(45, $eur)->multiplyRatio(1, 10, RoundingMode::HalfUp)->amount); // 4.5 -> 5

        // Negative
        $this->assertSame(-1, Money::of(-5, $eur)->multiplyRatio(1, 10, RoundingMode::HalfUp)->amount);  // -0.5 -> -1
        $this->assertSame(-2, Money::of(-15, $eur)->multiplyRatio(1, 10, RoundingMode::HalfUp)->amount); // -1.5 -> -2
        $this->assertSame(-3, Money::of(-25, $eur)->multiplyRatio(1, 10, RoundingMode::HalfUp)->amount); // -2.5 -> -3
        $this->assertSame(-4, Money::of(-35, $eur)->multiplyRatio(1, 10, RoundingMode::HalfUp)->amount); // -3.5 -> -4
        $this->assertSame(-5, Money::of(-45, $eur)->multiplyRatio(1, 10, RoundingMode::HalfUp)->amount); // -4.5 -> -5
    }

    /**
     * Formal HalfEven Test Matrix
     */
    public function testHalfEvenRoundingMatrix(): void
    {
        $eur = $this->eur;

        // Positive
        $this->assertSame(0, Money::of(5, $eur)->multiplyRatio(1, 10, RoundingMode::HalfEven)->amount);  // 0.5 -> 0 (even)
        $this->assertSame(2, Money::of(15, $eur)->multiplyRatio(1, 10, RoundingMode::HalfEven)->amount); // 1.5 -> 2 (even)
        $this->assertSame(2, Money::of(25, $eur)->multiplyRatio(1, 10, RoundingMode::HalfEven)->amount); // 2.5 -> 2 (even)
        $this->assertSame(4, Money::of(35, $eur)->multiplyRatio(1, 10, RoundingMode::HalfEven)->amount); // 3.5 -> 4 (even)
        $this->assertSame(4, Money::of(45, $eur)->multiplyRatio(1, 10, RoundingMode::HalfEven)->amount); // 4.5 -> 4 (even)

        // Negative
        $this->assertSame(0, Money::of(-5, $eur)->multiplyRatio(1, 10, RoundingMode::HalfEven)->amount);  // -0.5 -> 0 (even)
        $this->assertSame(-2, Money::of(-15, $eur)->multiplyRatio(1, 10, RoundingMode::HalfEven)->amount); // -1.5 -> -2 (even)
        $this->assertSame(-2, Money::of(-25, $eur)->multiplyRatio(1, 10, RoundingMode::HalfEven)->amount); // -2.5 -> -2 (even)
        $this->assertSame(-4, Money::of(-35, $eur)->multiplyRatio(1, 10, RoundingMode::HalfEven)->amount); // -3.5 -> -4 (even)
        $this->assertSame(-4, Money::of(-45, $eur)->multiplyRatio(1, 10, RoundingMode::HalfEven)->amount); // -4.5 -> -4 (even)
    }

    public function testApplyPercentageNominalAndGreaterThanOneHundred(): void
    {
        $m = Money::of(10000, $this->eur); // 100.00 EUR

        // 20%
        $p20 = Percentage::fromPercent(20);
        $this->assertSame(2000, $m->applyPercentage($p20)->amount);

        // 8.5% (850 bips)
        $p85 = Percentage::fromBasisPoints(850);
        $this->assertSame(850, $m->applyPercentage($p85)->amount);

        // 150%
        $p150 = Percentage::fromPercent(150);
        $this->assertSame(15000, $m->applyPercentage($p150)->amount);
    }

    public function testComparisonsAndPredicates(): void
    {
        $m1 = Money::of(1000, $this->eur);
        $m2 = Money::of(2000, $this->eur);
        $mZero = Money::zero($this->eur);
        $mNeg = Money::of(-500, $this->eur);

        $this->assertTrue($m2->isGreaterThan($m1));
        $this->assertTrue($m2->isGreaterThanOrEqual($m1));
        $this->assertTrue($m1->isLessThan($m2));
        $this->assertTrue($m1->isLessThanOrEqual($m2));
        $this->assertFalse($m1->equals($m2));

        $this->assertTrue($mZero->isZero());
        $this->assertTrue($m1->isPositive());
        $this->assertFalse($m1->isNegative());
        $this->assertTrue($mNeg->isNegative());
        $this->assertFalse($mNeg->isPositive());
    }

    public function testFormatDelegatesToCurrency(): void
    {
        $m = Money::of(12550, $this->eur);
        $this->assertSame('125.50 €', $m->format());
    }
}
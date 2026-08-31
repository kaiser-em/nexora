<?php

declare(strict_types=1);

namespace Silao\Domain\Common\ValueObject;

use Silao\Domain\Common\Enum\RoundingMode;
use Silao\Domain\Common\Exception\CurrencyMismatchException;
use Silao\Domain\Common\Exception\InvalidArgumentException;
use Silao\Domain\Common\Exception\MoneyOverflowException;

final readonly class Money
{
    public const int MAX_SAFETY_CEILING = 1_000_000_000_000;

    public int $amount;
    public Currency $currency;

    /**
     * @throws MoneyOverflowException
     */
    public function __construct(int $amount, Currency $currency)
    {
        self::assertWithinCeiling($amount);
        $this->amount = $amount;
        $this->currency = $currency;
    }

    /**
     * @throws MoneyOverflowException
     */
    public static function of(int $amount, Currency $currency): self
    {
        return new self($amount, $currency);
    }

    public static function zero(Currency $currency): self
    {
        return new self(0, $currency);
    }

    /**
     * @throws CurrencyMismatchException
     * @throws MoneyOverflowException
     */
    public function add(self $other): self
    {
        $this->assertSameCurrency($other);
        $result = self::safeAdd($this->amount, $other->amount);
        return new self($result, $this->currency);
    }

    /**
     * @throws CurrencyMismatchException
     * @throws MoneyOverflowException
     */
    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);
        $result = self::safeSubtract($this->amount, $other->amount);
        return new self($result, $this->currency);
    }

    /**
     * @throws MoneyOverflowException
     */
    public function multiply(int $multiplier): self
    {
        $result = self::safeMultiply($this->amount, $multiplier);
        return new self($result, $this->currency);
    }

    /**
     * @throws InvalidArgumentException
     * @throws MoneyOverflowException
     */
    public function multiplyRatio(int $numerator, int $denominator, RoundingMode $mode = RoundingMode::HalfUp): self
    {
        if ($denominator <= 0) {
            throw new InvalidArgumentException(
                sprintf('Ratio denominator must be strictly greater than 0, %d given.', $denominator)
            );
        }

        $product = self::safeMultiply($this->amount, $numerator);
        $result = self::safeDivideWithRounding($product, $denominator, $mode);
        return new self($result, $this->currency);
    }

    /**
     * @throws MoneyOverflowException
     */
    public function applyPercentage(Percentage $percentage, RoundingMode $mode = RoundingMode::HalfUp): self
    {
        $product = self::safeMultiply($this->amount, $percentage->toBasisPoints());
        $result = self::safeDivideWithRounding($product, 10000, $mode);
        return new self($result, $this->currency);
    }

    /**
     * @throws CurrencyMismatchException
     */
    public function equals(self $other): bool
    {
        $this->assertSameCurrency($other);
        return $this->amount === $other->amount;
    }

    /**
     * @throws CurrencyMismatchException
     */
    public function isGreaterThan(self $other): bool
    {
        $this->assertSameCurrency($other);
        return $this->amount > $other->amount;
    }

    /**
     * @throws CurrencyMismatchException
     */
    public function isGreaterThanOrEqual(self $other): bool
    {
        $this->assertSameCurrency($other);
        return $this->amount >= $other->amount;
    }

    /**
     * @throws CurrencyMismatchException
     */
    public function isLessThan(self $other): bool
    {
        $this->assertSameCurrency($other);
        return $this->amount < $other->amount;
    }

    /**
     * @throws CurrencyMismatchException
     */
    public function isLessThanOrEqual(self $other): bool
    {
        $this->assertSameCurrency($other);
        return $this->amount <= $other->amount;
    }

    public function isZero(): bool
    {
        return $this->amount === 0;
    }

    public function isPositive(): bool
    {
        return $this->amount > 0;
    }

    public function isNegative(): bool
    {
        return $this->amount < 0;
    }

    public function format(): string
    {
        return $this->currency->format($this->amount);
    }

    /**
     * @throws CurrencyMismatchException
     */
    private function assertSameCurrency(self $other): void
    {
        if (!$this->currency->equals($other->currency)) {
            throw new CurrencyMismatchException(
                sprintf('Currency mismatch: cannot operate on "%s" and "%s".', $this->currency->code, $other->currency->code)
            );
        }
    }

    /**
     * @throws MoneyOverflowException
     */
    private static function assertWithinCeiling(int $amount): void
    {
        if ($amount > self::MAX_SAFETY_CEILING || $amount < -self::MAX_SAFETY_CEILING) {
            throw new MoneyOverflowException(
                sprintf('Money amount %d exceeds the safety ceiling of %d minor units.', $amount, self::MAX_SAFETY_CEILING)
            );
        }
    }

    /**
     * @throws MoneyOverflowException
     */
    private static function safeAdd(int $a, int $b): int
    {
        if ($b > 0 && $a > PHP_INT_MAX - $b) {
            throw new MoneyOverflowException('Integer addition overflow.');
        }
        if ($b < 0 && $a < PHP_INT_MIN - $b) {
            throw new MoneyOverflowException('Integer addition underflow.');
        }

        return $a + $b;
    }

    /**
     * @throws MoneyOverflowException
     */
    private static function safeSubtract(int $a, int $b): int
    {
        if ($b > 0 && $a < PHP_INT_MIN + $b) {
            throw new MoneyOverflowException('Integer subtraction underflow.');
        }
        if ($b < 0 && $a > PHP_INT_MAX + $b) {
            throw new MoneyOverflowException('Integer subtraction overflow.');
        }

        return $a - $b;
    }

    /**
     * @throws MoneyOverflowException
     */
    private static function safeMultiply(int $a, int $b): int
    {
        if ($a === 0 || $b === 0) {
            return 0;
        }

        if ($a === 1) {
            return $b;
        }
        if ($b === 1) {
            return $a;
        }

        if ($a === PHP_INT_MIN || $b === PHP_INT_MIN) {
            throw new MoneyOverflowException('Integer multiplication overflow involving PHP_INT_MIN.');
        }

        if ($a > 0 && $b > 0 && $a > intdiv(PHP_INT_MAX, $b)) {
            throw new MoneyOverflowException('Integer multiplication overflow.');
        }
        if ($a > 0 && $b < 0 && $b < intdiv(PHP_INT_MIN, $a)) {
            throw new MoneyOverflowException('Integer multiplication overflow.');
        }
        if ($a < 0 && $b > 0 && $a < intdiv(PHP_INT_MIN, $b)) {
            throw new MoneyOverflowException('Integer multiplication overflow.');
        }
        if ($a < 0 && $b < 0 && $a < intdiv(PHP_INT_MAX, $b)) {
            throw new MoneyOverflowException('Integer multiplication overflow.');
        }

        return $a * $b;
    }

    /**
     * @throws MoneyOverflowException
     */
    private static function safeDivideWithRounding(int $numerator, int $denominator, RoundingMode $mode): int
    {
        $q = intdiv($numerator, $denominator);
        $r = $numerator % $denominator;

        if ($r === 0) {
            return $q;
        }

        $absR = $r < 0 ? -$r : $r;
        $halfD = intdiv($denominator, 2);
        $remD = $denominator % 2;

        $isStrictlyLessThanHalf = false;
        $isStrictlyGreaterThanHalf = false;
        $isExactHalf = false;

        if ($absR < $halfD) {
            $isStrictlyLessThanHalf = true;
        } elseif ($absR > $halfD) {
            $isStrictlyGreaterThanHalf = true;
        } else {
            if ($remD === 1) {
                $isStrictlyLessThanHalf = true;
            } else {
                $isExactHalf = true;
            }
        }

        $sign = $numerator > 0 ? 1 : -1;

        if ($isStrictlyLessThanHalf) {
            return $q;
        }

        if ($isStrictlyGreaterThanHalf) {
            return self::safeAdd($q, $sign);
        }

        if ($mode === RoundingMode::HalfUp) {
            return self::safeAdd($q, $sign);
        }

        $isEven = ($q % 2) === 0;
        if ($isEven) {
            return $q;
        }

        return self::safeAdd($q, $sign);
    }
}
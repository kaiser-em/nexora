<?php

declare(strict_types=1);

namespace Silao\Domain\Common\ValueObject;

use Silao\Domain\Common\Enum\CurrencyPosition;
use Silao\Domain\Common\Exception\InvalidArgumentException;
use Silao\Domain\Common\Exception\InvalidCurrencyException;

final readonly class Currency
{
    public string $code;
    public string $symbol;
    public int $subunitPrecision;
    public CurrencyPosition $symbolPosition;

    /**
     * @throws InvalidCurrencyException
     * @throws InvalidArgumentException
     */
    public function __construct(
        string $code,
        string $symbol = '',
        int $subunitPrecision = 2,
        CurrencyPosition $symbolPosition = CurrencyPosition::After
    ) {
        $normalizedCode = strtoupper(trim($code));
        if (preg_match('/^[A-Z]{3}$/', $normalizedCode) !== 1) {
            throw new InvalidCurrencyException(
                sprintf('Invalid ISO-4217 currency code format: "%s". Must be 3 uppercase ASCII letters.', $code)
            );
        }

        if ($subunitPrecision < 0) {
            throw new InvalidArgumentException(
                sprintf('Subunit precision must be greater than or equal to 0, %d given.', $subunitPrecision)
            );
        }

        $this->code = $normalizedCode;
        $this->symbol = $symbol !== '' ? $symbol : $normalizedCode;
        $this->subunitPrecision = $subunitPrecision;
        $this->symbolPosition = $symbolPosition;
    }

    /**
     * @throws InvalidCurrencyException
     * @throws InvalidArgumentException
     */
    public static function of(string $code, string $symbol = '', int $precision = 2): self
    {
        return new self($code, $symbol, $precision);
    }

    public static function EUR(): self
    {
        return new self('EUR', '€', 2, CurrencyPosition::After);
    }

    public static function USD(): self
    {
        return new self('USD', '$', 2, CurrencyPosition::Before);
    }

    public static function GBP(): self
    {
        return new self('GBP', '£', 2, CurrencyPosition::Before);
    }

    public static function JPY(): self
    {
        return new self('JPY', '¥', 0, CurrencyPosition::Before);
    }

    public function equals(self $other): bool
    {
        return $this->code === $other->code
            && $this->subunitPrecision === $other->subunitPrecision;
    }

    public function format(int $minorUnits): string
    {
        $str = (string) $minorUnits;
        $isNegative = str_starts_with($str, '-');
        $rawDigits = $isNegative ? substr($str, 1) : $str;

        if ($this->subunitPrecision === 0) {
            $formattedAmount = $rawDigits;
        } else {
            $padded = str_pad($rawDigits, $this->subunitPrecision + 1, '0', STR_PAD_LEFT);
            $intPart = substr($padded, 0, -$this->subunitPrecision);
            $fracPart = substr($padded, -$this->subunitPrecision);
            $formattedAmount = $intPart . '.' . $fracPart;
        }

        $sign = $isNegative ? '-' : '';

        if ($this->symbolPosition === CurrencyPosition::Before) {
            return $sign . $this->symbol . $formattedAmount;
        }

        return $sign . $formattedAmount . ' ' . $this->symbol;
    }
}
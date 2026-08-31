<?php

declare(strict_types=1);

namespace Silao\Domain\Booking\ValueObject;

use DateTimeImmutable;
use DateTimeZone;
use Silao\Domain\Booking\Exception\InvalidPriceSnapshotException;
use Silao\Domain\Common\Exception\CurrencyMismatchException;
use Silao\Domain\Common\Exception\MoneyOverflowException;
use Silao\Domain\Common\ValueObject\Currency;
use Silao\Domain\Common\ValueObject\Money;

final readonly class PriceSnapshot
{
    public Currency $currency;
    public Money $subtotal;
    public Money $fees;
    public Money $discounts;
    public Money $total;
    public DateTimeImmutable $calculatedAt;

    /**
     * @throws InvalidPriceSnapshotException
     */
    public function __construct(
        Currency $currency,
        Money $subtotal,
        Money $fees,
        Money $discounts,
        Money $total,
        DateTimeImmutable $calculatedAt
    ) {
        if (
            !$subtotal->currency->equals($currency)
            || !$fees->currency->equals($currency)
            || !$discounts->currency->equals($currency)
            || !$total->currency->equals($currency)
        ) {
            throw new InvalidPriceSnapshotException('All money amounts in PriceSnapshot must match the snapshot currency.');
        }

        try {
            $expectedTotal = $subtotal->add($fees)->subtract($discounts);
            if (!$total->equals($expectedTotal)) {
                throw new InvalidPriceSnapshotException('PriceSnapshot total does not match subtotal + fees - discounts.');
            }
        } catch (CurrencyMismatchException | MoneyOverflowException $e) {
            throw new InvalidPriceSnapshotException('Arithmetic error in PriceSnapshot: ' . $e->getMessage(), 0, $e);
        }

        $this->currency = $currency;
        $this->subtotal = $subtotal;
        $this->fees = $fees;
        $this->discounts = $discounts;
        $this->total = $total;
        $this->calculatedAt = $calculatedAt->setTimezone(new DateTimeZone('UTC'));
    }

    /**
     * @throws InvalidPriceSnapshotException
     */
    public static function fromQuote(Quote $quote, ?DateTimeImmutable $calculatedAt = null): self
    {
        return new self(
            $quote->currency(),
            $quote->subtotal(),
            $quote->fees(),
            $quote->discounts(),
            $quote->total(),
            $calculatedAt ?? new DateTimeImmutable('now', new DateTimeZone('UTC'))
        );
    }
}
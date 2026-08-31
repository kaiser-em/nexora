<?php

declare(strict_types=1);

namespace Silao\Domain\Booking\ValueObject;

use Silao\Domain\Booking\Enum\QuoteLineType;
use Silao\Domain\Booking\Exception\InvalidQuoteException;
use Silao\Domain\Common\Exception\CurrencyMismatchException;
use Silao\Domain\Common\Exception\MoneyOverflowException;
use Silao\Domain\Common\ValueObject\Currency;
use Silao\Domain\Common\ValueObject\Money;

final readonly class Quote
{
    /** @var array<QuoteLine> */
    private array $lines;
    private Money $subtotal;
    private Money $fees;
    private Money $discounts;
    private Money $total;
    private Currency $currency;

    /**
     * @param array<mixed> $lines
     * @throws InvalidQuoteException
     */
    public function __construct(
        array $lines,
        Money $subtotal,
        Money $fees,
        Money $discounts,
        Money $total,
        Currency $currency
    ) {
        if (empty($lines)) {
            throw new InvalidQuoteException('Quote must contain at least one quote line.');
        }

        $calcSubtotal = Money::zero($currency);
        $calcFees = Money::zero($currency);
        $calcDiscounts = Money::zero($currency);

        $validatedLines = [];
        foreach ($lines as $line) {
            if (!$line instanceof QuoteLine) {
                throw new InvalidQuoteException('Each line of Quote must be an instance of QuoteLine.');
            }

            if (!$line->total()->currency->equals($currency)) {
                throw new InvalidQuoteException('Quote line currency mismatch with Quote currency.');
            }

            try {
                match ($line->type()) {
                    QuoteLineType::Fee => $calcFees = $calcFees->add($line->total()),
                    QuoteLineType::Discount => $calcDiscounts = $calcDiscounts->add($line->total()),
                    default => $calcSubtotal = $calcSubtotal->add($line->total()),
                };
            } catch (CurrencyMismatchException | MoneyOverflowException $e) {
                throw new InvalidQuoteException('Arithmetic error while summing quote lines: ' . $e->getMessage(), 0, $e);
            }

            $validatedLines[] = $line;
        }

        try {
            if (!$subtotal->equals($calcSubtotal)) {
                throw new InvalidQuoteException('Subtotal does not match sum of base, option, and adjustment lines.');
            }

            if (!$fees->equals($calcFees)) {
                throw new InvalidQuoteException('Fees total does not match sum of fee lines.');
            }

            if (!$discounts->equals($calcDiscounts)) {
                throw new InvalidQuoteException('Discounts total does not match sum of discount lines.');
            }

            $expectedTotal = $subtotal->add($fees)->subtract($discounts);
            if (!$total->equals($expectedTotal)) {
                throw new InvalidQuoteException('Quote total does not match subtotal + fees - discounts.');
            }
        } catch (CurrencyMismatchException | MoneyOverflowException $e) {
            throw new InvalidQuoteException('Arithmetic error while validating quote totals: ' . $e->getMessage(), 0, $e);
        }

        $this->lines = $validatedLines;
        $this->subtotal = $subtotal;
        $this->fees = $fees;
        $this->discounts = $discounts;
        $this->total = $total;
        $this->currency = $currency;
    }

    /**
     * @return array<QuoteLine>
     */
    public function lines(): array
    {
        return $this->lines;
    }

    public function subtotal(): Money
    {
        return $this->subtotal;
    }

    public function fees(): Money
    {
        return $this->fees;
    }

    public function discounts(): Money
    {
        return $this->discounts;
    }

    public function total(): Money
    {
        return $this->total;
    }

    public function currency(): Currency
    {
        return $this->currency;
    }
}
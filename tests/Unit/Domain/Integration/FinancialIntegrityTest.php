<?php

declare(strict_types=1);

namespace Nexora\Tests\Unit\Domain\Integration;

use Nexora\Domain\Booking\Enum\QuoteLineType;
use Nexora\Domain\Booking\ValueObject\Quote;
use Nexora\Domain\Booking\ValueObject\QuoteLine;
use Nexora\Domain\Common\Exception\CurrencyMismatchException;
use Nexora\Domain\Common\ValueObject\Currency;
use Nexora\Domain\Common\ValueObject\Money;
use Nexora\Domain\Common\ValueObject\Percentage;
use PHPUnit\Framework\TestCase;

final class FinancialIntegrityTest extends TestCase
{
    public function testCompletePricingChainExactnessWithoutFloats(): void
    {
        $eur = Currency::EUR();

        // 1. Base Price: 100.00 EUR
        $base = Money::of(10000, $eur);
        $baseLine = new QuoteLine('base', QuoteLineType::BasePrice, 'Base Price', 1, $base, $base);

        // 2. Options: 2x Child Seat @ 15.00 EUR = 30.00 EUR
        $optUnit = Money::of(1500, $eur);
        $optTotal = $optUnit->multiply(2);
        $optLine = new QuoteLine('opt_seat', QuoteLineType::Option, 'Child Seat (x2)', 2, $optUnit, $optTotal);

        // 3. Fee: 5.00 EUR Airport Fee
        $fee = Money::of(500, $eur);
        $feeLine = new QuoteLine('fee_airport', QuoteLineType::Fee, 'Airport Fee', 1, $fee, $fee);

        // 4. Discount: 10% on Gross Subtotal (13000 * 10% = 1300)
        $subtotal = $base->add($optTotal); // 13000
        $discountAmount = $subtotal->applyPercentage(Percentage::fromPercent(10)); // 1300
        $discountLine = new QuoteLine('disc_promo', QuoteLineType::Discount, '10% Promo', 1, $discountAmount, $discountAmount);

        $finalTotal = $subtotal->add($fee)->subtract($discountAmount); // 13000 + 500 - 1300 = 12200

        $quote = new Quote(
            [$baseLine, $optLine, $feeLine, $discountLine],
            $subtotal,
            $fee,
            $discountAmount,
            $finalTotal,
            $eur
        );

        $this->assertSame(13000, $quote->subtotal()->amount);
        $this->assertSame(500, $quote->fees()->amount);
        $this->assertSame(1300, $quote->discounts()->amount);
        $this->assertSame(12200, $quote->total()->amount);
        $this->assertSame('122.00 €', $quote->total()->format());
    }

    public function testCurrencyMismatchIsRejectedAcrossEntireFinancialChain(): void
    {
        $eur = Currency::EUR();
        $usd = Currency::USD();

        $mEur = Money::of(5000, $eur);
        $mUsd = Money::of(5000, $usd);

        $this->expectException(CurrencyMismatchException::class);
        $mEur->add($mUsd);
    }
}
<?php

declare(strict_types=1);

namespace Nexora\Tests\Unit\Domain\Booking;

use Nexora\Domain\Booking\Enum\QuoteLineType;
use Nexora\Domain\Booking\Exception\InvalidQuoteException;
use Nexora\Domain\Booking\ValueObject\Quote;
use Nexora\Domain\Booking\ValueObject\QuoteLine;
use Nexora\Domain\Common\ValueObject\Currency;
use Nexora\Domain\Common\ValueObject\Money;
use PHPUnit\Framework\TestCase;

final class QuoteTest extends TestCase
{
    private Currency $eur;

    protected function setUp(): void
    {
        $this->eur = Currency::EUR();
    }

    public function testValidQuoteComputation(): void
    {
        $baseLine = new QuoteLine('l1', QuoteLineType::BasePrice, 'Base Transfer', 1, Money::of(10000, $this->eur), Money::of(10000, $this->eur));
        $optLine = new QuoteLine('l2', QuoteLineType::Option, 'Child Seat (x2)', 2, Money::of(1000, $this->eur), Money::of(2000, $this->eur));
        $feeLine = new QuoteLine('l3', QuoteLineType::Fee, 'Airport Fee', 1, Money::of(500, $this->eur), Money::of(500, $this->eur));
        $discLine = new QuoteLine('l4', QuoteLineType::Discount, 'Promo Coupon', 1, Money::of(1500, $this->eur), Money::of(1500, $this->eur));

        $subtotal = Money::of(12000, $this->eur);  // 10000 + 2000
        $fees = Money::of(500, $this->eur);       // 500
        $discounts = Money::of(1500, $this->eur); // 1500
        $total = Money::of(11000, $this->eur);     // 12000 + 500 - 1500 = 11000

        $quote = new Quote([$baseLine, $optLine, $feeLine, $discLine], $subtotal, $fees, $discounts, $total, $this->eur);

        $this->assertCount(4, $quote->lines());
        $this->assertSame(12000, $quote->subtotal()->amount);
        $this->assertSame(500, $quote->fees()->amount);
        $this->assertSame(1500, $quote->discounts()->amount);
        $this->assertSame(11000, $quote->total()->amount);
        $this->assertTrue($quote->currency()->equals($this->eur));
    }

    public function testQuoteArithmeticMismatchThrowsException(): void
    {
        $baseLine = new QuoteLine('l1', QuoteLineType::BasePrice, 'Base', 1, Money::of(10000, $this->eur), Money::of(10000, $this->eur));

        $subtotal = Money::of(10000, $this->eur);
        $fees = Money::of(0, $this->eur);
        $discounts = Money::of(0, $this->eur);
        $wrongTotal = Money::of(9999, $this->eur); // Wrong total

        $this->expectException(InvalidQuoteException::class);
        new Quote([$baseLine], $subtotal, $fees, $discounts, $wrongTotal, $this->eur);
    }

    public function testQuoteCurrencyMismatchThrowsException(): void
    {
        $usdLine = new QuoteLine('l1', QuoteLineType::BasePrice, 'Base', 1, Money::of(10000, Currency::USD()), Money::of(10000, Currency::USD()));

        $this->expectException(InvalidQuoteException::class);
        new Quote([$usdLine], Money::of(10000, $this->eur), Money::of(0, $this->eur), Money::of(0, $this->eur), Money::of(10000, $this->eur), $this->eur);
    }
}
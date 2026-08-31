<?php

declare(strict_types=1);

namespace Silao\Tests\Unit\Domain\Booking;

use Silao\Domain\Booking\Enum\QuoteLineType;
use Silao\Domain\Booking\Exception\InvalidQuoteException;
use Silao\Domain\Booking\ValueObject\QuoteLine;
use Silao\Domain\Common\ValueObject\Currency;
use Silao\Domain\Common\ValueObject\Money;
use PHPUnit\Framework\TestCase;

final class QuoteLineTest extends TestCase
{
    private Currency $eur;

    protected function setUp(): void
    {
        $this->eur = Currency::EUR();
    }

    public function testValidQuoteLine(): void
    {
        $unitPrice = Money::of(1000, $this->eur);
        $total = Money::of(2000, $this->eur);

        $line = new QuoteLine('line_1', QuoteLineType::Option, 'Child Seat (x2)', 2, $unitPrice, $total, ['opt_id' => 'opt_seat']);

        $this->assertSame('line_1', $line->id());
        $this->assertSame(QuoteLineType::Option, $line->type());
        $this->assertSame('Child Seat (x2)', $line->description());
        $this->assertSame(2, $line->quantity());
        $this->assertSame(1000, $line->unitPrice()->amount);
        $this->assertSame(2000, $line->total()->amount);
        $this->assertSame(['opt_id' => 'opt_seat'], $line->metadata());
    }

    public function testTotalMismatchThrowsException(): void
    {
        $unitPrice = Money::of(1000, $this->eur);
        $wrongTotal = Money::of(2500, $this->eur); // 1000 * 2 != 2500

        $this->expectException(InvalidQuoteException::class);
        new QuoteLine('l1', QuoteLineType::Option, 'Seat', 2, $unitPrice, $wrongTotal);
    }

    public function testZeroOrNegativeQuantityThrowsException(): void
    {
        $unitPrice = Money::of(1000, $this->eur);
        $this->expectException(InvalidQuoteException::class);
        new QuoteLine('l1', QuoteLineType::Option, 'Seat', 0, $unitPrice, Money::of(0, $this->eur));
    }

    public function testEmptyDescriptionThrowsException(): void
    {
        $unitPrice = Money::of(1000, $this->eur);
        $this->expectException(InvalidQuoteException::class);
        new QuoteLine('l1', QuoteLineType::Option, '   ', 1, $unitPrice, $unitPrice);
    }
}
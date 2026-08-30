<?php

declare(strict_types=1);

namespace Nexora\Tests\Unit\Domain\Booking;

use DateTimeImmutable;
use DateTimeZone;
use Nexora\Domain\Booking\Enum\QuoteLineType;
use Nexora\Domain\Booking\Exception\InvalidPriceSnapshotException;
use Nexora\Domain\Booking\ValueObject\PriceSnapshot;
use Nexora\Domain\Booking\ValueObject\Quote;
use Nexora\Domain\Booking\ValueObject\QuoteLine;
use Nexora\Domain\Common\ValueObject\Currency;
use Nexora\Domain\Common\ValueObject\Money;
use PHPUnit\Framework\TestCase;

final class PriceSnapshotTest extends TestCase
{
    private Currency $eur;

    protected function setUp(): void
    {
        $this->eur = Currency::EUR();
    }

    public function testValidSnapshotCreation(): void
    {
        $subtotal = Money::of(10000, $this->eur);
        $fees = Money::of(500, $this->eur);
        $discounts = Money::of(1000, $this->eur);
        $total = Money::of(9500, $this->eur); // 10000 + 500 - 1000
        $now = new DateTimeImmutable('2026-06-15 12:00:00', new DateTimeZone('UTC'));

        $snapshot = new PriceSnapshot($this->eur, $subtotal, $fees, $discounts, $total, $now);

        $this->assertSame(10000, $snapshot->subtotal->amount);
        $this->assertSame(500, $snapshot->fees->amount);
        $this->assertSame(1000, $snapshot->discounts->amount);
        $this->assertSame(9500, $snapshot->total->amount);
        $this->assertSame('2026-06-15 12:00:00', $snapshot->calculatedAt->format('Y-m-d H:i:s'));
    }

    public function testFromQuoteFactory(): void
    {
        $line = new QuoteLine('l1', QuoteLineType::BasePrice, 'Base', 1, Money::of(5000, $this->eur), Money::of(5000, $this->eur));
        $quote = new Quote([$line], Money::of(5000, $this->eur), Money::of(0, $this->eur), Money::of(0, $this->eur), Money::of(5000, $this->eur), $this->eur);

        $snapshot = PriceSnapshot::fromQuote($quote);

        $this->assertSame(5000, $snapshot->total->amount);
        $this->assertTrue($snapshot->currency->equals($this->eur));
    }

    public function testTotalMismatchThrowsException(): void
    {
        $subtotal = Money::of(10000, $this->eur);
        $fees = Money::of(500, $this->eur);
        $discounts = Money::of(1000, $this->eur);
        $wrongTotal = Money::of(9000, $this->eur); // Mismatch

        $this->expectException(InvalidPriceSnapshotException::class);
        new PriceSnapshot($this->eur, $subtotal, $fees, $discounts, $wrongTotal, new DateTimeImmutable());
    }
}
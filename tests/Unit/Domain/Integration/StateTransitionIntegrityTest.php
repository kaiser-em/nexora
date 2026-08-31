<?php

declare(strict_types=1);

namespace Silao\Tests\Unit\Domain\Integration;

use Silao\Domain\Booking\Booking;
use Silao\Domain\Booking\Enum\QuoteLineType;
use Silao\Domain\Booking\Exception\InvalidBookingException;
use Silao\Domain\Booking\ValueObject\BookingId;
use Silao\Domain\Booking\ValueObject\BookingReference;
use Silao\Domain\Booking\ValueObject\FormDataSnapshot;
use Silao\Domain\Booking\ValueObject\Quote;
use Silao\Domain\Booking\ValueObject\QuoteLine;
use Silao\Domain\Common\ValueObject\Currency;
use Silao\Domain\Common\ValueObject\Money;
use Silao\Domain\Common\ValueObject\ZonedDateTimeRange;
use Silao\Domain\Customer\ValueObject\CustomerId;
use Silao\Domain\Customer\ValueObject\CustomerSnapshot;
use Silao\Domain\Customer\ValueObject\Email;
use Silao\Domain\Model\ValueObject\BookingModelId;
use PHPUnit\Framework\TestCase;

final class StateTransitionIntegrityTest extends TestCase
{
    private function createBooking(): Booking
    {
        return new Booking(
            BookingId::fromString('b_trans'),
            BookingReference::fromString('NEX-TR-001'),
            new CustomerSnapshot(CustomerId::fromString('c1'), Email::fromString('test@example.com'), 'John', 'Doe'),
            BookingModelId::fromString('m1'),
            null,
            ZonedDateTimeRange::fromIsoStrings('2026-06-15 10:00:00', '2026-06-15 12:00:00', 'UTC'),
            new FormDataSnapshot([])
        );
    }

    private function createQuote(): Quote
    {
        $eur = Currency::EUR();
        $line = new QuoteLine('l1', QuoteLineType::BasePrice, 'Base', 1, Money::of(5000, $eur), Money::of(5000, $eur));
        return new Quote([$line], Money::of(5000, $eur), Money::of(0, $eur), Money::of(0, $eur), Money::of(5000, $eur), $eur);
    }

    public function testCompleteNominalLifecycle(): void
    {
        $booking = $this->createBooking();
        $this->assertTrue($booking->status()->isDraft());

        $booking->createQuote($this->createQuote());
        $this->assertTrue($booking->status()->isQuoted());

        $booking->markPending();
        $this->assertTrue($booking->status()->isPending());

        $booking->confirm();
        $this->assertTrue($booking->status()->isConfirmed());

        $booking->complete();
        $this->assertTrue($booking->status()->isCompleted());

        // Verify chronological audit trail
        $events = $booking->events();
        $this->assertCount(5, $events);
        $this->assertSame('booking.created', $events[0]->type());
        $this->assertSame('booking.quoted', $events[1]->type());
        $this->assertSame('booking.pending', $events[2]->type());
        $this->assertSame('booking.confirmed', $events[3]->type());
        $this->assertSame('booking.completed', $events[4]->type());
    }

    public function testIllegalTransitionDirectlyFromDraftToConfirmedThrowsException(): void
    {
        $booking = $this->createBooking();
        $this->expectException(InvalidBookingException::class);
        $booking->confirm();
    }

    public function testCompletedBookingIsTerminalAndCannotBeReopened(): void
    {
        $booking = $this->createBooking();
        $booking->createQuote($this->createQuote());
        $booking->confirm();
        $booking->complete();

        $this->expectException(InvalidBookingException::class);
        $booking->cancel();
    }
}
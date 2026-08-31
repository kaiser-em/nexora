<?php

declare(strict_types=1);

namespace Silao\Tests\Unit\Domain\Booking;

use DateTimeImmutable;
use DateTimeZone;
use Silao\Domain\Booking\Event\BookingEvent;
use Silao\Domain\Booking\Exception\InvalidBookingException;
use PHPUnit\Framework\TestCase;

final class BookingEventTest extends TestCase
{
    public function testValidBookingEvent(): void
    {
        $now = new DateTimeImmutable('2026-06-15 10:00:00', new DateTimeZone('UTC'));
        $event = new BookingEvent('booking.confirmed', $now, ['actor' => 'admin']);

        $this->assertSame('booking.confirmed', $event->type());
        $this->assertSame('2026-06-15 10:00:00', $event->occurredAt()->format('Y-m-d H:i:s'));
        $this->assertSame(['actor' => 'admin'], $event->metadata());
    }

    public function testEmptyTypeThrowsException(): void
    {
        $this->expectException(InvalidBookingException::class);
        new BookingEvent('   ', new DateTimeImmutable());
    }
}
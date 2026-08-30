<?php

declare(strict_types=1);

namespace Nexora\Tests\Unit\Domain\Booking;

use Nexora\Domain\Booking\Exception\InvalidBookingException;
use Nexora\Domain\Booking\ValueObject\BookingId;
use PHPUnit\Framework\TestCase;

final class BookingIdTest extends TestCase
{
    public function testValidBookingId(): void
    {
        $id = new BookingId('book_123');
        $this->assertSame('book_123', $id->toString());
        $this->assertTrue($id->equals(BookingId::fromString('book_123')));
        $this->assertFalse($id->equals(BookingId::fromString('other_book')));
    }

    public function testEmptyBookingIdThrowsException(): void
    {
        $this->expectException(InvalidBookingException::class);
        new BookingId('   ');
    }
}
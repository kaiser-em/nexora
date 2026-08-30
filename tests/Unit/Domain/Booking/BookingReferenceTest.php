<?php

declare(strict_types=1);

namespace Nexora\Tests\Unit\Domain\Booking;

use Nexora\Domain\Booking\Exception\InvalidBookingReferenceException;
use Nexora\Domain\Booking\ValueObject\BookingReference;
use PHPUnit\Framework\TestCase;

final class BookingReferenceTest extends TestCase
{
    public function testValidBookingReference(): void
    {
        $ref = new BookingReference('  NEX-2026-X8K9M2  ');
        $this->assertSame('NEX-2026-X8K9M2', $ref->toString());
        $this->assertTrue($ref->equals(BookingReference::fromString('NEX-2026-X8K9M2')));
    }

    public function testEmptyReferenceThrowsException(): void
    {
        $this->expectException(InvalidBookingReferenceException::class);
        new BookingReference('   ');
    }
}
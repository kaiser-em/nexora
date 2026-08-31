<?php

declare(strict_types=1);

namespace Silao\Tests\Unit\Domain\Booking;

use Silao\Domain\Booking\Exception\InvalidBookingReferenceException;
use Silao\Domain\Booking\ValueObject\BookingReference;
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
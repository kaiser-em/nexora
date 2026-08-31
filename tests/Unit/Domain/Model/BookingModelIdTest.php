<?php

declare(strict_types=1);

namespace Silao\Tests\Unit\Domain\Model;

use Silao\Domain\Model\Exception\InvalidBookingModelException;
use Silao\Domain\Model\ValueObject\BookingModelId;
use PHPUnit\Framework\TestCase;

final class BookingModelIdTest extends TestCase
{
    public function testValidIdCreation(): void
    {
        $id = new BookingModelId('model_123');
        $this->assertSame('model_123', $id->toString());
        $this->assertTrue($id->equals(BookingModelId::fromString('model_123')));
        $this->assertFalse($id->equals(BookingModelId::fromString('other_id')));
    }

    public function testEmptyIdThrowsException(): void
    {
        $this->expectException(InvalidBookingModelException::class);
        new BookingModelId('   ');
    }
}
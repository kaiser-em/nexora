<?php

declare(strict_types=1);

namespace Nexora\Tests\Unit\Domain\Resource;

use Nexora\Domain\Resource\Exception\InvalidCapacityException;
use Nexora\Domain\Resource\ValueObject\Capacity;
use PHPUnit\Framework\TestCase;

final class CapacityTest extends TestCase
{
    public function testValidCapacity(): void
    {
        $c = new Capacity(4);
        $this->assertSame(4, $c->toInt());
        $this->assertTrue($c->isSufficientFor(3));
        $this->assertTrue($c->isSufficientFor(4));
        $this->assertFalse($c->isSufficientFor(5));
    }

    public function testCanAccommodate(): void
    {
        $c4 = Capacity::of(4);
        $c2 = Capacity::of(2);
        $c6 = Capacity::of(6);

        $this->assertTrue($c4->canAccommodate($c2));
        $this->assertTrue($c4->canAccommodate($c4));
        $this->assertFalse($c4->canAccommodate($c6));
    }

    public function testZeroOrNegativeCapacityThrowsException(): void
    {
        $this->expectException(InvalidCapacityException::class);
        new Capacity(0);
    }

    public function testNegativeCapacityThrowsException(): void
    {
        $this->expectException(InvalidCapacityException::class);
        Capacity::of(-5);
    }
}
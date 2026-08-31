<?php

declare(strict_types=1);

namespace Silao\Tests\Unit\Domain\Common;

use Silao\Domain\Common\Exception\InvalidPercentageException;
use Silao\Domain\Common\ValueObject\Percentage;
use PHPUnit\Framework\TestCase;

final class PercentageTest extends TestCase
{
    public function testFromBasisPoints(): void
    {
        $p = Percentage::fromBasisPoints(850);
        $this->assertSame(850, $p->toBasisPoints());
        $this->assertFalse($p->isZero());
    }

    public function testFromPercent(): void
    {
        $p = Percentage::fromPercent(20);
        $this->assertSame(2000, $p->toBasisPoints());
    }

    public function testZeroAndOneHundred(): void
    {
        $zero = Percentage::zero();
        $this->assertSame(0, $zero->toBasisPoints());
        $this->assertTrue($zero->isZero());

        $hundred = Percentage::oneHundred();
        $this->assertSame(10000, $hundred->toBasisPoints());
    }

    public function testNegativeBasisPointsThrowsException(): void
    {
        $this->expectException(InvalidPercentageException::class);
        Percentage::fromBasisPoints(-1);
    }

    public function testNegativePercentThrowsException(): void
    {
        $this->expectException(InvalidPercentageException::class);
        Percentage::fromPercent(-5);
    }

    public function testPercentageGreaterThanOneHundredPercent(): void
    {
        $p = Percentage::fromPercent(150);
        $this->assertSame(15000, $p->toBasisPoints());

        $large = Percentage::fromBasisPoints(50000);
        $this->assertSame(50000, $large->toBasisPoints());
    }

    public function testFromPercentMaxSafeValue(): void
    {
        $maxSafePercent = intdiv(PHP_INT_MAX, 100);
        $p = Percentage::fromPercent($maxSafePercent);
        $this->assertSame($maxSafePercent * 100, $p->toBasisPoints());
    }

    public function testFromPercentOverflowThrowsException(): void
    {
        $overflowPercent = intdiv(PHP_INT_MAX, 100) + 1;
        $this->expectException(InvalidPercentageException::class);
        Percentage::fromPercent($overflowPercent);
    }

    public function testComparisonsAndEquality(): void
    {
        $p1 = Percentage::fromPercent(20);
        $p2 = Percentage::fromBasisPoints(2000);
        $p3 = Percentage::fromPercent(25);

        $this->assertTrue($p1->equals($p2));
        $this->assertFalse($p1->equals($p3));
        $this->assertTrue($p3->isGreaterThan($p1));
        $this->assertFalse($p1->isGreaterThan($p3));
    }
}
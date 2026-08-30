<?php

declare(strict_types=1);

namespace Nexora\Tests\Unit\Domain\Common;

use Nexora\Domain\Common\Exception\InvalidTimeOfDayException;
use Nexora\Domain\Common\ValueObject\TimeOfDay;
use PHPUnit\Framework\TestCase;

final class TimeOfDayTest extends TestCase
{
    public function testCreationAndAccessors(): void
    {
        $time = new TimeOfDay(14, 30, 45);
        $this->assertSame(14, $time->hour);
        $this->assertSame(30, $time->minute);
        $this->assertSame(45, $time->second);
        $this->assertSame(870, $time->toMinutes());
        $this->assertSame(52245, $time->toSeconds());
        $this->assertSame('14:30', $time->format());
        $this->assertSame('14:30:45', $time->format('H:i:s'));
    }

    public function testFromString(): void
    {
        $t1 = TimeOfDay::fromString('08:30');
        $this->assertSame(8, $t1->hour);
        $this->assertSame(30, $t1->minute);
        $this->assertSame(0, $t1->second);

        $t2 = TimeOfDay::fromString('18:45:15');
        $this->assertSame(18, $t2->hour);
        $this->assertSame(45, $t2->minute);
        $this->assertSame(15, $t2->second);
    }

    public function testInvalidHourThrowsException(): void
    {
        $this->expectException(InvalidTimeOfDayException::class);
        new TimeOfDay(24, 0);
    }

    public function testInvalidMinuteThrowsException(): void
    {
        $this->expectException(InvalidTimeOfDayException::class);
        new TimeOfDay(12, 60);
    }

    public function testInvalidSecondThrowsException(): void
    {
        $this->expectException(InvalidTimeOfDayException::class);
        new TimeOfDay(12, 0, 60);
    }

    public function testInvalidStringFormatThrowsException(): void
    {
        $this->expectException(InvalidTimeOfDayException::class);
        TimeOfDay::fromString('invalid-time');
    }

    public function testComparisons(): void
    {
        $t1 = TimeOfDay::of(8, 0);
        $t2 = TimeOfDay::of(9, 30);
        $t3 = TimeOfDay::of(8, 0);

        $this->assertTrue($t1->isBefore($t2));
        $this->assertFalse($t2->isBefore($t1));
        $this->assertTrue($t2->isAfter($t1));
        $this->assertTrue($t1->equals($t3));
    }

    public function testIsBetweenStandardDaytimeSlot(): void
    {
        $start = TimeOfDay::of(8, 0);
        $end = TimeOfDay::of(18, 0);

        $this->assertTrue(TimeOfDay::of(12, 0)->isBetween($start, $end));
        $this->assertTrue(TimeOfDay::of(8, 0)->isBetween($start, $end));
        $this->assertTrue(TimeOfDay::of(18, 0)->isBetween($start, $end));
        $this->assertFalse(TimeOfDay::of(7, 59)->isBetween($start, $end));
        $this->assertFalse(TimeOfDay::of(18, 1)->isBetween($start, $end));
    }

    public function testIsBetweenOvernightSlot(): void
    {
        $start = TimeOfDay::of(22, 0);
        $end = TimeOfDay::of(6, 0);

        $this->assertTrue(TimeOfDay::of(23, 0)->isBetween($start, $end));
        $this->assertTrue(TimeOfDay::of(2, 0)->isBetween($start, $end));
        $this->assertTrue(TimeOfDay::of(22, 0)->isBetween($start, $end));
        $this->assertTrue(TimeOfDay::of(6, 0)->isBetween($start, $end));
        $this->assertFalse(TimeOfDay::of(12, 0)->isBetween($start, $end));
        $this->assertFalse(TimeOfDay::of(7, 0)->isBetween($start, $end));
    }

    public function testIsBetweenSinglePointInTime(): void
    {
        $point = TimeOfDay::of(14, 0);

        $this->assertTrue(TimeOfDay::of(14, 0, 0)->isBetween($point, $point));
        $this->assertFalse(TimeOfDay::of(14, 0, 1)->isBetween($point, $point));
        $this->assertFalse(TimeOfDay::of(13, 59, 59)->isBetween($point, $point));
    }
}
<?php

declare(strict_types=1);

namespace Silao\Tests\Unit\Domain\Resource;

use Silao\Domain\Common\ValueObject\TimeOfDay;
use Silao\Domain\Resource\Exception\InvalidScheduleException;
use Silao\Domain\Resource\ValueObject\Schedule;
use PHPUnit\Framework\TestCase;

final class ScheduleTest extends TestCase
{
    public function testValidScheduleCreation(): void
    {
        $start = TimeOfDay::of(8, 0);
        $end = TimeOfDay::of(18, 0);
        $schedule = new Schedule(1, $start, $end);

        $this->assertSame(1, $schedule->dayOfWeek);
        $this->assertTrue($schedule->startTime->equals($start));
        $this->assertTrue($schedule->endTime->equals($end));
    }

    public function testInvalidDayOfWeekThrowsException(): void
    {
        $this->expectException(InvalidScheduleException::class);
        Schedule::of(0, TimeOfDay::of(8, 0), TimeOfDay::of(18, 0));
    }

    public function testStartTimeAfterEndTimeThrowsException(): void
    {
        $this->expectException(InvalidScheduleException::class);
        Schedule::of(1, TimeOfDay::of(18, 0), TimeOfDay::of(8, 0));
    }

    public function testHalfOpenIntervalIsOpenAt(): void
    {
        $schedule = Schedule::of(1, TimeOfDay::of(8, 0), TimeOfDay::of(12, 0));

        // Day 1 (Monday)
        $this->assertTrue($schedule->isOpenAt(1, TimeOfDay::of(8, 0)));     // Start inclusive
        $this->assertTrue($schedule->isOpenAt(1, TimeOfDay::of(11, 59, 59)));
        $this->assertFalse($schedule->isOpenAt(1, TimeOfDay::of(12, 0)));   // End EXCLUSIVE
        $this->assertFalse($schedule->isOpenAt(1, TimeOfDay::of(7, 59)));

        // Different day (Tuesday = 2)
        $this->assertFalse($schedule->isOpenAt(2, TimeOfDay::of(10, 0)));
    }

    public function testCoversInterval(): void
    {
        $schedule = Schedule::of(1, TimeOfDay::of(8, 0), TimeOfDay::of(18, 0));

        $this->assertTrue($schedule->covers(TimeOfDay::of(8, 0), TimeOfDay::of(18, 0)));
        $this->assertTrue($schedule->covers(TimeOfDay::of(9, 0), TimeOfDay::of(17, 0)));
        $this->assertFalse($schedule->covers(TimeOfDay::of(7, 30), TimeOfDay::of(10, 0)));
        $this->assertFalse($schedule->covers(TimeOfDay::of(10, 0), TimeOfDay::of(19, 0)));
    }

    public function testOverlapsOnSameDay(): void
    {
        $s1 = Schedule::of(1, TimeOfDay::of(8, 0), TimeOfDay::of(12, 0));
        $s2 = Schedule::of(1, TimeOfDay::of(10, 0), TimeOfDay::of(14, 0)); // Overlap
        $s3 = Schedule::of(1, TimeOfDay::of(12, 0), TimeOfDay::of(16, 0)); // Adjacent -> NO overlap
        $sOtherDay = Schedule::of(2, TimeOfDay::of(10, 0), TimeOfDay::of(14, 0)); // Different day

        $this->assertTrue($s1->overlaps($s2));
        $this->assertFalse($s1->overlaps($s3)); // Touching boundary non-conflicting
        $this->assertFalse($s1->overlaps($sOtherDay));
    }
}
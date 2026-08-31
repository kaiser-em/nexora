<?php

declare(strict_types=1);

namespace Silao\Tests\Unit\Domain\Resource;

use Silao\Domain\Common\ValueObject\BlackoutPeriod;
use Silao\Domain\Common\ValueObject\TimeOfDay;
use Silao\Domain\Common\ValueObject\ZonedDateTimeRange;
use Silao\Domain\Resource\Enum\ResourceStatus;
use Silao\Domain\Resource\Exception\InvalidResourceException;
use Silao\Domain\Resource\Exception\ScheduleOverlapException;
use Silao\Domain\Resource\Resource;
use Silao\Domain\Resource\ValueObject\Capacity;
use Silao\Domain\Resource\ValueObject\ResourceId;
use Silao\Domain\Resource\ValueObject\Schedule;
use PHPUnit\Framework\TestCase;

final class ResourceTest extends TestCase
{
    public function testCreationWithValidAttributes(): void
    {
        $resource = new Resource(
            new ResourceId('res_1'),
            'VIP Mercedes V-Class',
            Capacity::of(7),
            ResourceStatus::Active,
            [],
            [],
            ['category' => 'van']
        );

        $this->assertSame('res_1', $resource->id()->toString());
        $this->assertSame('VIP Mercedes V-Class', $resource->name());
        $this->assertSame(7, $resource->capacity()->toInt());
        $this->assertSame(ResourceStatus::Active, $resource->status());
        $this->assertSame(['category' => 'van'], $resource->metadata());
    }

    public function testEmptyNameThrowsException(): void
    {
        $this->expectException(InvalidResourceException::class);
        new Resource(new ResourceId('res_1'), '   ', Capacity::of(4));
    }

    public function testInitialOverlappingSchedulesThrowsException(): void
    {
        $s1 = Schedule::of(1, TimeOfDay::of(8, 0), TimeOfDay::of(12, 0));
        $s2 = Schedule::of(1, TimeOfDay::of(10, 0), TimeOfDay::of(14, 0)); // Overlap

        $this->expectException(ScheduleOverlapException::class);
        new Resource(new ResourceId('res_1'), 'Van', Capacity::of(4), ResourceStatus::Active, [$s1, $s2]);
    }

    public function testAddOverlappingScheduleThrowsException(): void
    {
        $resource = new Resource(new ResourceId('res_1'), 'Van', Capacity::of(4));
        $resource->addSchedule(Schedule::of(1, TimeOfDay::of(8, 0), TimeOfDay::of(12, 0)));

        $this->expectException(ScheduleOverlapException::class);
        $resource->addSchedule(Schedule::of(1, TimeOfDay::of(11, 0), TimeOfDay::of(15, 0)));
    }

    public function testRenameMutation(): void
    {
        $resource = new Resource(new ResourceId('res_1'), 'Initial Name', Capacity::of(4));
        $resource->rename('Updated Name');
        $this->assertSame('Updated Name', $resource->name());

        $this->expectException(InvalidResourceException::class);
        $resource->rename('   ');
    }

    public function testIsAvailableForSlotWithSchedulesAndBlackouts(): void
    {
        $resource = new Resource(new ResourceId('res_1'), 'Van', Capacity::of(4));

        // Monday 08:00 to 18:00
        $resource->addSchedule(Schedule::of(1, TimeOfDay::of(8, 0), TimeOfDay::of(18, 0)));

        // Blackout on 2026-06-15 from 12:00 to 14:00 UTC
        $blackoutRange = ZonedDateTimeRange::fromIsoStrings('2026-06-15 12:00:00', '2026-06-15 14:00:00', 'UTC');
        $resource->addBlackoutPeriod(new BlackoutPeriod($blackoutRange, 'Maintenance'));

        // 2026-06-15 is a Monday. In Paris (UTC+2), 08:00 UTC is 10:00 local time.
        // Booking A: Monday 10:00 to 11:30 local in Paris (08:00 to 09:30 UTC) -> Available
        $slotA = ZonedDateTimeRange::fromIsoStrings('2026-06-15 10:00:00', '2026-06-15 11:30:00', 'Europe/Paris');
        $this->assertTrue($resource->isAvailableForSlot($slotA));

        // Booking B: Monday 13:00 to 15:00 local in Paris (11:00 to 13:00 UTC) -> Blocked by blackout (12:00-14:00 UTC)
        $slotB = ZonedDateTimeRange::fromIsoStrings('2026-06-15 13:00:00', '2026-06-15 15:00:00', 'Europe/Paris');
        $this->assertFalse($resource->isAvailableForSlot($slotB));

        // Booking C: Tuesday 10:00 to 11:00 (day 2, closed) -> False
        $slotC = ZonedDateTimeRange::fromIsoStrings('2026-06-16 10:00:00', '2026-06-16 11:00:00', 'Europe/Paris');
        $this->assertFalse($resource->isAvailableForSlot($slotC));

        // Status Inactive -> False
        $resource->updateStatus(ResourceStatus::Inactive);
        $this->assertFalse($resource->isAvailableForSlot($slotA));
    }
}
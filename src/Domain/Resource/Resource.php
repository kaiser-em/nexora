<?php

declare(strict_types=1);

namespace Nexora\Domain\Resource;

use Nexora\Domain\Common\ValueObject\BlackoutPeriod;
use Nexora\Domain\Common\ValueObject\TimeOfDay;
use Nexora\Domain\Common\ValueObject\ZonedDateTimeRange;
use Nexora\Domain\Resource\Enum\ResourceStatus;
use Nexora\Domain\Resource\Exception\InvalidResourceException;
use Nexora\Domain\Resource\Exception\ScheduleOverlapException;
use Nexora\Domain\Resource\ValueObject\Capacity;
use Nexora\Domain\Resource\ValueObject\ResourceId;
use Nexora\Domain\Resource\ValueObject\Schedule;

final class Resource
{
    /**
     * @param array<Schedule> $schedules
     * @param array<BlackoutPeriod> $blackoutPeriods
     * @param array<string, mixed> $metadata
     * @throws InvalidResourceException
     * @throws ScheduleOverlapException
     */
    public function __construct(
        private readonly ResourceId $id,
        private string $name,
        private Capacity $capacity,
        private ResourceStatus $status = ResourceStatus::Active,
        private array $schedules = [],
        private array $blackoutPeriods = [],
        private array $metadata = []
    ) {
        $trimmedName = trim($name);
        if ($trimmedName === '') {
            throw new InvalidResourceException('Resource name cannot be empty.');
        }
        $this->name = $trimmedName;

        self::assertNoOverlappingSchedules($schedules);
        $this->schedules = array_values($schedules);
        $this->blackoutPeriods = array_values($blackoutPeriods);
    }

    public function id(): ResourceId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function capacity(): Capacity
    {
        return $this->capacity;
    }

    public function status(): ResourceStatus
    {
        return $this->status;
    }

    /**
     * @return array<Schedule>
     */
    public function schedules(): array
    {
        return $this->schedules;
    }

    /**
     * @return array<BlackoutPeriod>
     */
    public function blackoutPeriods(): array
    {
        return $this->blackoutPeriods;
    }

    /**
     * @return array<string, mixed>
     */
    public function metadata(): array
    {
        return $this->metadata;
    }

    /**
     * @throws ScheduleOverlapException
     */
    public function addSchedule(Schedule $schedule): void
    {
        foreach ($this->schedules as $existing) {
            if ($existing->overlaps($schedule)) {
                throw new ScheduleOverlapException(
                    sprintf(
                        'Schedule for day %d (%s-%s) overlaps with existing schedule (%s-%s).',
                        $schedule->dayOfWeek,
                        $schedule->startTime->format(),
                        $schedule->endTime->format(),
                        $existing->startTime->format(),
                        $existing->endTime->format()
                    )
                );
            }
        }

        $this->schedules[] = $schedule;
    }

    public function addBlackoutPeriod(BlackoutPeriod $blackoutPeriod): void
    {
        $this->blackoutPeriods[] = $blackoutPeriod;
    }

    public function updateStatus(ResourceStatus $status): void
    {
        $this->status = $status;
    }

    public function updateCapacity(Capacity $capacity): void
    {
        $this->capacity = $capacity;
    }

    /**
     * @throws InvalidResourceException
     */
    public function rename(string $name): void
    {
        $trimmed = trim($name);
        if ($trimmed === '') {
            throw new InvalidResourceException('Resource name cannot be empty.');
        }
        $this->name = $trimmed;
    }

    public function isBlockedByBlackout(ZonedDateTimeRange $range): bool
    {
        foreach ($this->blackoutPeriods as $blackout) {
            if ($blackout->isBlocking($range)) {
                return true;
            }
        }

        return false;
    }

    public function isAvailableForSlot(ZonedDateTimeRange $range): bool
    {
        if (!$this->status->isAvailableForBooking()) {
            return false;
        }

        if ($this->isBlockedByBlackout($range)) {
            return false;
        }

        // If no schedules are configured, resource has no schedule restriction
        if (empty($this->schedules)) {
            return true;
        }

        $localStart = $range->startsAtLocal();
        $localEnd = $range->endsAtLocal();

        // Multi-day local bookings are not supported by single-day schedule configuration
        if ($localStart->format('Y-m-d') !== $localEnd->format('Y-m-d')) {
            return false;
        }

        $dayOfWeek = (int) $localStart->format('N');
        $startTime = TimeOfDay::fromString($localStart->format('H:i:s'));
        $endTime = TimeOfDay::fromString($localEnd->format('H:i:s'));

        foreach ($this->schedules as $schedule) {
            if ($schedule->dayOfWeek === $dayOfWeek && $schedule->covers($startTime, $endTime)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<Schedule> $schedules
     * @throws ScheduleOverlapException
     */
    private static function assertNoOverlappingSchedules(array $schedules): void
    {
        $count = count($schedules);
        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                if ($schedules[$i]->overlaps($schedules[$j])) {
                    throw new ScheduleOverlapException(
                        sprintf(
                            'Initial schedules on day %d overlap: (%s-%s) and (%s-%s).',
                            $schedules[$i]->dayOfWeek,
                            $schedules[$i]->startTime->format(),
                            $schedules[$i]->endTime->format(),
                            $schedules[$j]->startTime->format(),
                            $schedules[$j]->endTime->format()
                        )
                    );
                }
            }
        }
    }
}
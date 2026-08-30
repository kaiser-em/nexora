<?php

declare(strict_types=1);

namespace Nexora\Domain\Resource\ValueObject;

use Nexora\Domain\Common\ValueObject\TimeOfDay;
use Nexora\Domain\Resource\Exception\InvalidScheduleException;

final readonly class Schedule
{
    public int $dayOfWeek; // 1 (Mon) to 7 (Sun)
    public TimeOfDay $startTime;
    public TimeOfDay $endTime;

    /**
     * @throws InvalidScheduleException
     */
    public function __construct(int $dayOfWeek, TimeOfDay $startTime, TimeOfDay $endTime)
    {
        if ($dayOfWeek < 1 || $dayOfWeek > 7) {
            throw new InvalidScheduleException(
                sprintf('Day of week must be between 1 (Monday) and 7 (Sunday), %d given.', $dayOfWeek)
            );
        }

        if (!$startTime->isBefore($endTime)) {
            throw new InvalidScheduleException(
                sprintf(
                    'Start time (%s) must be strictly before end time (%s).',
                    $startTime->format(),
                    $endTime->format()
                )
            );
        }

        $this->dayOfWeek = $dayOfWeek;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
    }

    /**
     * @throws InvalidScheduleException
     */
    public static function of(int $dayOfWeek, TimeOfDay $startTime, TimeOfDay $endTime): self
    {
        return new self($dayOfWeek, $startTime, $endTime);
    }

    public function isOpenAt(int $dayOfWeek, TimeOfDay $time): bool
    {
        if ($this->dayOfWeek !== $dayOfWeek) {
            return false;
        }

        // Half-open interval [startTime, endTime)
        return $time->toSeconds() >= $this->startTime->toSeconds()
            && $time->toSeconds() < $this->endTime->toSeconds();
    }

    public function covers(TimeOfDay $start, TimeOfDay $end): bool
    {
        return $start->toSeconds() >= $this->startTime->toSeconds()
            && $end->toSeconds() <= $this->endTime->toSeconds();
    }

    public function overlaps(self $other): bool
    {
        if ($this->dayOfWeek !== $other->dayOfWeek) {
            return false;
        }

        // Strict half-open overlap: A.start < B.end && A.end > B.start
        return $this->startTime->isBefore($other->endTime)
            && $this->endTime->isAfter($other->startTime);
    }

    public function equals(self $other): bool
    {
        return $this->dayOfWeek === $other->dayOfWeek
            && $this->startTime->equals($other->startTime)
            && $this->endTime->equals($other->endTime);
    }
}
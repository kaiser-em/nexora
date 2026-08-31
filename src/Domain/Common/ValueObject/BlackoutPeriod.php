<?php

declare(strict_types=1);

namespace Silao\Domain\Common\ValueObject;

use DateTimeImmutable;

final readonly class BlackoutPeriod
{
    public ZonedDateTimeRange $range;
    public string $reason;

    public function __construct(ZonedDateTimeRange $range, string $reason = '')
    {
        $this->range = $range;
        $this->reason = trim(strip_tags($reason));
    }

    public function isBlocking(ZonedDateTimeRange $requestedRange): bool
    {
        return $this->range->overlaps($requestedRange);
    }

    public function contains(DateTimeImmutable $pointInTime): bool
    {
        return $this->range->contains($pointInTime);
    }

    public function equals(self $other): bool
    {
        return $this->range->equals($other->range)
            && $this->reason === $other->reason;
    }
}
<?php

declare(strict_types=1);

namespace Silao\Domain\Common\ValueObject;

use DateTimeImmutable;
use DateTimeZone;
use Silao\Domain\Common\Enum\RoundingMode;
use Silao\Domain\Common\Exception\InvalidDateTimeRangeException;

final readonly class ZonedDateTimeRange
{
    public DateTimeImmutable $startsAtUtc;
    public DateTimeImmutable $endsAtUtc;
    public DateTimeZone $timezone;

    /**
     * @throws InvalidDateTimeRangeException
     */
    public function __construct(
        DateTimeImmutable $startsAt,
        DateTimeImmutable $endsAt,
        DateTimeZone $timezone
    ) {
        $utc = new DateTimeZone('UTC');
        $startsAtUtc = $startsAt->setTimezone($utc);
        $endsAtUtc = $endsAt->setTimezone($utc);

        if ($startsAtUtc >= $endsAtUtc) {
            throw new InvalidDateTimeRangeException(
                sprintf(
                    'Start date (%s UTC) must be strictly before end date (%s UTC).',
                    $startsAtUtc->format('Y-m-d H:i:s'),
                    $endsAtUtc->format('Y-m-d H:i:s')
                )
            );
        }

        $this->startsAtUtc = $startsAtUtc;
        $this->endsAtUtc = $endsAtUtc;
        $this->timezone = $timezone;
    }

    /**
     * @throws InvalidDateTimeRangeException
     */
    public static function fromUtc(
        DateTimeImmutable $startsAtUtc,
        DateTimeImmutable $endsAtUtc,
        DateTimeZone $timezone
    ): self {
        return new self($startsAtUtc, $endsAtUtc, $timezone);
    }

    /**
     * @throws InvalidDateTimeRangeException
     */
    public static function fromLocal(
        DateTimeImmutable $startsAtLocal,
        DateTimeImmutable $endsAtLocal,
        DateTimeZone $timezone
    ): self {
        return new self($startsAtLocal, $endsAtLocal, $timezone);
    }

    /**
     * @throws InvalidDateTimeRangeException
     */
    public static function fromIsoStrings(
        string $startsAtIso,
        string $endsAtIso,
        string $timezoneName
    ): self {
        try {
            $tz = new DateTimeZone(trim($timezoneName));
        } catch (\Exception $e) {
            throw new InvalidDateTimeRangeException(
                sprintf('Invalid timezone name: "%s".', $timezoneName),
                0,
                $e
            );
        }

        try {
            $startsAt = new DateTimeImmutable(trim($startsAtIso), $tz);
            $endsAt = new DateTimeImmutable(trim($endsAtIso), $tz);
        } catch (\Exception $e) {
            throw new InvalidDateTimeRangeException(
                sprintf('Invalid date format in ISO strings: "%s", "%s".', $startsAtIso, $endsAtIso),
                0,
                $e
            );
        }

        return new self($startsAt, $endsAt, $tz);
    }

    public function startsAtLocal(): DateTimeImmutable
    {
        return $this->startsAtUtc->setTimezone($this->timezone);
    }

    public function endsAtLocal(): DateTimeImmutable
    {
        return $this->endsAtUtc->setTimezone($this->timezone);
    }

    public function durationInMinutes(): int
    {
        return intdiv($this->endsAtUtc->getTimestamp() - $this->startsAtUtc->getTimestamp(), 60);
    }

    public function durationInFullHours(): int
    {
        return intdiv($this->durationInMinutes(), 60);
    }

    public function durationInHours(RoundingMode $mode = RoundingMode::HalfUp): int
    {
        $numerator = $this->durationInMinutes();
        $denominator = 60;

        $q = intdiv($numerator, $denominator);
        $r = $numerator % $denominator;

        if ($r === 0) {
            return $q;
        }

        $absR = $r < 0 ? -$r : $r;
        $halfD = intdiv($denominator, 2); // 30

        if ($absR < $halfD) {
            return $q;
        }

        if ($absR > $halfD) {
            return $q + 1;
        }

        // Exact half (30 minutes)
        if ($mode === RoundingMode::HalfUp) {
            return $q + 1;
        }

        return ($q % 2 === 0) ? $q : $q + 1;
    }

    public function overlaps(self $other): bool
    {
        return $this->startsAtUtc < $other->endsAtUtc && $this->endsAtUtc > $other->startsAtUtc;
    }

    public function contains(DateTimeImmutable $pointInTime): bool
    {
        $pointUtc = $pointInTime->setTimezone(new DateTimeZone('UTC'));
        return $pointUtc >= $this->startsAtUtc && $pointUtc < $this->endsAtUtc;
    }

    public function isCompletelyBefore(self $other): bool
    {
        return $this->endsAtUtc <= $other->startsAtUtc;
    }

    public function isCompletelyAfter(self $other): bool
    {
        return $this->startsAtUtc >= $other->endsAtUtc;
    }

    public function equals(self $other): bool
    {
        return $this->startsAtUtc == $other->startsAtUtc
            && $this->endsAtUtc == $other->endsAtUtc
            && $this->timezone->getName() === $other->timezone->getName();
    }
}
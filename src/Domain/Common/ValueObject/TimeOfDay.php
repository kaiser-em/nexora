<?php

declare(strict_types=1);

namespace Nexora\Domain\Common\ValueObject;

use Nexora\Domain\Common\Exception\InvalidTimeOfDayException;

final readonly class TimeOfDay
{
    public int $hour;
    public int $minute;
    public int $second;

    /**
     * @throws InvalidTimeOfDayException
     */
    public function __construct(int $hour, int $minute, int $second = 0)
    {
        if ($hour < 0 || $hour > 23) {
            throw new InvalidTimeOfDayException(
                sprintf('Hour must be between 0 and 23, %d given.', $hour)
            );
        }

        if ($minute < 0 || $minute > 59) {
            throw new InvalidTimeOfDayException(
                sprintf('Minute must be between 0 and 59, %d given.', $minute)
            );
        }

        if ($second < 0 || $second > 59) {
            throw new InvalidTimeOfDayException(
                sprintf('Second must be between 0 and 59, %d given.', $second)
            );
        }

        $this->hour = $hour;
        $this->minute = $minute;
        $this->second = $second;
    }

    /**
     * @throws InvalidTimeOfDayException
     */
    public static function of(int $hour, int $minute, int $second = 0): self
    {
        return new self($hour, $minute, $second);
    }

    /**
     * @throws InvalidTimeOfDayException
     */
    public static function fromString(string $timeString): self
    {
        $trimmed = trim($timeString);
        if (preg_match('/^([0-1]?[0-9]|2[0-3]):([0-5][0-9])(?::([0-5][0-9]))?$/', $trimmed, $matches) !== 1) {
            throw new InvalidTimeOfDayException(
                sprintf('Invalid time string format: "%s". Expected "HH:MM" or "HH:MM:SS".', $timeString)
            );
        }

        $hour = (int) $matches[1];
        $minute = (int) $matches[2];
        $second = isset($matches[3]) ? (int) $matches[3] : 0;

        return new self($hour, $minute, $second);
    }

    public static function midnight(): self
    {
        return new self(0, 0, 0);
    }

    public static function endOfDay(): self
    {
        return new self(23, 59, 59);
    }

    public function toMinutes(): int
    {
        return $this->hour * 60 + $this->minute;
    }

    public function toSeconds(): int
    {
        return $this->hour * 3600 + $this->minute * 60 + $this->second;
    }

    public function isBefore(self $other): bool
    {
        return $this->toSeconds() < $other->toSeconds();
    }

    public function isAfter(self $other): bool
    {
        return $this->toSeconds() > $other->toSeconds();
    }

    public function equals(self $other): bool
    {
        return $this->toSeconds() === $other->toSeconds();
    }

    public function isBetween(self $start, self $end): bool
    {
        // Single point in time
        if ($start->equals($end)) {
            return $this->equals($start);
        }

        // Standard daytime slot (ex: 08:00 to 18:00)
        if ($start->isBefore($end)) {
            return $this->toSeconds() >= $start->toSeconds() && $this->toSeconds() <= $end->toSeconds();
        }

        // Overnight slot crossing midnight (ex: 22:00 to 06:00)
        return $this->toSeconds() >= $start->toSeconds() || $this->toSeconds() <= $end->toSeconds();
    }

    public function format(string $format = 'H:i'): string
    {
        $h24 = str_pad((string) $this->hour, 2, '0', STR_PAD_LEFT);
        $m = str_pad((string) $this->minute, 2, '0', STR_PAD_LEFT);
        $s = str_pad((string) $this->second, 2, '0', STR_PAD_LEFT);

        return str_replace(['H', 'i', 's'], [$h24, $m, $s], $format);
    }
}
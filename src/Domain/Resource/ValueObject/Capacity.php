<?php

declare(strict_types=1);

namespace Nexora\Domain\Resource\ValueObject;

use Nexora\Domain\Resource\Exception\InvalidCapacityException;

final readonly class Capacity
{
    public int $value;

    /**
     * @throws InvalidCapacityException
     */
    public function __construct(int $value)
    {
        if ($value <= 0) {
            throw new InvalidCapacityException(
                sprintf('Capacity must be strictly greater than 0, %d given.', $value)
            );
        }
        $this->value = $value;
    }

    /**
     * @throws InvalidCapacityException
     */
    public static function of(int $value): self
    {
        return new self($value);
    }

    public function toInt(): int
    {
        return $this->value;
    }

    public function isSufficientFor(int $requested): bool
    {
        return $this->value >= $requested;
    }

    public function canAccommodate(self $requested): bool
    {
        return $this->value >= $requested->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
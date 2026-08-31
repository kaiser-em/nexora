<?php

declare(strict_types=1);

namespace Silao\Domain\Model\ValueObject;

use Silao\Domain\Model\Exception\InvalidBookingModelException;

final readonly class BookingModelId
{
    public string $value;

    /**
     * @throws InvalidBookingModelException
     */
    public function __construct(string $value)
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new InvalidBookingModelException('Booking model ID cannot be empty.');
        }

        $this->value = $trimmed;
    }

    /**
     * @throws InvalidBookingModelException
     */
    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function toString(): string
    {
        return $this->value;
    }
}
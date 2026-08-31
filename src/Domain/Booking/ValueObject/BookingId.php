<?php

declare(strict_types=1);

namespace Silao\Domain\Booking\ValueObject;

use Silao\Domain\Booking\Exception\InvalidBookingException;

final readonly class BookingId
{
    public string $value;

    /**
     * @throws InvalidBookingException
     */
    public function __construct(string $value)
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new InvalidBookingException('Booking ID cannot be empty.');
        }

        $this->value = $trimmed;
    }

    /**
     * @throws InvalidBookingException
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
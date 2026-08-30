<?php

declare(strict_types=1);

namespace Nexora\Domain\Booking\ValueObject;

use Nexora\Domain\Booking\Exception\InvalidBookingReferenceException;

final readonly class BookingReference
{
    public string $value;

    /**
     * @throws InvalidBookingReferenceException
     */
    public function __construct(string $value)
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new InvalidBookingReferenceException('Booking reference cannot be empty.');
        }

        $this->value = $trimmed;
    }

    /**
     * @throws InvalidBookingReferenceException
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
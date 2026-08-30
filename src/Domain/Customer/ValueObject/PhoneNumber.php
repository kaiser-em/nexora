<?php

declare(strict_types=1);

namespace Nexora\Domain\Customer\ValueObject;

use Nexora\Domain\Customer\Exception\InvalidPhoneNumberException;

final readonly class PhoneNumber
{
    public string $value;

    /**
     * @throws InvalidPhoneNumberException
     */
    public function __construct(string $value)
    {
        $trimmed = trim($value);
        if (preg_match('/^[+]?[0-9\s\-().]{6,25}$/', $trimmed) !== 1) {
            throw new InvalidPhoneNumberException(
                sprintf('Phone number "%s" contains invalid characters or has invalid length.', $value)
            );
        }

        $digits = (string) preg_replace('/\D/', '', $trimmed);
        $digitCount = strlen($digits);

        if ($digitCount < 6 || $digitCount > 15) {
            throw new InvalidPhoneNumberException(
                sprintf('Phone number must contain between 6 and 15 digits (E.164 standard), %d digits found in "%s".', $digitCount, $value)
            );
        }

        $this->value = $trimmed;
    }

    /**
     * @throws InvalidPhoneNumberException
     */
    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function digits(): string
    {
        return (string) preg_replace('/\D/', '', $this->value);
    }

    public function equals(self $other): bool
    {
        return $this->digits() === $other->digits();
    }

    public function toString(): string
    {
        return $this->value;
    }
}
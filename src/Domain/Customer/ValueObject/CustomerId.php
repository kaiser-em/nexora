<?php

declare(strict_types=1);

namespace Nexora\Domain\Customer\ValueObject;

use Nexora\Domain\Customer\Exception\InvalidCustomerException;

final readonly class CustomerId
{
    public string $value;

    /**
     * @throws InvalidCustomerException
     */
    public function __construct(string $value)
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new InvalidCustomerException('Customer ID cannot be empty.');
        }
        $this->value = $trimmed;
    }

    /**
     * @throws InvalidCustomerException
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
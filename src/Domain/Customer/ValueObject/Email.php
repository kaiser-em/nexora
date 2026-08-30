<?php

declare(strict_types=1);

namespace Nexora\Domain\Customer\ValueObject;

use Nexora\Domain\Customer\Exception\InvalidEmailException;

final readonly class Email
{
    public string $value;

    /**
     * @throws InvalidEmailException
     */
    public function __construct(string $value)
    {
        $trimmed = trim(strtolower($value));
        if (filter_var($trimmed, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidEmailException(
                sprintf('Invalid email address format: "%s".', $value)
            );
        }
        $this->value = $trimmed;
    }

    /**
     * @throws InvalidEmailException
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
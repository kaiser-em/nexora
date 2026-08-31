<?php

declare(strict_types=1);

namespace Silao\Domain\Customer\ValueObject;

final readonly class CustomerSnapshot
{
    public function __construct(
        public CustomerId $customerId,
        public Email $email,
        public string $firstName,
        public string $lastName,
        public ?PhoneNumber $phone = null,
        public bool $isGuest = true
    ) {
    }

    public function fullName(): string
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }
}
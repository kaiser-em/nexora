<?php

declare(strict_types=1);

namespace Silao\Application\DTO;

final readonly class CustomerDTO
{
    public function __construct(
        public string $customerId,
        public string $email,
        public string $firstName,
        public string $lastName,
        public string $fullName,
        public ?string $phone,
        public bool $isGuest
    ) {
    }
}
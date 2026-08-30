<?php

declare(strict_types=1);

namespace Nexora\Domain\Customer;

use Nexora\Domain\Customer\Exception\InvalidCustomerException;
use Nexora\Domain\Customer\ValueObject\CustomerId;
use Nexora\Domain\Customer\ValueObject\CustomerSnapshot;
use Nexora\Domain\Customer\ValueObject\Email;
use Nexora\Domain\Customer\ValueObject\PhoneNumber;

final class Customer
{
    /**
     * @throws InvalidCustomerException
     */
    public function __construct(
        private readonly CustomerId $id,
        private Email $email,
        private string $firstName,
        private string $lastName,
        private ?PhoneNumber $phone = null,
        private ?int $wpUserId = null
    ) {
        $trimmedFirst = trim($firstName);
        $trimmedLast = trim($lastName);

        if ($trimmedFirst === '' || $trimmedLast === '') {
            throw new InvalidCustomerException('Customer first name and last name cannot be empty.');
        }

        if ($wpUserId !== null && $wpUserId <= 0) {
            throw new InvalidCustomerException(
                sprintf('WordPress user ID must be strictly greater than 0, %d given.', $wpUserId)
            );
        }

        $this->firstName = $trimmedFirst;
        $this->lastName = $trimmedLast;
    }

    public function id(): CustomerId
    {
        return $this->id;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function firstName(): string
    {
        return $this->firstName;
    }

    public function lastName(): string
    {
        return $this->lastName;
    }

    public function fullName(): string
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }

    public function phone(): ?PhoneNumber
    {
        return $this->phone;
    }

    public function wpUserId(): ?int
    {
        return $this->wpUserId;
    }

    public function isGuest(): bool
    {
        return $this->wpUserId === null;
    }

    public function isRegistered(): bool
    {
        return $this->wpUserId !== null;
    }

    public function updateContact(Email $email, ?PhoneNumber $phone): void
    {
        $this->email = $email;
        $this->phone = $phone;
    }

    /**
     * @throws InvalidCustomerException
     */
    public function updateName(string $firstName, string $lastName): void
    {
        $trimmedFirst = trim($firstName);
        $trimmedLast = trim($lastName);

        if ($trimmedFirst === '' || $trimmedLast === '') {
            throw new InvalidCustomerException('Customer first name and last name cannot be empty.');
        }

        $this->firstName = $trimmedFirst;
        $this->lastName = $trimmedLast;
    }

    /**
     * @throws InvalidCustomerException
     */
    public function linkToWordPressUser(int $wpUserId): void
    {
        if ($wpUserId <= 0) {
            throw new InvalidCustomerException(
                sprintf('WordPress user ID must be strictly greater than 0, %d given.', $wpUserId)
            );
        }

        $this->wpUserId = $wpUserId;
    }

    public function toSnapshot(): CustomerSnapshot
    {
        return new CustomerSnapshot(
            $this->id,
            $this->email,
            $this->firstName,
            $this->lastName,
            $this->phone,
            $this->isGuest()
        );
    }
}
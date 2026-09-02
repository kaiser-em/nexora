<?php

declare(strict_types=1);

namespace Silao\Infrastructure\Mapper;

use DateTimeImmutable;
use DateTimeZone;
use Silao\Domain\Customer\Customer;
use Silao\Domain\Customer\ValueObject\CustomerId;
use Silao\Domain\Customer\ValueObject\Email;
use Silao\Domain\Customer\ValueObject\PhoneNumber;
use Silao\Infrastructure\Exception\PersistenceException;

final class CustomerMapper
{
    /**
     * @return array<string, mixed>
     */
    public static function toDatabase(Customer $customer, ?DateTimeImmutable $now = null): array
    {
        $utcNow = ($now ?? new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');

        return [
            'customer_id' => $customer->id()->toString(),
            'wp_user_id' => $customer->wpUserId(),
            'email' => $customer->email()->toString(),
            'first_name' => $customer->firstName(),
            'last_name' => $customer->lastName(),
            'phone' => $customer->phone()?->toString(),
            'updated_at_utc' => $utcNow,
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @throws PersistenceException
     */
    public static function toDomain(array $row): Customer
    {
        $requiredKeys = ['customer_id', 'email', 'first_name', 'last_name'];
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $row) || $row[$key] === null) {
                throw new PersistenceException(sprintf('Corrupted customer row: missing required column "%s".', $key));
            }
        }

        try {
            $id = CustomerId::fromString((string) $row['customer_id']);
            $email = Email::fromString((string) $row['email']);
            $firstName = (string) $row['first_name'];
            $lastName = (string) $row['last_name'];
            $phone = isset($row['phone']) && $row['phone'] !== ''
                ? PhoneNumber::fromString((string) $row['phone'])
                : null;
            $wpUserId = isset($row['wp_user_id'])
                ? (int) $row['wp_user_id']
                : null;

            return new Customer($id, $email, $firstName, $lastName, $phone, $wpUserId);
        } catch (\Throwable $e) {
            throw new PersistenceException(
                sprintf('Failed to hydrate Customer from row ID "%s": %s', (string) $row['customer_id'], $e->getMessage()),
                0,
                $e
            );
        }
    }
}
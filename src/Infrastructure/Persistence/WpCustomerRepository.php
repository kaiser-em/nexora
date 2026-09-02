<?php

declare(strict_types=1);

namespace Silao\Infrastructure\Persistence;

use Silao\Domain\Customer\Customer;
use Silao\Domain\Customer\Repository\CustomerRepositoryInterface;
use Silao\Domain\Customer\ValueObject\CustomerId;
use Silao\Domain\Customer\ValueObject\Email;
use Silao\Infrastructure\Database\TableNames;
use Silao\Infrastructure\Exception\PersistenceException;
use Silao\Infrastructure\Mapper\CustomerMapper;

final class WpCustomerRepository implements CustomerRepositoryInterface
{
    private string $table;

    /**
     * @param object $wpdb WordPress database abstraction
     */
    public function __construct(
        private readonly object $wpdb,
        string $prefix
    ) {
        $this->table = TableNames::customers($prefix);
    }

    public function save(Customer $customer): void
    {
        $data = CustomerMapper::toDatabase($customer);
        $data['created_at_utc'] = $data['updated_at_utc'];

        $sql = $this->wpdb->prepare(
            "INSERT INTO {$this->table} (customer_id, wp_user_id, email, first_name, last_name, phone, created_at_utc, updated_at_utc)
             VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
             ON DUPLICATE KEY UPDATE
             wp_user_id = VALUES(wp_user_id),
             email = VALUES(email),
             first_name = VALUES(first_name),
             last_name = VALUES(last_name),
             phone = VALUES(phone),
             updated_at_utc = VALUES(updated_at_utc)",
            $data['customer_id'],
            $data['wp_user_id'],
            $data['email'],
            $data['first_name'],
            $data['last_name'],
            $data['phone'],
            $data['created_at_utc'],
            $data['updated_at_utc']
        );

        $result = $this->wpdb->query($sql);
        if ($result === false) {
            throw new PersistenceException(
                sprintf('Failed to save customer "%s": %s', $customer->id()->toString(), $this->wpdb->last_error ?? 'Unknown SQL error')
            );
        }
    }

    public function findById(CustomerId $id): ?Customer
    {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE customer_id = %s LIMIT 1",
            $id->toString()
        );

        $row = $this->wpdb->get_row($sql, ARRAY_A);
        if ($row === null || !is_array($row)) {
            return null;
        }

        return CustomerMapper::toDomain($row);
    }

    public function findByEmail(Email $email): ?Customer
    {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE email = %s LIMIT 1",
            $email->toString()
        );

        $row = $this->wpdb->get_row($sql, ARRAY_A);
        if ($row === null || !is_array($row)) {
            return null;
        }

        return CustomerMapper::toDomain($row);
    }

    public function findByWpUserId(int $wpUserId): ?Customer
    {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE wp_user_id = %d LIMIT 1",
            $wpUserId
        );

        $row = $this->wpdb->get_row($sql, ARRAY_A);
        if ($row === null || !is_array($row)) {
            return null;
        }

        return CustomerMapper::toDomain($row);
    }
}
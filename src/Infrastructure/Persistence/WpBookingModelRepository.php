<?php

declare(strict_types=1);

namespace Silao\Infrastructure\Persistence;

use Silao\Domain\Model\BookingModel;
use Silao\Domain\Model\Repository\BookingModelRepositoryInterface;
use Silao\Domain\Model\ValueObject\BookingModelId;
use Silao\Infrastructure\Database\TableNames;
use Silao\Infrastructure\Exception\PersistenceException;
use Silao\Infrastructure\Mapper\BookingModelMapper;

final class WpBookingModelRepository implements BookingModelRepositoryInterface
{
    private string $table;

    /**
     * @param object $wpdb
     */
    public function __construct(
        private readonly object $wpdb,
        string $prefix
    ) {
        $this->table = TableNames::models($prefix);
    }

    public function save(BookingModel $model): void
    {
        $data = BookingModelMapper::toDatabase($model);
        $data['created_at_utc'] = $data['updated_at_utc'];

        $sql = $this->wpdb->prepare(
            "INSERT INTO {$this->table} (model_id, slug, name, description, status, base_price_amount, base_price_currency, resource_strategy, eligible_resources_json, fields_json, options_json, pricing_rules_json, created_at_utc, updated_at_utc)
             VALUES (%s, %s, %s, %s, %s, %d, %s, %s, %s, %s, %s, %s, %s, %s)
             ON DUPLICATE KEY UPDATE
             slug = VALUES(slug),
             name = VALUES(name),
             description = VALUES(description),
             status = VALUES(status),
             base_price_amount = VALUES(base_price_amount),
             base_price_currency = VALUES(base_price_currency),
             resource_strategy = VALUES(resource_strategy),
             eligible_resources_json = VALUES(eligible_resources_json),
             fields_json = VALUES(fields_json),
             options_json = VALUES(options_json),
             pricing_rules_json = VALUES(pricing_rules_json),
             updated_at_utc = VALUES(updated_at_utc)",
            $data['model_id'],
            $data['slug'],
            $data['name'],
            $data['description'],
            $data['status'],
            $data['base_price_amount'],
            $data['base_price_currency'],
            $data['resource_strategy'],
            $data['eligible_resources_json'],
            $data['fields_json'],
            $data['options_json'],
            $data['pricing_rules_json'],
            $data['created_at_utc'],
            $data['updated_at_utc']
        );

        $result = $this->wpdb->query($sql);
        if ($result === false) {
            throw new PersistenceException(
                sprintf('Failed to save booking model "%s": %s', $model->id()->toString(), $this->wpdb->last_error ?? 'Unknown SQL error')
            );
        }
    }

    public function findById(BookingModelId $id): ?BookingModel
    {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE model_id = %s LIMIT 1",
            $id->toString()
        );

        $row = $this->wpdb->get_row($sql, ARRAY_A);
        if ($row === null || !is_array($row)) {
            return null;
        }

        return BookingModelMapper::toDomain($row);
    }

    public function findBySlug(string $slug): ?BookingModel
    {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE slug = %s LIMIT 1",
            $slug
        );

        $row = $this->wpdb->get_row($sql, ARRAY_A);
        if ($row === null || !is_array($row)) {
            return null;
        }

        return BookingModelMapper::toDomain($row);
    }

    /**
     * @return array<BookingModel>
     */
    public function findAllPublished(): array
    {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE status = %s ORDER BY name ASC",
            'published'
        );

        $rows = $this->wpdb->get_results($sql, ARRAY_A);
        if (!is_array($rows)) {
            return [];
        }

        $models = [];
        foreach ($rows as $row) {
            if (is_array($row)) {
                $models[] = BookingModelMapper::toDomain($row);
            }
        }

        return $models;
    }
}
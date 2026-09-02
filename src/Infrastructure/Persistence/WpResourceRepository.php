<?php

declare(strict_types=1);

namespace Silao\Infrastructure\Persistence;

use Silao\Domain\Resource\Repository\ResourceRepositoryInterface;
use Silao\Domain\Resource\Resource;
use Silao\Domain\Resource\ValueObject\ResourceId;
use Silao\Infrastructure\Database\TableNames;
use Silao\Infrastructure\Exception\PersistenceException;
use Silao\Infrastructure\Mapper\ResourceMapper;

final class WpResourceRepository implements ResourceRepositoryInterface
{
    private string $table;

    /**
     * @param object $wpdb
     */
    public function __construct(
        private readonly object $wpdb,
        string $prefix
    ) {
        $this->table = TableNames::resources($prefix);
    }

    public function save(Resource $resource): void
    {
        $data = ResourceMapper::toDatabase($resource);
        $data['created_at_utc'] = $data['updated_at_utc'];

        $sql = $this->wpdb->prepare(
            "INSERT INTO {$this->table} (resource_id, name, capacity, status, schedules_json, blackouts_json, metadata_json, created_at_utc, updated_at_utc)
             VALUES (%s, %s, %d, %s, %s, %s, %s, %s, %s)
             ON DUPLICATE KEY UPDATE
             name = VALUES(name),
             capacity = VALUES(capacity),
             status = VALUES(status),
             schedules_json = VALUES(schedules_json),
             blackouts_json = VALUES(blackouts_json),
             metadata_json = VALUES(metadata_json),
             updated_at_utc = VALUES(updated_at_utc)",
            $data['resource_id'],
            $data['name'],
            $data['capacity'],
            $data['status'],
            $data['schedules_json'],
            $data['blackouts_json'],
            $data['metadata_json'],
            $data['created_at_utc'],
            $data['updated_at_utc']
        );

        $result = $this->wpdb->query($sql);
        if ($result === false) {
            throw new PersistenceException(
                sprintf('Failed to save resource "%s": %s', $resource->id()->toString(), $this->wpdb->last_error ?? 'Unknown SQL error')
            );
        }
    }

    public function findById(ResourceId $id): ?Resource
    {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE resource_id = %s LIMIT 1",
            $id->toString()
        );

        $row = $this->wpdb->get_row($sql, ARRAY_A);
        if ($row === null || !is_array($row)) {
            return null;
        }

        return ResourceMapper::toDomain($row);
    }

    /**
     * @return array<Resource>
     */
    public function findAllActive(): array
    {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE status = %s ORDER BY name ASC",
            'active'
        );

        $rows = $this->wpdb->get_results($sql, ARRAY_A);
        if (!is_array($rows)) {
            return [];
        }

        $resources = [];
        foreach ($rows as $row) {
            if (is_array($row)) {
                $resources[] = ResourceMapper::toDomain($row);
            }
        }

        return $resources;
    }
}
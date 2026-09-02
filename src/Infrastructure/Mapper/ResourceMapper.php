<?php

declare(strict_types=1);

namespace Silao\Infrastructure\Mapper;

use DateTimeImmutable;
use DateTimeZone;
use Silao\Domain\Common\ValueObject\BlackoutPeriod;
use Silao\Domain\Common\ValueObject\TimeOfDay;
use Silao\Domain\Common\ValueObject\ZonedDateTimeRange;
use Silao\Domain\Resource\Enum\ResourceStatus;
use Silao\Domain\Resource\Resource;
use Silao\Domain\Resource\ValueObject\Capacity;
use Silao\Domain\Resource\ValueObject\ResourceId;
use Silao\Domain\Resource\ValueObject\Schedule;
use Silao\Infrastructure\Exception\PersistenceException;

final class ResourceMapper
{
    /**
     * @return array<string, mixed>
     */
    public static function toDatabase(Resource $resource, ?DateTimeImmutable $now = null): array
    {
        $utcNow = ($now ?? new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');

        $schedulesData = [];
        foreach ($resource->schedules() as $schedule) {
            $schedulesData[] = [
                'day_of_week' => $schedule->dayOfWeek,
                'start_time' => $schedule->startTime->format('H:i:s'),
                'end_time' => $schedule->endTime->format('H:i:s'),
            ];
        }

        $blackoutsData = [];
        foreach ($resource->blackoutPeriods() as $blackout) {
            $blackoutsData[] = [
                'starts_at_utc' => $blackout->range->startsAtUtc->format('Y-m-d H:i:s'),
                'ends_at_utc' => $blackout->range->endsAtUtc->format('Y-m-d H:i:s'),
                'timezone' => $blackout->range->timezone->getName(),
                'reason' => $blackout->reason,
            ];
        }

        return [
            'resource_id' => $resource->id()->toString(),
            'name' => $resource->name(),
            'capacity' => $resource->capacity()->toInt(),
            'status' => $resource->status()->value,
            'schedules_json' => json_encode($schedulesData, JSON_THROW_ON_ERROR),
            'blackouts_json' => json_encode($blackoutsData, JSON_THROW_ON_ERROR),
            'metadata_json' => json_encode($resource->metadata(), JSON_THROW_ON_ERROR),
            'updated_at_utc' => $utcNow,
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @throws PersistenceException
     */
    public static function toDomain(array $row): Resource
    {
        $requiredKeys = ['resource_id', 'name', 'capacity', 'status'];
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $row)) {
                throw new PersistenceException(sprintf('Corrupted resource row: missing column "%s".', $key));
            }
        }

        try {
            $id = ResourceId::fromString((string) $row['resource_id']);
            $name = (string) $row['name'];
            $capacity = Capacity::of((int) $row['capacity']);
            $status = ResourceStatus::tryFrom((string) $row['status']) ?? ResourceStatus::Active;

            $schedules = [];
            if (isset($row['schedules_json']) && is_string($row['schedules_json']) && $row['schedules_json'] !== '') {
                $rawSchedules = json_decode($row['schedules_json'], true, 512, JSON_THROW_ON_ERROR);
                if (is_array($rawSchedules)) {
                    foreach ($rawSchedules as $s) {
                        $schedules[] = new Schedule(
                            (int) $s['day_of_week'],
                            TimeOfDay::fromString((string) $s['start_time']),
                            TimeOfDay::fromString((string) $s['end_time'])
                        );
                    }
                }
            }

            $blackouts = [];
            if (isset($row['blackouts_json']) && is_string($row['blackouts_json']) && $row['blackouts_json'] !== '') {
                $rawBlackouts = json_decode($row['blackouts_json'], true, 512, JSON_THROW_ON_ERROR);
                if (is_array($rawBlackouts)) {
                    foreach ($rawBlackouts as $b) {
                        $range = ZonedDateTimeRange::fromIsoStrings(
                            (string) $b['starts_at_utc'],
                            (string) $b['ends_at_utc'],
                            (string) ($b['timezone'] ?? 'UTC')
                        );
                        $blackouts[] = new BlackoutPeriod($range, (string) ($b['reason'] ?? ''));
                    }
                }
            }

            $metadata = [];
            if (isset($row['metadata_json']) && is_string($row['metadata_json']) && $row['metadata_json'] !== '') {
                $rawMeta = json_decode($row['metadata_json'], true, 512, JSON_THROW_ON_ERROR);
                if (is_array($rawMeta)) {
                    $metadata = $rawMeta;
                }
            }

            return new Resource($id, $name, $capacity, $status, $schedules, $blackouts, $metadata);
        } catch (\Throwable $e) {
            throw new PersistenceException(
                sprintf('Failed to hydrate Resource from row ID "%s": %s', $row['resource_id'] ?? 'unknown', $e->getMessage()),
                0,
                $e
            );
        }
    }
}
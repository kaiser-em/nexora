<?php

declare(strict_types=1);

namespace Silao\Application\DTO;

final readonly class ResourceDTO
{
    /**
     * @param array<array{day_of_week: int, start_time: string, end_time: string}> $schedules
     * @param array<array{starts_at_utc: string, ends_at_utc: string, timezone: string, reason: string}> $blackouts
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $resourceId,
        public string $name,
        public int $capacity,
        public string $status,
        public array $schedules = [],
        public array $blackouts = [],
        public array $metadata = []
    ) {
    }
}
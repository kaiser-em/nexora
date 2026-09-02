<?php

declare(strict_types=1);

namespace Silao\Application\DTO;

final readonly class AvailabilityResultDTO
{
    public function __construct(
        public bool $isAvailable,
        public ?string $reasonCode,
        public int $remainingCapacity
    ) {
    }
}
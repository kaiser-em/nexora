<?php

declare(strict_types=1);

namespace Silao\Application\Command;

final readonly class CheckAvailabilityCommand
{
    public function __construct(
        public string $modelId,
        public ?string $resourceId,
        public string $startsAtIso,
        public string $endsAtIso,
        public string $timezone,
        public int $requestedCapacity = 1
    ) {
    }
}
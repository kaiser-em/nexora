<?php

declare(strict_types=1);

namespace Silao\Application\Command;

final readonly class UpdateResourceStatusCommand
{
    public function __construct(
        public string $resourceId,
        public string $status
    ) {
    }
}
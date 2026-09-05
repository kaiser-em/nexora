<?php

declare(strict_types=1);

namespace Silao\Application\DTO;

final readonly class InstallBlueprintResultDTO
{
    public function __construct(
        public string $modelId,
        public string $slug,
        public string $status,
        public string $blueprintId,
        public string $blueprintVersion,
        public int $resourcesCreated
    ) {
    }
}
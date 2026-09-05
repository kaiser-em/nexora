<?php

declare(strict_types=1);

namespace Silao\Application\Command;

final readonly class ConfigureBookingModelCommand
{
    /**
     * @param array<string, mixed> $modelData
     * @param array<array<string, mixed>> $fieldsData
     * @param array<array<string, mixed>> $optionsData
     * @param array<array<string, mixed>> $rulesData
     * @param array<array<string, mixed>> $resourcesData
     */
    public function __construct(
        public string $targetSlug,
        public string $targetStatus,
        public array $modelData,
        public array $fieldsData,
        public array $optionsData,
        public array $rulesData,
        public array $resourcesData
    ) {
    }
}
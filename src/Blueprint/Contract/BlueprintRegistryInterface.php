<?php

declare(strict_types=1);

namespace Silao\Blueprint\Contract;

use Silao\Blueprint\ValueObject\BlueprintSummary;

interface BlueprintRegistryInterface
{
    /** @return array<BlueprintSummary> */
    public function all(): array;

    /**
     * @return array<string, mixed>
     */
    public function getRawDefinition(string $id): array;
}
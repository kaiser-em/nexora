<?php

declare(strict_types=1);

namespace Silao\Blueprint\ValueObject;

final readonly class BlueprintSummary
{
    public function __construct(
        public string $id,
        public string $version,
        public string $name,
        public string $description,
        public string $category
    ) {
    }
}
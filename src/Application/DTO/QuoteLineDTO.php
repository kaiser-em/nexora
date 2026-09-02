<?php

declare(strict_types=1);

namespace Silao\Application\DTO;

final readonly class QuoteLineDTO
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $id,
        public string $type,
        public string $description,
        public int $quantity,
        public int $unitPriceMinorUnits,
        public int $totalMinorUnits,
        public string $formattedTotal,
        public array $metadata = []
    ) {
    }
}
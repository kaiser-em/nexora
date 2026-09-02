<?php

declare(strict_types=1);

namespace Silao\Application\DTO;

final readonly class QuoteDTO
{
    /**
     * @param array<QuoteLineDTO> $lines
     */
    public function __construct(
        public string $currency,
        public int $subtotalMinorUnits,
        public int $feesMinorUnits,
        public int $discountsMinorUnits,
        public int $totalMinorUnits,
        public string $formattedTotal,
        public array $lines
    ) {
    }
}
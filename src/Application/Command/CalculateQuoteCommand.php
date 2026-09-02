<?php

declare(strict_types=1);

namespace Silao\Application\Command;

final readonly class CalculateQuoteCommand
{
    /**
     * @param array<array{id: string, quantity: int}> $selectedOptions
     * @param array<string, mixed> $formData
     * @param array<string, mixed>|null $customerContext
     */
    public function __construct(
        public string $modelId,
        public ?string $resourceId,
        public string $startsAtIso,
        public string $endsAtIso,
        public string $timezone,
        public array $selectedOptions = [],
        public array $formData = [],
        public ?array $customerContext = null,
        public ?int $taxRateBips = null
    ) {
    }
}
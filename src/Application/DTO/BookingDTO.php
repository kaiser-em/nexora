<?php

declare(strict_types=1);

namespace Silao\Application\DTO;

final readonly class BookingDTO
{
    /**
     * @param array<string, mixed> $formData
     * @param array<string> $eventTypes
     */
    public function __construct(
        public string $bookingId,
        public string $reference,
        public string $status,
        public string $modelId,
        public ?string $resourceId,
        public string $startsAtUtc,
        public string $endsAtUtc,
        public string $timezone,
        public CustomerDTO $customer,
        public array $formData,
        public ?QuoteDTO $quote,
        public array $eventTypes
    ) {
    }
}
<?php

declare(strict_types=1);

namespace Silao\Application\Event;

final readonly class BookingCreatedEvent
{
    public function __construct(
        public string $bookingId,
        public string $reference,
        public string $modelId,
        public string $customerEmail
    ) {
    }
}
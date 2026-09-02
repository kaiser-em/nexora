<?php

declare(strict_types=1);

namespace Silao\Application\Event;

final readonly class BookingCompletedEvent
{
    public function __construct(
        public string $bookingId,
        public string $reference
    ) {
    }
}
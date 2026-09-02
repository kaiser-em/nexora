<?php

declare(strict_types=1);

namespace Silao\Application\Event;

final readonly class BookingCancelledEvent
{
    public function __construct(
        public string $bookingId,
        public string $reference
    ) {
    }
}
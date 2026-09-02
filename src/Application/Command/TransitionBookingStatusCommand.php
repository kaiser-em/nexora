<?php

declare(strict_types=1);

namespace Silao\Application\Command;

final readonly class TransitionBookingStatusCommand
{
    public function __construct(
        public string $bookingId,
        public string $action // 'confirm', 'cancel', 'complete', 'mark_pending'
    ) {
    }
}
<?php

declare(strict_types=1);

namespace Silao\Infrastructure\Event;

use Silao\Application\Event\BookingCancelledEvent;
use Silao\Application\Event\BookingCompletedEvent;
use Silao\Application\Event\BookingConfirmedEvent;
use Silao\Application\Event\BookingCreatedEvent;
use Silao\Application\Event\EventDispatcherInterface;

final readonly class WpHookEventDispatcher implements EventDispatcherInterface
{
    /**
     * @param callable(non-empty-string, mixed...): void $actionEmitter
     */
    public function __construct(
        private mixed $actionEmitter = 'do_action'
    ) {
    }

    public function dispatch(object $event): void
    {
        /** @var callable(non-empty-string, mixed...): void $emitter */
        $emitter = $this->actionEmitter;

        if ($event instanceof BookingCreatedEvent) {
            $emitter(
                'silao_booking_created',
                $event->bookingId,
                $event->reference,
                $event->modelId,
                $event->customerEmail
            );
            return;
        }

        if ($event instanceof BookingConfirmedEvent) {
            $emitter(
                'silao_booking_confirmed',
                $event->bookingId,
                $event->reference
            );
            return;
        }

        if ($event instanceof BookingCancelledEvent) {
            $emitter(
                'silao_booking_cancelled',
                $event->bookingId,
                $event->reference
            );
            return;
        }

        if ($event instanceof BookingCompletedEvent) {
            $emitter(
                'silao_booking_completed',
                $event->bookingId,
                $event->reference
            );
        }
    }
}
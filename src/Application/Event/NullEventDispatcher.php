<?php

declare(strict_types=1);

namespace Silao\Application\Event;

final class NullEventDispatcher implements EventDispatcherInterface
{
    /** @var array<object> */
    public array $dispatchedEvents = [];

    public function dispatch(object $event): void
    {
        $this->dispatchedEvents[] = $event;
    }
}
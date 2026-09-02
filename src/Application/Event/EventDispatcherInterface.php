<?php

declare(strict_types=1);

namespace Silao\Application\Event;

interface EventDispatcherInterface
{
    public function dispatch(object $event): void;
}
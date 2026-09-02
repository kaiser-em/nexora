<?php

declare(strict_types=1);

namespace Silao\Infrastructure\Container\Provider;

use Silao\Application\Event\EventDispatcherInterface;
use Silao\Infrastructure\Container\Container;
use Silao\Infrastructure\Container\ServiceProviderInterface;
use Silao\Infrastructure\Event\WpHookEventDispatcher;

final class EventServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $container->singleton(EventDispatcherInterface::class, static function (): EventDispatcherInterface {
            return new WpHookEventDispatcher();
        });
    }
}
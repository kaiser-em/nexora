<?php

declare(strict_types=1);

namespace Silao\Infrastructure\Container\Provider;

use Silao\Blueprint\Contract\BlueprintRegistryInterface;
use Silao\Blueprint\Registry\FileBlueprintRegistry;
use Silao\Infrastructure\Container\Container;
use Silao\Infrastructure\Container\ServiceProviderInterface;

final class BlueprintServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $container->singleton(BlueprintRegistryInterface::class, static function (): BlueprintRegistryInterface {
            return new FileBlueprintRegistry();
        });
    }
}
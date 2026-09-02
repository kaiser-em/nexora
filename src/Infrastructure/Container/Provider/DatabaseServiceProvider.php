<?php

declare(strict_types=1);

namespace Silao\Infrastructure\Container\Provider;

use Silao\Infrastructure\Container\Container;
use Silao\Infrastructure\Container\ServiceProviderInterface;

final class DatabaseServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $container->singleton('wpdb', static function (): object {
            global $wpdb;
            return $wpdb ?? (object) ['prefix' => 'wp_'];
        });
    }
}
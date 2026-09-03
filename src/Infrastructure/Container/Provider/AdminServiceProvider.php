<?php

declare(strict_types=1);

namespace Silao\Infrastructure\Container\Provider;

use Silao\Admin\AdminAssets;
use Silao\Admin\AdminMenu;
use Silao\Infrastructure\Container\Container;
use Silao\Infrastructure\Container\ServiceProviderInterface;

final class AdminServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $container->singleton(AdminMenu::class, static function (): AdminMenu {
            return new AdminMenu();
        });

        $container->singleton(AdminAssets::class, static function (): AdminAssets {
            return new AdminAssets();
        });
    }
}
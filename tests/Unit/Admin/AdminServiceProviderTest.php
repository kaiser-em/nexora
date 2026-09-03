<?php

declare(strict_types=1);

namespace Silao\Tests\Unit\Admin;

use PHPUnit\Framework\TestCase;
use Silao\Admin\AdminAssets;
use Silao\Admin\AdminMenu;
use Silao\Infrastructure\Container\Container;
use Silao\Infrastructure\Container\Provider\AdminServiceProvider;

final class AdminServiceProviderTest extends TestCase
{
    public function testRegistersAdminMenuAndAssets(): void
    {
        $container = new Container();
        $container->register(new AdminServiceProvider());

        $this->assertInstanceOf(AdminMenu::class, $container->get(AdminMenu::class));
        $this->assertInstanceOf(AdminAssets::class, $container->get(AdminAssets::class));
    }
}
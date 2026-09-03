<?php

declare(strict_types=1);

namespace Silao\Tests\Unit\Admin;

use PHPUnit\Framework\TestCase;
use Silao\Admin\AdminMenu;

final class AdminMenuTest extends TestCase
{
    public function testMenuMethodsRenderWithoutException(): void
    {
        $menu = new AdminMenu();

        ob_start();
        $menu->renderDashboardPage();
        $menu->renderBookingModelsPage();
        $menu->renderBookingsPage();
        $menu->renderResourcesPage();
        $menu->renderCustomersPage();
        $output = ob_get_clean();

        $this->assertIsString($output);
        $this->assertStringContainsString('silao-admin-app', $output);
    }
}
<?php

declare(strict_types=1);

namespace Silao\Tests\Unit\Admin;

use PHPUnit\Framework\TestCase;

final class AdminPageRenderingTest extends TestCase
{
    public function testHtmlViewsContainRequiredRootDataViews(): void
    {
        $views = ['dashboard', 'booking-models', 'bookings', 'resources', 'customers'];

        foreach ($views as $view) {
            $path = dirname(__DIR__, 3) . '/src/Admin/View/' . $view . '.php';
            $this->assertFileExists($path);
            $content = (string) file_get_contents($path);
            $this->assertStringContainsString('id="silao-admin-app"', $content);
            $this->assertStringContainsString('data-view="' . $view . '"', $content);
        }
    }
}
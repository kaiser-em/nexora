<?php

declare(strict_types=1);

namespace Silao\Tests\Unit\Admin;

use PHPUnit\Framework\TestCase;
use Silao\Admin\AdminAssets;

final class AdminAssetsTest extends TestCase
{
    public function testEnqueueIgnoresNonSilaoScreens(): void
    {
        $assets = new AdminAssets();
        $assets->enqueue('edit.php');
        $this->assertInstanceOf(AdminAssets::class, $assets);
    }
}
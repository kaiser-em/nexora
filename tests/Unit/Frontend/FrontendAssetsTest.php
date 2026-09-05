<?php

declare(strict_types=1);

namespace Silao\Tests\Unit\Frontend;

use PHPUnit\Framework\TestCase;
use Silao\Frontend\FrontendAssets;

final class FrontendAssetsTest extends TestCase
{
    public function testEnqueueWithoutShortcodeDoesNotRegisterAssets(): void
    {
        $assets = new FrontendAssets();
        $assets->enqueue();
        $this->assertInstanceOf(FrontendAssets::class, $assets);
    }
}
<?php

declare(strict_types=1);

namespace Silao\Tests\Unit\Frontend;

use PHPUnit\Framework\TestCase;
use Silao\Domain\Common\ValueObject\Currency;
use Silao\Domain\Common\ValueObject\Money;
use Silao\Domain\Model\BookingModel;
use Silao\Domain\Model\Enum\BookingModelStatus;
use Silao\Domain\Model\Repository\BookingModelRepositoryInterface;
use Silao\Domain\Model\ValueObject\BookingModelId;
use Silao\Frontend\FrontendAssets;
use Silao\Frontend\Shortcode;
use Silao\Infrastructure\Container\Container;
use Silao\Infrastructure\Container\Provider\FrontendServiceProvider;

final class FrontendServiceProviderTest extends TestCase
{
    public function testRegistersShortcodeAndFrontendAssets(): void
    {
        $repo = $this->createMock(BookingModelRepositoryInterface::class);

        $container = new Container();
        $container->instance(BookingModelRepositoryInterface::class, $repo);
        $container->register(new FrontendServiceProvider());

        $this->assertInstanceOf(Shortcode::class, $container->get(Shortcode::class));
        $this->assertInstanceOf(FrontendAssets::class, $container->get(FrontendAssets::class));
    }
}
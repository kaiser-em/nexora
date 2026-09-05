<?php

declare(strict_types=1);

namespace Silao\Infrastructure\Container\Provider;

use Silao\Domain\Model\Repository\BookingModelRepositoryInterface;
use Silao\Frontend\FrontendAssets;
use Silao\Frontend\Shortcode;
use Silao\Infrastructure\Container\Container;
use Silao\Infrastructure\Container\ServiceProviderInterface;

final class FrontendServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $container->singleton(Shortcode::class, static function (Container $c): Shortcode {
            return new Shortcode($c->get(BookingModelRepositoryInterface::class));
        });

        $container->singleton(FrontendAssets::class, static function (): FrontendAssets {
            return new FrontendAssets();
        });
    }
}
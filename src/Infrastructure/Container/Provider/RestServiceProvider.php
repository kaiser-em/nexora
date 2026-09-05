<?php

declare(strict_types=1);

namespace Silao\Infrastructure\Container\Provider;

use Silao\Blueprint\Contract\BlueprintRegistryInterface;
use Silao\Infrastructure\Container\Container;
use Silao\Infrastructure\Container\ServiceProviderInterface;
use Silao\REST\Controller\AvailabilityController;
use Silao\REST\Controller\BlueprintController;
use Silao\REST\Controller\BookingController;
use Silao\REST\Controller\BookingModelController;
use Silao\REST\Controller\CustomerController;
use Silao\REST\Controller\QuoteController;
use Silao\REST\Controller\ResourceController;
use Silao\REST\RestServer;

final class RestServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $container->singleton(QuoteController::class, static fn (Container $c) => new QuoteController($c));
        $container->singleton(AvailabilityController::class, static fn (Container $c) => new AvailabilityController($c));
        $container->singleton(BookingController::class, static fn (Container $c) => new BookingController($c));
        $container->singleton(BookingModelController::class, static fn (Container $c) => new BookingModelController($c));
        $container->singleton(ResourceController::class, static fn (Container $c) => new ResourceController($c));
        $container->singleton(CustomerController::class, static fn (Container $c) => new CustomerController($c));
        $container->singleton(BlueprintController::class, static fn (Container $c) => new BlueprintController($c));
        $container->singleton(RestServer::class, static fn (Container $c) => new RestServer($c));
    }
}
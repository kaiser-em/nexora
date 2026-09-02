<?php

declare(strict_types=1);

namespace Silao\Infrastructure\Container\Provider;

use Silao\Infrastructure\Container\Container;
use Silao\Infrastructure\Container\ServiceProviderInterface;
use Silao\REST\Controller\AvailabilityController;
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
        $container->singleton(QuoteController::class, static function (Container $c): QuoteController {
            return new QuoteController($c);
        });

        $container->singleton(AvailabilityController::class, static function (Container $c): AvailabilityController {
            return new AvailabilityController($c);
        });

        $container->singleton(BookingController::class, static function (Container $c): BookingController {
            return new BookingController($c);
        });

        $container->singleton(BookingModelController::class, static function (Container $c): BookingModelController {
            return new BookingModelController($c);
        });

        $container->singleton(ResourceController::class, static function (Container $c): ResourceController {
            return new ResourceController($c);
        });

        $container->singleton(CustomerController::class, static function (Container $c): CustomerController {
            return new CustomerController($c);
        });

        $container->singleton(RestServer::class, static function (Container $c): RestServer {
            return new RestServer($c);
        });
    }
}
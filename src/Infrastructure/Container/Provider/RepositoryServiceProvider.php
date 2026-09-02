<?php

declare(strict_types=1);

namespace Silao\Infrastructure\Container\Provider;

use Silao\Domain\Booking\Repository\BookingRepositoryInterface;
use Silao\Domain\Customer\Repository\CustomerRepositoryInterface;
use Silao\Domain\Model\Repository\BookingModelRepositoryInterface;
use Silao\Domain\Resource\Repository\ResourceRepositoryInterface;
use Silao\Infrastructure\Container\Container;
use Silao\Infrastructure\Container\ServiceProviderInterface;
use Silao\Infrastructure\Persistence\WpBookingModelRepository;
use Silao\Infrastructure\Persistence\WpBookingRepository;
use Silao\Infrastructure\Persistence\WpCustomerRepository;
use Silao\Infrastructure\Persistence\WpResourceRepository;

final class RepositoryServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $container->singleton(BookingRepositoryInterface::class, static function (Container $c): BookingRepositoryInterface {
            $wpdb = $c->get("wpdb");
            return new WpBookingRepository($wpdb, (string) $wpdb->prefix);
        });

        $container->singleton(CustomerRepositoryInterface::class, static function (Container $c): CustomerRepositoryInterface {
            $wpdb = $c->get("wpdb");
            return new WpCustomerRepository($wpdb, (string) $wpdb->prefix);
        });

        $container->singleton(ResourceRepositoryInterface::class, static function (Container $c): ResourceRepositoryInterface {
            $wpdb = $c->get("wpdb");
            return new WpResourceRepository($wpdb, (string) $wpdb->prefix);
        });

        $container->singleton(BookingModelRepositoryInterface::class, static function (Container $c): BookingModelRepositoryInterface {
            $wpdb = $c->get("wpdb");
            return new WpBookingModelRepository($wpdb, (string) $wpdb->prefix);
        });
    }
}

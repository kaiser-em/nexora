<?php

declare(strict_types=1);

namespace Silao\Infrastructure\Container\Provider;

use Silao\Application\Event\EventDispatcherInterface;
use Silao\Application\Service\CalculateQuoteService;
use Silao\Application\Service\CheckAvailabilityService;
use Silao\Application\Service\CreateBookingService;
use Silao\Application\Service\TransitionBookingStatusService;
use Silao\Application\Transaction\TransactionManagerInterface;
use Silao\Domain\Booking\Repository\BookingRepositoryInterface;
use Silao\Domain\Customer\Repository\CustomerRepositoryInterface;
use Silao\Domain\Model\Repository\BookingModelRepositoryInterface;
use Silao\Domain\Resource\Repository\ResourceRepositoryInterface;
use Silao\Infrastructure\Container\Container;
use Silao\Infrastructure\Container\ServiceProviderInterface;

final class ApplicationServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $container->singleton(CalculateQuoteService::class, static function (Container $c): CalculateQuoteService {
            return new CalculateQuoteService($c->get(BookingModelRepositoryInterface::class));
        });

        $container->singleton(CheckAvailabilityService::class, static function (Container $c): CheckAvailabilityService {
            return new CheckAvailabilityService(
                $c->get(BookingModelRepositoryInterface::class),
                $c->get(ResourceRepositoryInterface::class),
                $c->get(BookingRepositoryInterface::class)
            );
        });

        $container->singleton(CreateBookingService::class, static function (Container $c): CreateBookingService {
            return new CreateBookingService(
                $c->get(BookingModelRepositoryInterface::class),
                $c->get(ResourceRepositoryInterface::class),
                $c->get(CustomerRepositoryInterface::class),
                $c->get(BookingRepositoryInterface::class),
                $c->get(TransactionManagerInterface::class),
                $c->get(EventDispatcherInterface::class)
            );
        });

        $container->singleton(TransitionBookingStatusService::class, static function (Container $c): TransitionBookingStatusService {
            return new TransitionBookingStatusService(
                $c->get(BookingRepositoryInterface::class),
                $c->get(TransactionManagerInterface::class),
                $c->get(EventDispatcherInterface::class)
            );
        });
    }
}
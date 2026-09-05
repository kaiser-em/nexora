<?php

declare(strict_types=1);

namespace Silao\Infrastructure\Container\Provider;

use Silao\Application\Blueprint\InstallBlueprintService;
use Silao\Application\Event\EventDispatcherInterface;
use Silao\Application\Service\CalculateQuoteService;
use Silao\Application\Service\CheckAvailabilityService;
use Silao\Application\Service\CreateBookingService;
use Silao\Application\Service\ReplaceResourceService;
use Silao\Application\Service\TransitionBookingStatusService;
use Silao\Application\Service\UpdateResourceStatusService;
use Silao\Application\Transaction\TransactionManagerInterface;
use Silao\Blueprint\Contract\BlueprintRegistryInterface;
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
        $container->singleton(CalculateQuoteService::class, static fn(Container $c) => new CalculateQuoteService($c->get(BookingModelRepositoryInterface::class)));
        $container->singleton(CheckAvailabilityService::class, static fn(Container $c) => new CheckAvailabilityService($c->get(BookingModelRepositoryInterface::class), $c->get(ResourceRepositoryInterface::class), $c->get(BookingRepositoryInterface::class)));
        $container->singleton(CreateBookingService::class, static fn(Container $c) => new CreateBookingService($c->get(BookingModelRepositoryInterface::class), $c->get(ResourceRepositoryInterface::class), $c->get(CustomerRepositoryInterface::class), $c->get(BookingRepositoryInterface::class), $c->get(TransactionManagerInterface::class), $c->get(EventDispatcherInterface::class)));
        $container->singleton(TransitionBookingStatusService::class, static fn(Container $c) => new TransitionBookingStatusService($c->get(BookingRepositoryInterface::class), $c->get(TransactionManagerInterface::class), $c->get(EventDispatcherInterface::class)));
        $container->singleton(ReplaceResourceService::class, static fn(Container $c) => new ReplaceResourceService($c->get(ResourceRepositoryInterface::class), $c->get(TransactionManagerInterface::class)));
        $container->singleton(UpdateResourceStatusService::class, static fn(Container $c) => new UpdateResourceStatusService($c->get(ResourceRepositoryInterface::class), $c->get(TransactionManagerInterface::class)));
        
        $container->singleton(InstallBlueprintService::class, static fn(Container $c) => new InstallBlueprintService(
            $c->get(BlueprintRegistryInterface::class),
            $c->get(BookingModelRepositoryInterface::class),
            $c->get(ResourceRepositoryInterface::class),
            $c->get(TransactionManagerInterface::class)
        ));
    }
}
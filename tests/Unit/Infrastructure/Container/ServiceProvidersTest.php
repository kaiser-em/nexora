<?php

declare(strict_types=1);

namespace Silao\Tests\Unit\Infrastructure\Container;

use PHPUnit\Framework\TestCase;
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
use Silao\Infrastructure\Container\Provider\ApplicationServiceProvider;
use Silao\Infrastructure\Container\Provider\DatabaseServiceProvider;
use Silao\Infrastructure\Container\Provider\EventServiceProvider;
use Silao\Infrastructure\Container\Provider\RepositoryServiceProvider;
use Silao\Infrastructure\Container\Provider\TransactionServiceProvider;

final class ServiceProvidersTest extends TestCase
{
    public function testContainerResolvesAllWiredServices(): void
    {
        $container = new Container();
        $container->instance('wpdb', (object) ['prefix' => 'wp_']);

        $container->register(new DatabaseServiceProvider());
        $container->register(new RepositoryServiceProvider());
        $container->register(new TransactionServiceProvider());
        $container->register(new EventServiceProvider());
        $container->register(new ApplicationServiceProvider());

        $this->assertInstanceOf(BookingRepositoryInterface::class, $container->get(BookingRepositoryInterface::class));
        $this->assertInstanceOf(CustomerRepositoryInterface::class, $container->get(CustomerRepositoryInterface::class));
        $this->assertInstanceOf(ResourceRepositoryInterface::class, $container->get(ResourceRepositoryInterface::class));
        $this->assertInstanceOf(BookingModelRepositoryInterface::class, $container->get(BookingModelRepositoryInterface::class));
        $this->assertInstanceOf(TransactionManagerInterface::class, $container->get(TransactionManagerInterface::class));
        $this->assertInstanceOf(EventDispatcherInterface::class, $container->get(EventDispatcherInterface::class));
        $this->assertInstanceOf(CalculateQuoteService::class, $container->get(CalculateQuoteService::class));
        $this->assertInstanceOf(CheckAvailabilityService::class, $container->get(CheckAvailabilityService::class));
        $this->assertInstanceOf(CreateBookingService::class, $container->get(CreateBookingService::class));
        $this->assertInstanceOf(TransitionBookingStatusService::class, $container->get(TransitionBookingStatusService::class));
    }
}
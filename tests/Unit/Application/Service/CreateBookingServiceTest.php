<?php

declare(strict_types=1);

namespace Silao\Tests\Unit\Application\Service;

use PHPUnit\Framework\TestCase;
use Silao\Application\Command\CreateBookingCommand;
use Silao\Application\Event\BookingCreatedEvent;
use Silao\Application\Event\NullEventDispatcher;
use Silao\Application\Exception\BookingUnavailableException;
use Silao\Application\Service\CreateBookingService;
use Silao\Application\Transaction\TransactionManagerInterface;
use Silao\Domain\Booking\Repository\BookingRepositoryInterface;
use Silao\Domain\Common\ValueObject\Currency;
use Silao\Domain\Common\ValueObject\Money;
use Silao\Domain\Common\ValueObject\TimeOfDay;
use Silao\Domain\Customer\Repository\CustomerRepositoryInterface;
use Silao\Domain\Model\BookingModel;
use Silao\Domain\Model\Enum\BookingModelStatus;
use Silao\Domain\Model\Enum\ResourceStrategyType;
use Silao\Domain\Model\Repository\BookingModelRepositoryInterface;
use Silao\Domain\Model\ValueObject\BookingModelId;
use Silao\Domain\Resource\Enum\ResourceStatus;
use Silao\Domain\Resource\Repository\ResourceRepositoryInterface;
use Silao\Domain\Resource\Resource;
use Silao\Domain\Resource\ValueObject\Capacity;
use Silao\Domain\Resource\ValueObject\ResourceId;
use Silao\Domain\Resource\ValueObject\Schedule;

final class CreateBookingServiceTest extends TestCase
{
    private Currency $eur;
    private TransactionManagerInterface $txManager;

    protected function setUp(): void
    {
        $this->eur = Currency::EUR();
        $this->txManager = new class implements TransactionManagerInterface {
            public function transactional(callable $operation): mixed
            {
                return $operation();
            }
        };
    }

    public function testNominalBookingCreationAndPostCommitEvent(): void
    {
        $model = new BookingModel(
            BookingModelId::fromString('m1'),
            'transfer-vip',
            'VIP Transfer',
            '',
            Money::of(10000, $this->eur),
            BookingModelStatus::Published,
            ResourceStrategyType::SingleSelect,
            [ResourceId::fromString('van_1')]
        );

        $resource = new Resource(
            ResourceId::fromString('van_1'),
            'Mercedes V-Class',
            Capacity::of(7),
            ResourceStatus::Active,
            [Schedule::of(1, TimeOfDay::of(8, 0), TimeOfDay::of(18, 0))]
        );

        $modelRepo = $this->createMock(BookingModelRepositoryInterface::class);
        $modelRepo->method('findById')->willReturn($model);

        $resourceRepo = $this->createMock(ResourceRepositoryInterface::class);
        $resourceRepo->method('findById')->willReturn($resource);

        $customerRepo = $this->createMock(CustomerRepositoryInterface::class);
        $customerRepo->method('findByEmail')->willReturn(null);

        $bookingRepo = $this->createMock(BookingRepositoryInterface::class);
        $bookingRepo->method('findActiveByResourceAndDateRange')->willReturn([]);
        $bookingRepo->expects($this->once())->method('save');

        $dispatcher = new NullEventDispatcher();

        $service = new CreateBookingService(
            $modelRepo,
            $resourceRepo,
            $customerRepo,
            $bookingRepo,
            $this->txManager,
            $dispatcher
        );

        $command = new CreateBookingCommand(
            'm1',
            'van_1',
            '2026-06-15 10:00:00',
            '2026-06-15 11:30:00',
            'Europe/Paris',
            'John',
            'Doe',
            'john.doe@example.com',
            '+33 6 12 34 56 78',
            null,
            [],
            ['passengers' => 4],
            null,
            null,
            true
        );

        $bookingDTO = $service->execute($command);

        $this->assertSame('confirmed', $bookingDTO->status);
        $this->assertSame('John Doe', $bookingDTO->customer->fullName);
        $this->assertSame(10000, $bookingDTO->quote?->totalMinorUnits);
        $this->assertCount(1, $dispatcher->dispatchedEvents);
        $this->assertInstanceOf(BookingCreatedEvent::class, $dispatcher->dispatchedEvents[0]);
    }

    public function testRejectsUnavailableResourceWithoutEventDispatch(): void
    {
        $model = new BookingModel(
            BookingModelId::fromString('m1'),
            'transfer-vip',
            'VIP Transfer',
            '',
            Money::of(10000, $this->eur),
            BookingModelStatus::Published,
            ResourceStrategyType::SingleSelect,
            [ResourceId::fromString('van_1')]
        );

        $resource = new Resource(
            ResourceId::fromString('van_1'),
            'Mercedes V-Class',
            Capacity::of(7),
            ResourceStatus::Inactive
        );

        $modelRepo = $this->createMock(BookingModelRepositoryInterface::class);
        $modelRepo->method('findById')->willReturn($model);

        $resourceRepo = $this->createMock(ResourceRepositoryInterface::class);
        $resourceRepo->method('findById')->willReturn($resource);

        $customerRepo = $this->createMock(CustomerRepositoryInterface::class);
        $bookingRepo = $this->createMock(BookingRepositoryInterface::class);
        $bookingRepo->expects($this->never())->method('save');

        $dispatcher = new NullEventDispatcher();

        $service = new CreateBookingService(
            $modelRepo,
            $resourceRepo,
            $customerRepo,
            $bookingRepo,
            $this->txManager,
            $dispatcher
        );

        $command = new CreateBookingCommand(
            'm1',
            'van_1',
            '2026-06-15 10:00:00',
            '2026-06-15 11:00:00',
            'UTC',
            'John',
            'Doe',
            'john@example.com'
        );

        $this->expectException(BookingUnavailableException::class);
        $service->execute($command);

        $this->assertCount(0, $dispatcher->dispatchedEvents);
    }
}
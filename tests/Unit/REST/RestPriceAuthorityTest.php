<?php

declare(strict_types=1);

namespace Silao\Tests\Unit\REST;

use PHPUnit\Framework\TestCase;
use Silao\Application\Event\NullEventDispatcher;
use Silao\Application\Service\CreateBookingService;
use Silao\Application\Transaction\TransactionManagerInterface;
use Silao\Domain\Booking\Booking;
use Silao\Domain\Booking\Repository\BookingRepositoryInterface;
use Silao\Domain\Common\ValueObject\Currency;
use Silao\Domain\Common\ValueObject\Money;
use Silao\Domain\Customer\Customer;
use Silao\Domain\Customer\Repository\CustomerRepositoryInterface;
use Silao\Domain\Customer\ValueObject\CustomerId;
use Silao\Domain\Customer\ValueObject\Email;
use Silao\Domain\Model\BookingModel;
use Silao\Domain\Model\Enum\BookingModelStatus;
use Silao\Domain\Model\Enum\ResourceStrategyType;
use Silao\Domain\Model\Repository\BookingModelRepositoryInterface;
use Silao\Domain\Model\ValueObject\BookingModelId;
use Silao\Domain\Resource\Repository\ResourceRepositoryInterface;
use Silao\Infrastructure\Container\Container;
use Silao\REST\Controller\BookingController;
use WP_REST_Request;
use WP_REST_Response;

final class RestPriceAuthorityTest extends TestCase
{
    public function testBookingControllerIgnoresClientTamperedTotalAndCalculatesOnServer(): void
    {
        $eur = Currency::EUR();
        $model = new BookingModel(
            BookingModelId::fromString('m1'),
            'transfer-service',
            'Transfer Service',
            '',
            Money::of(10000, $eur),
            BookingModelStatus::Published,
            ResourceStrategyType::None
        );

        $modelRepo = $this->createMock(BookingModelRepositoryInterface::class);
        $modelRepo->method('findById')->willReturn($model);

        $resRepo = $this->createMock(ResourceRepositoryInterface::class);

        $custRepo = $this->createMock(CustomerRepositoryInterface::class);
        $custRepo->method('findByEmail')->willReturn(
            new Customer(CustomerId::fromString('c1'), Email::fromString('client@example.com'), 'A', 'B')
        );

        $savedBooking = null;
        $bookingRepo = $this->createMock(BookingRepositoryInterface::class);
        $bookingRepo->method('save')->willReturnCallback(function (Booking $b) use (&$savedBooking): void {
            $savedBooking = $b;
        });

        $txManager = new class implements TransactionManagerInterface {
            public function transactional(callable $op): mixed
            {
                return $op();
            }
        };

        $createBookingService = new CreateBookingService(
            $modelRepo,
            $resRepo,
            $custRepo,
            $bookingRepo,
            $txManager,
            new NullEventDispatcher()
        );

        $container = new Container();
        $container->instance(CreateBookingService::class, $createBookingService);

        $controller = new BookingController($container);

        // Fraudulent client request sending "total": 100 (1.00 EUR)
        $request = new WP_REST_Request('POST', '/silao/v1/bookings');
        $request->set_body_params([
            'model_id' => 'm1',
            'starts_at' => '2026-06-15T10:00:00Z',
            'ends_at' => '2026-06-15T12:00:00Z',
            'customer' => ['first_name' => 'A', 'last_name' => 'B', 'email' => 'client@example.com'],
            'total' => 100, // FRAUDULENT CLIENT PRICE IGNORED
            'currency' => 'EUR',
        ]);

        $response = $controller->create($request);
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertSame(201, $response->get_status());

        $data = $response->get_data();
        $this->assertIsArray($data);
        $this->assertSame(10000, $data['data']['quote']['total']); // Server authoritative price: 100.00 EUR
        $this->assertSame(10000, $savedBooking?->priceSnapshot()?->total->amount);
    }
}
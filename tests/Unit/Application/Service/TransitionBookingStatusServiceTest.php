<?php

declare(strict_types=1);

namespace Silao\Tests\Unit\Application\Service;

use PHPUnit\Framework\TestCase;
use Silao\Application\Command\TransitionBookingStatusCommand;
use Silao\Application\Event\BookingCancelledEvent;
use Silao\Application\Event\BookingConfirmedEvent;
use Silao\Application\Event\NullEventDispatcher;
use Silao\Application\Service\TransitionBookingStatusService;
use Silao\Application\Transaction\TransactionManagerInterface;
use Silao\Domain\Booking\Booking;
use Silao\Domain\Booking\Enum\QuoteLineType;
use Silao\Domain\Booking\Repository\BookingRepositoryInterface;
use Silao\Domain\Booking\ValueObject\BookingId;
use Silao\Domain\Booking\ValueObject\BookingReference;
use Silao\Domain\Booking\ValueObject\FormDataSnapshot;
use Silao\Domain\Booking\ValueObject\Quote;
use Silao\Domain\Booking\ValueObject\QuoteLine;
use Silao\Domain\Common\ValueObject\Currency;
use Silao\Domain\Common\ValueObject\Money;
use Silao\Domain\Common\ValueObject\ZonedDateTimeRange;
use Silao\Domain\Customer\ValueObject\CustomerId;
use Silao\Domain\Customer\ValueObject\CustomerSnapshot;
use Silao\Domain\Customer\ValueObject\Email;
use Silao\Domain\Model\ValueObject\BookingModelId;

final class TransitionBookingStatusServiceTest extends TestCase
{
    private TransactionManagerInterface $txManager;

    protected function setUp(): void
    {
        $this->txManager = new class implements TransactionManagerInterface {
            public function transactional(callable $operation): mixed
            {
                return $operation();
            }
        };
    }

    public function testConfirmAndCancelTransitions(): void
    {
        $eur = Currency::EUR();
        $range = ZonedDateTimeRange::fromIsoStrings('2026-06-15 10:00:00', '2026-06-15 12:00:00', 'UTC');
        $cSnap = new CustomerSnapshot(CustomerId::fromString('c1'), Email::fromString('a@b.com'), 'A', 'B');

        $booking = new Booking(
            BookingId::fromString('b1'),
            BookingReference::fromString('SIL-01'),
            $cSnap,
            BookingModelId::fromString('m1'),
            null,
            $range,
            new FormDataSnapshot([])
        );

        $line = new QuoteLine('l1', QuoteLineType::BasePrice, 'Base', 1, Money::of(5000, $eur), Money::of(5000, $eur));
        $quote = new Quote([$line], Money::of(5000, $eur), Money::of(0, $eur), Money::of(0, $eur), Money::of(5000, $eur), $eur);
        $booking->createQuote($quote);

        $repo = $this->createMock(BookingRepositoryInterface::class);
        $repo->method('findById')->willReturn($booking);
        $repo->expects($this->exactly(2))->method('save');

        $dispatcher = new NullEventDispatcher();
        $service = new TransitionBookingStatusService($repo, $this->txManager, $dispatcher);

        $resDto = $service->execute(new TransitionBookingStatusCommand('b1', 'confirm'));
        $this->assertSame('confirmed', $resDto->status);
        $this->assertCount(1, $dispatcher->dispatchedEvents);
        $this->assertInstanceOf(BookingConfirmedEvent::class, $dispatcher->dispatchedEvents[0]);

        $resDto2 = $service->execute(new TransitionBookingStatusCommand('b1', 'cancel'));
        $this->assertSame('cancelled', $resDto2->status);
        $this->assertCount(2, $dispatcher->dispatchedEvents);
        $this->assertInstanceOf(BookingCancelledEvent::class, $dispatcher->dispatchedEvents[1]);
    }
}
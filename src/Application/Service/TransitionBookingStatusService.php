<?php

declare(strict_types=1);

namespace Silao\Application\Service;

use Silao\Application\Command\TransitionBookingStatusCommand;
use Silao\Application\DTO\BookingDTO;
use Silao\Application\DTO\CustomerDTO;
use Silao\Application\Event\BookingCancelledEvent;
use Silao\Application\Event\BookingCompletedEvent;
use Silao\Application\Event\BookingConfirmedEvent;
use Silao\Application\Event\EventDispatcherInterface;
use Silao\Application\Exception\ApplicationException;
use Silao\Application\Transaction\TransactionManagerInterface;
use Silao\Domain\Booking\Booking;
use Silao\Domain\Booking\Repository\BookingRepositoryInterface;
use Silao\Domain\Booking\ValueObject\BookingId;

final readonly class TransitionBookingStatusService
{
    public function __construct(
        private BookingRepositoryInterface $bookingRepository,
        private TransactionManagerInterface $transactionManager,
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * @throws ApplicationException
     */
    public function execute(TransitionBookingStatusCommand $command): BookingDTO
    {
        /** @var array{booking: Booking, event: ?object} $txResult */
        $txResult = $this->transactionManager->transactional(function () use ($command): array {
            $bookingId = BookingId::fromString($command->bookingId);
            $booking = $this->bookingRepository->findById($bookingId);

            if ($booking === null) {
                throw new ApplicationException(sprintf('Booking "%s" not found.', $command->bookingId));
            }

            $eventToDispatch = null;

            switch ($command->action) {
                case 'confirm':
                    $booking->confirm();
                    $eventToDispatch = new BookingConfirmedEvent($booking->id()->toString(), $booking->reference()->toString());
                    break;
                case 'cancel':
                    $booking->cancel();
                    $eventToDispatch = new BookingCancelledEvent($booking->id()->toString(), $booking->reference()->toString());
                    break;
                case 'complete':
                    $booking->complete();
                    $eventToDispatch = new BookingCompletedEvent($booking->id()->toString(), $booking->reference()->toString());
                    break;
                case 'mark_pending':
                    $booking->markPending();
                    break;
                default:
                    throw new ApplicationException(sprintf('Unknown transition action "%s".', $command->action));
            }

            $this->bookingRepository->save($booking);

            return ['booking' => $booking, 'event' => $eventToDispatch];
        });

        if ($txResult['event'] !== null) {
            $this->eventDispatcher->dispatch($txResult['event']);
        }

        $booking = $txResult['booking'];
        $cSnap = $booking->customerSnapshot();
        $cDto = new CustomerDTO(
            $cSnap->customerId->toString(),
            $cSnap->email->toString(),
            $cSnap->firstName,
            $cSnap->lastName,
            $cSnap->fullName(),
            $cSnap->phone?->toString(),
            $cSnap->isGuest
        );

        $eventTypes = array_map(static fn($e) => $e->type(), $booking->events());

        return new BookingDTO(
            $booking->id()->toString(),
            $booking->reference()->toString(),
            $booking->status()->value,
            $booking->modelId()->toString(),
            $booking->resourceId()?->toString(),
            $booking->dateTimeRange()->startsAtUtc->format('Y-m-d H:i:s'),
            $booking->dateTimeRange()->endsAtUtc->format('Y-m-d H:i:s'),
            $booking->dateTimeRange()->timezone->getName(),
            $cDto,
            $booking->formDataSnapshot()->all(),
            null,
            $eventTypes
        );
    }
}
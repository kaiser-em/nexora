<?php

declare(strict_types=1);

namespace Nexora\Domain\Booking;

use DateTimeImmutable;
use DateTimeZone;
use Nexora\Domain\Booking\Enum\BookingStatus;
use Nexora\Domain\Booking\Event\BookingEvent;
use Nexora\Domain\Booking\Exception\InvalidBookingException;
use Nexora\Domain\Booking\Exception\InvalidPriceSnapshotException;
use Nexora\Domain\Booking\ValueObject\BookingId;
use Nexora\Domain\Booking\ValueObject\BookingReference;
use Nexora\Domain\Booking\ValueObject\FormDataSnapshot;
use Nexora\Domain\Booking\ValueObject\PriceSnapshot;
use Nexora\Domain\Booking\ValueObject\Quote;
use Nexora\Domain\Common\ValueObject\ZonedDateTimeRange;
use Nexora\Domain\Customer\ValueObject\CustomerSnapshot;
use Nexora\Domain\Model\ValueObject\BookingModelId;
use Nexora\Domain\Resource\ValueObject\ResourceId;

final class Booking
{
    private BookingStatus $status;
    private ?Quote $quote = null;
    private ?PriceSnapshot $priceSnapshot = null;
    /** @var array<BookingEvent> */
    private array $events = [];

    public function __construct(
        private readonly BookingId $id,
        private readonly BookingReference $reference,
        private readonly CustomerSnapshot $customerSnapshot,
        private readonly BookingModelId $modelId,
        private readonly ?ResourceId $resourceId,
        private readonly ZonedDateTimeRange $dateTimeRange,
        private readonly FormDataSnapshot $formDataSnapshot,
        BookingStatus $status = BookingStatus::Draft
    ) {
        $this->status = $status;
        $this->recordEvent('booking.created');
    }

    public function id(): BookingId
    {
        return $this->id;
    }

    public function reference(): BookingReference
    {
        return $this->reference;
    }

    public function customerSnapshot(): CustomerSnapshot
    {
        return $this->customerSnapshot;
    }

    public function modelId(): BookingModelId
    {
        return $this->modelId;
    }

    public function resourceId(): ?ResourceId
    {
        return $this->resourceId;
    }

    public function dateTimeRange(): ZonedDateTimeRange
    {
        return $this->dateTimeRange;
    }

    public function formDataSnapshot(): FormDataSnapshot
    {
        return $this->formDataSnapshot;
    }

    public function status(): BookingStatus
    {
        return $this->status;
    }

    public function quote(): ?Quote
    {
        return $this->quote;
    }

    public function priceSnapshot(): ?PriceSnapshot
    {
        return $this->priceSnapshot;
    }

    /**
     * @return array<BookingEvent>
     */
    public function events(): array
    {
        return $this->events;
    }

    /**
     * @throws InvalidBookingException
     */
    public function createQuote(Quote $quote): void
    {
        if (!$this->status->isDraft() && !$this->status->isQuoted()) {
            throw new InvalidBookingException(
                sprintf('Cannot create quote for booking in status "%s".', $this->status->value)
            );
        }

        try {
            $this->quote = $quote;
            $this->priceSnapshot = PriceSnapshot::fromQuote($quote);
            $this->status = BookingStatus::Quoted;
            $this->recordEvent('booking.quoted');
        } catch (InvalidPriceSnapshotException $e) {
            throw new InvalidBookingException('Failed to snapshot quote price: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @throws InvalidBookingException
     */
    public function markPending(): void
    {
        if (!$this->status->isQuoted()) {
            throw new InvalidBookingException(
                sprintf('Cannot mark booking as pending from status "%s".', $this->status->value)
            );
        }

        $this->status = BookingStatus::Pending;
        $this->recordEvent('booking.pending');
    }

    /**
     * @throws InvalidBookingException
     */
    public function confirm(): void
    {
        if ($this->quote === null || $this->priceSnapshot === null) {
            throw new InvalidBookingException('Cannot confirm booking without a valid quote and price snapshot.');
        }

        if (!$this->status->isQuoted() && !$this->status->isPending()) {
            throw new InvalidBookingException(
                sprintf('Cannot confirm booking from status "%s".', $this->status->value)
            );
        }

        $this->status = BookingStatus::Confirmed;
        $this->recordEvent('booking.confirmed');
    }

    /**
     * @throws InvalidBookingException
     */
    public function cancel(): void
    {
        if (!$this->status->isQuoted() && !$this->status->isPending() && !$this->status->isConfirmed()) {
            throw new InvalidBookingException(
                sprintf('Cannot cancel booking in status "%s".', $this->status->value)
            );
        }

        $this->status = BookingStatus::Cancelled;
        $this->recordEvent('booking.cancelled');
    }

    /**
     * @throws InvalidBookingException
     */
    public function complete(): void
    {
        if (!$this->status->isConfirmed()) {
            throw new InvalidBookingException(
                sprintf('Cannot complete booking from status "%s". Must be confirmed first.', $this->status->value)
            );
        }

        $this->status = BookingStatus::Completed;
        $this->recordEvent('booking.completed');
    }

    private function recordEvent(string $type): void
    {
        $this->events[] = new BookingEvent(
            $type,
            new DateTimeImmutable('now', new DateTimeZone('UTC'))
        );
    }
}
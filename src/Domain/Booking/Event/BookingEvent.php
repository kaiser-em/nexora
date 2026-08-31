<?php

declare(strict_types=1);

namespace Silao\Domain\Booking\Event;

use DateTimeImmutable;
use DateTimeZone;
use Silao\Domain\Booking\Exception\InvalidBookingException;

final readonly class BookingEvent
{
    private string $type;
    private DateTimeImmutable $occurredAt;
    /** @var array<string, mixed> */
    private array $metadata;

    /**
     * @param array<string, mixed> $metadata
     * @throws InvalidBookingException
     */
    public function __construct(string $type, DateTimeImmutable $occurredAt, array $metadata = [])
    {
        $trimmedType = trim($type);
        if ($trimmedType === '') {
            throw new InvalidBookingException('Booking event type cannot be empty.');
        }

        $this->type = $trimmedType;
        $this->occurredAt = $occurredAt->setTimezone(new DateTimeZone('UTC'));
        $this->metadata = $metadata;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    /**
     * @return array<string, mixed>
     */
    public function metadata(): array
    {
        return $this->metadata;
    }
}
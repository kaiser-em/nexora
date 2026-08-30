<?php

declare(strict_types=1);

namespace Nexora\Domain\Booking\Enum;

enum BookingStatus: string
{
    case Draft = 'draft';
    case Quoted = 'quoted';
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
    case Completed = 'completed';

    public function isDraft(): bool
    {
        return $this === self::Draft;
    }

    public function isQuoted(): bool
    {
        return $this === self::Quoted;
    }

    public function isPending(): bool
    {
        return $this === self::Pending;
    }

    public function isConfirmed(): bool
    {
        return $this === self::Confirmed;
    }

    public function isCancelled(): bool
    {
        return $this === self::Cancelled;
    }

    public function isCompleted(): bool
    {
        return $this === self::Completed;
    }
}
<?php

declare(strict_types=1);

namespace Nexora\Domain\Resource\Enum;

enum ResourceStatus: string
{
    case Active = 'active';
    case Maintenance = 'maintenance';
    case Inactive = 'inactive';

    public function isAvailableForBooking(): bool
    {
        return $this === self::Active;
    }
}
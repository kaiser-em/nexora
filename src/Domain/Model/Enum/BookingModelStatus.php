<?php

declare(strict_types=1);

namespace Silao\Domain\Model\Enum;

enum BookingModelStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    public function isPublished(): bool
    {
        return $this === self::Published;
    }
}
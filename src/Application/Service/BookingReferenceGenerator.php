<?php

declare(strict_types=1);

namespace Silao\Application\Service;

use Silao\Domain\Booking\ValueObject\BookingReference;

final class BookingReferenceGenerator
{
    public static function generate(?string $prefix = 'SIL'): BookingReference
    {
        $year = date('Y');
        $random = strtoupper(bin2hex(random_bytes(3))); // 6 alphanumeric chars

        return new BookingReference(sprintf('%s-%s-%s', $prefix ?? 'SIL', $year, $random));
    }
}
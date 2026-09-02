<?php

declare(strict_types=1);

namespace Silao\Domain\Booking\Repository;

use Silao\Domain\Booking\Booking;
use Silao\Domain\Booking\ValueObject\BookingId;
use Silao\Domain\Booking\ValueObject\BookingReference;
use Silao\Domain\Common\ValueObject\ZonedDateTimeRange;
use Silao\Domain\Resource\ValueObject\ResourceId;

interface BookingRepositoryInterface
{
    public function save(Booking $booking): void;

    public function findById(BookingId $id): ?Booking;

    public function findByReference(BookingReference $reference): ?Booking;

    /**
     * @return array<Booking>
     */
    public function findActiveByResourceAndDateRange(ResourceId $resourceId, ZonedDateTimeRange $range): array;
}
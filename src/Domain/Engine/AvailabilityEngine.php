<?php

declare(strict_types=1);

namespace Silao\Domain\Engine;

use Silao\Domain\Booking\Booking;
use Silao\Domain\Common\ValueObject\TimeOfDay;
use Silao\Domain\Common\ValueObject\ZonedDateTimeRange;
use Silao\Domain\Model\BookingModel;
use Silao\Domain\Model\Enum\ResourceStrategyType;
use Silao\Domain\Resource\Resource;

final class AvailabilityEngine
{
    /**
     * @param array<Booking> $activeConflictingBookings
     * @return array{is_available: bool, reason_code: ?string, remaining_capacity: int}
     */
    public static function check(
        BookingModel $model,
        ?Resource $resource,
        ZonedDateTimeRange $range,
        int $requestedCapacity = 1,
        array $activeConflictingBookings = []
    ): array {
        // 1. Model status check
        if (!$model->status()->isPublished()) {
            return [
                'is_available' => false,
                'reason_code' => 'MODEL_NOT_PUBLISHED',
                'remaining_capacity' => 0,
            ];
        }

        // 2. Resource Strategy check
        if ($model->resourceStrategy()->requiresResource() && $resource === null) {
            return [
                'is_available' => false,
                'reason_code' => 'RESOURCE_REQUIRED',
                'remaining_capacity' => 0,
            ];
        }

        // If no resource is needed by strategy, availability depends only on model status
        if ($resource === null || $model->resourceStrategy() === ResourceStrategyType::None) {
            return [
                'is_available' => true,
                'reason_code' => null,
                'remaining_capacity' => 999999,
            ];
        }

        // 3. Resource operational checks
        if (!$resource->status()->isAvailableForBooking()) {
            return [
                'is_available' => false,
                'reason_code' => 'RESOURCE_INACTIVE',
                'remaining_capacity' => 0,
            ];
        }

        if ($resource->isBlockedByBlackout($range)) {
            return [
                'is_available' => false,
                'reason_code' => 'BLACKOUT_DATE',
                'remaining_capacity' => 0,
            ];
        }

        if (!$resource->isAvailableForSlot($range)) {
            return [
                'is_available' => false,
                'reason_code' => 'OUT_OF_SCHEDULE',
                'remaining_capacity' => 0,
            ];
        }

        // 4. Capacity & Active conflicting bookings check
        $totalCapacity = $resource->capacity()->toInt();
        $occupiedCapacity = 0;

        foreach ($activeConflictingBookings as $booking) {
            if ($booking->status()->isPending() || $booking->status()->isConfirmed()) {
                if ($booking->dateTimeRange()->overlaps($range)) {
                    // For exclusive strategies (SingleSelect, AutoAssign), each booking occupies 1 slot
                    $occupiedCapacity += 1;
                }
            }
        }

        $remainingCapacity = max(0, $totalCapacity - $occupiedCapacity);

        if ($model->resourceStrategy() === ResourceStrategyType::SharedCapacityPool) {
            $isAvailable = ($occupiedCapacity + $requestedCapacity) <= $totalCapacity;
        } else {
            // Exclusive allocation: capacity must be >= 1
            $isAvailable = $occupiedCapacity === 0 && $totalCapacity >= $requestedCapacity;
        }

        return [
            'is_available' => $isAvailable,
            'reason_code' => $isAvailable ? null : 'CAPACITY_EXCEEDED',
            'remaining_capacity' => $remainingCapacity,
        ];
    }
}
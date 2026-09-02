<?php

declare(strict_types=1);

namespace Silao\Application\Service;

use Silao\Application\Command\CheckAvailabilityCommand;
use Silao\Application\DTO\AvailabilityResultDTO;
use Silao\Application\Exception\ApplicationException;
use Silao\Domain\Booking\Repository\BookingRepositoryInterface;
use Silao\Domain\Common\ValueObject\ZonedDateTimeRange;
use Silao\Domain\Engine\AvailabilityEngine;
use Silao\Domain\Model\Repository\BookingModelRepositoryInterface;
use Silao\Domain\Model\ValueObject\BookingModelId;
use Silao\Domain\Resource\Repository\ResourceRepositoryInterface;
use Silao\Domain\Resource\ValueObject\ResourceId;

final readonly class CheckAvailabilityService
{
    public function __construct(
        private BookingModelRepositoryInterface $modelRepository,
        private ResourceRepositoryInterface $resourceRepository,
        private BookingRepositoryInterface $bookingRepository
    ) {
    }

    /**
     * @throws ApplicationException
     */
    public function execute(CheckAvailabilityCommand $command): AvailabilityResultDTO
    {
        $model = $this->modelRepository->findById(BookingModelId::fromString($command->modelId));
        if ($model === null) {
            throw new ApplicationException(sprintf('Booking model "%s" not found.', $command->modelId));
        }

        $range = ZonedDateTimeRange::fromIsoStrings(
            $command->startsAtIso,
            $command->endsAtIso,
            $command->timezone
        );

        $resource = null;
        $activeBookings = [];

        if ($command->resourceId !== null && $command->resourceId !== '') {
            $resourceId = ResourceId::fromString($command->resourceId);
            $resource = $this->resourceRepository->findById($resourceId);
            if ($resource !== null) {
                $activeBookings = $this->bookingRepository->findActiveByResourceAndDateRange($resourceId, $range);
            }
        }

        $result = AvailabilityEngine::check(
            $model,
            $resource,
            $range,
            $command->requestedCapacity,
            $activeBookings
        );

        return new AvailabilityResultDTO(
            $result['is_available'],
            $result['reason_code'],
            $result['remaining_capacity']
        );
    }
}
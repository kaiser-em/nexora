<?php

declare(strict_types=1);

namespace Silao\Application\Service;

use Silao\Application\Command\UpdateResourceStatusCommand;
use Silao\Application\DTO\ResourceDTO;
use Silao\Application\Exception\ApplicationException;
use Silao\Application\Transaction\TransactionManagerInterface;
use Silao\Domain\Common\ValueObject\BlackoutPeriod;
use Silao\Domain\Resource\Enum\ResourceStatus;
use Silao\Domain\Resource\Repository\ResourceRepositoryInterface;
use Silao\Domain\Resource\Resource;
use Silao\Domain\Resource\ValueObject\ResourceId;
use Silao\Domain\Resource\ValueObject\Schedule;

final readonly class UpdateResourceStatusService
{
    public function __construct(
        private ResourceRepositoryInterface $resourceRepository,
        private TransactionManagerInterface $transactionManager
    ) {
    }

    /**
     * @throws ApplicationException
     */
    public function execute(UpdateResourceStatusCommand $command): ResourceDTO
    {
        /** @var Resource $resource */
        $resource = $this->transactionManager->transactional(function () use ($command): Resource {
            $id = ResourceId::fromString($command->resourceId);
            $resource = $this->resourceRepository->findById($id);

            if ($resource === null) {
                throw new ApplicationException(sprintf('Resource "%s" not found.', $command->resourceId));
            }

            $status = ResourceStatus::tryFrom($command->status);
            if ($status === null) {
                throw new ApplicationException(sprintf('Invalid resource status "%s".', $command->status));
            }

            $resource->updateStatus($status);
            $this->resourceRepository->save($resource);

            return $resource;
        });

        $schedules = array_map(static fn(Schedule $s) => [
            'day_of_week' => $s->dayOfWeek,
            'start_time' => $s->startTime->format(),
            'end_time' => $s->endTime->format(),
        ], $resource->schedules());

        $blackouts = array_map(static fn(BlackoutPeriod $b) => [
            'starts_at_utc' => $b->range->startsAtUtc->format('Y-m-d H:i:s'),
            'ends_at_utc' => $b->range->endsAtUtc->format('Y-m-d H:i:s'),
            'timezone' => $b->range->timezone->getName(),
            'reason' => $b->reason,
        ], $resource->blackoutPeriods());

        return new ResourceDTO(
            $resource->id()->toString(),
            $resource->name(),
            $resource->capacity()->toInt(),
            $resource->status()->value,
            $schedules,
            $blackouts,
            $resource->metadata()
        );
    }
}
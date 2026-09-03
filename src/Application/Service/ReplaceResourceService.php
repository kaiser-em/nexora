<?php

declare(strict_types=1);

namespace Silao\Application\Service;

use Silao\Application\Command\ReplaceResourceCommand;
use Silao\Application\DTO\ResourceDTO;
use Silao\Application\Exception\ApplicationException;
use Silao\Application\Transaction\TransactionManagerInterface;
use Silao\Domain\Common\ValueObject\BlackoutPeriod;
use Silao\Domain\Common\ValueObject\TimeOfDay;
use Silao\Domain\Common\ValueObject\ZonedDateTimeRange;
use Silao\Domain\Resource\Enum\ResourceStatus;
use Silao\Domain\Resource\Repository\ResourceRepositoryInterface;
use Silao\Domain\Resource\Resource;
use Silao\Domain\Resource\ValueObject\Capacity;
use Silao\Domain\Resource\ValueObject\ResourceId;
use Silao\Domain\Resource\ValueObject\Schedule;

final readonly class ReplaceResourceService
{
    public function __construct(
        private ResourceRepositoryInterface $resourceRepository,
        private TransactionManagerInterface $transactionManager
    ) {
    }

    /**
     * @throws ApplicationException
     */
    public function execute(ReplaceResourceCommand $command): ResourceDTO
    {
        /** @var Resource $resource */
        $resource = $this->transactionManager->transactional(function () use ($command): Resource {
            $id = ResourceId::fromString($command->resourceId);
            $capacity = Capacity::of($command->capacity);
            $status = ResourceStatus::tryFrom($command->status) ?? ResourceStatus::Active;

            $schedules = [];
            foreach ($command->schedules as $s) {
                $schedules[] = new Schedule(
                    (int) $s['day_of_week'],
                    TimeOfDay::fromString((string) $s['start_time']),
                    TimeOfDay::fromString((string) $s['end_time'])
                );
            }

            $blackouts = [];
            foreach ($command->blackouts as $b) {
                $range = ZonedDateTimeRange::fromIsoStrings(
                    (string) $b['starts_at_utc'],
                    (string) $b['ends_at_utc'],
                    (string) ($b['timezone'] ?? 'UTC')
                );
                $blackouts[] = new BlackoutPeriod($range, (string) ($b['reason'] ?? ''));
            }

            $resource = new Resource(
                $id,
                $command->name,
                $capacity,
                $status,
                $schedules,
                $blackouts,
                $command->metadata
            );

            $this->resourceRepository->save($resource);

            return $resource;
        });

        return self::assembleDTO($resource);
    }

    private static function assembleDTO(Resource $resource): ResourceDTO
    {
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
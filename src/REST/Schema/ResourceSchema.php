<?php

declare(strict_types=1);

namespace Silao\REST\Schema;

use Silao\Domain\Common\ValueObject\TimeOfDay;
use Silao\Domain\Resource\Enum\ResourceStatus;
use Silao\Domain\Resource\Resource;
use Silao\Domain\Resource\ValueObject\Capacity;
use Silao\Domain\Resource\ValueObject\ResourceId;
use Silao\Domain\Resource\ValueObject\Schedule;
use WP_REST_Request;

final class ResourceSchema
{
    public static function toResource(WP_REST_Request $request): Resource
    {
        $id = ResourceId::fromString((string) $request->get_param('resource_id'));
        $name = (string) $request->get_param('name');
        $capacity = Capacity::of((int) ($request->get_param('capacity') ?? 1));
        $statusStr = (string) ($request->get_param('status') ?? 'active');
        $status = ResourceStatus::tryFrom($statusStr) ?? ResourceStatus::Active;

        $rawSchedules = $request->get_param('schedules');
        $schedules = [];
        if (is_array($rawSchedules)) {
            foreach ($rawSchedules as $s) {
                if (is_array($s) && isset($s['day_of_week'], $s['start_time'], $s['end_time'])) {
                    $schedules[] = new Schedule(
                        (int) $s['day_of_week'],
                        TimeOfDay::fromString((string) $s['start_time']),
                        TimeOfDay::fromString((string) $s['end_time'])
                    );
                }
            }
        }

        $rawMeta = $request->get_param('metadata');
        $metadata = is_array($rawMeta) ? $rawMeta : [];

        return new Resource($id, $name, $capacity, $status, $schedules, [], $metadata);
    }
}
<?php

declare(strict_types=1);

namespace Silao\REST\Schema;

use Silao\Application\Command\ReplaceResourceCommand;
use Silao\Application\Command\UpdateResourceStatusCommand;
use WP_REST_Request;

final class ResourceSchema
{
    public static function toReplaceCommand(WP_REST_Request $request, ?string $id = null): ReplaceResourceCommand
    {
        $resourceId = $id ?? (string) $request->get_param('resource_id');
        $name = (string) ($request->get_param('name') ?? '');
        $capacity = (int) ($request->get_param('capacity') ?? 1);
        $status = (string) ($request->get_param('status') ?? 'active');

        $rawSchedules = $request->get_param('schedules');
        $schedules = is_array($rawSchedules) ? $rawSchedules : [];

        $rawBlackouts = $request->get_param('blackouts');
        $blackouts = is_array($rawBlackouts) ? $rawBlackouts : [];

        $rawMeta = $request->get_param('metadata');
        $metadata = is_array($rawMeta) ? $rawMeta : [];

        return new ReplaceResourceCommand($resourceId, $name, $capacity, $status, $schedules, $blackouts, $metadata);
    }

    public static function toUpdateStatusCommand(WP_REST_Request $request): UpdateResourceStatusCommand
    {
        $id = (string) $request->get_param('id');
        $status = (string) $request->get_param('status');

        return new UpdateResourceStatusCommand($id, $status);
    }
}
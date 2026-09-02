<?php

declare(strict_types=1);

namespace Silao\REST\Schema;

use Silao\Application\Command\CheckAvailabilityCommand;
use WP_REST_Request;

final class AvailabilitySchema
{
    public static function toCommand(WP_REST_Request $request): CheckAvailabilityCommand
    {
        return new CheckAvailabilityCommand(
            (string) $request->get_param('model_id'),
            $request->get_param('resource_id') !== null ? (string) $request->get_param('resource_id') : null,
            (string) $request->get_param('starts_at'),
            (string) $request->get_param('ends_at'),
            (string) ($request->get_param('timezone') ?? 'UTC'),
            (int) ($request->get_param('requested_capacity') ?? 1)
        );
    }
}
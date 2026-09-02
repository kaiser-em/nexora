<?php

declare(strict_types=1);

namespace Silao\REST\Controller;

use Silao\Domain\Resource\Repository\ResourceRepositoryInterface;
use Silao\REST\RestErrorMapper;
use Silao\REST\Schema\ResourceSchema;
use Throwable;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class ResourceController extends AbstractRestController
{
    public function getAll(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $repo = $this->container->get(ResourceRepositoryInterface::class);
            $resources = $repo->findAllActive();

            $data = [];
            foreach ($resources as $res) {
                $data[] = [
                    'resource_id' => $res->id()->toString(),
                    'name' => $res->name(),
                    'capacity' => $res->capacity()->toInt(),
                    'status' => $res->status()->value,
                    'schedules' => array_map(static fn($s) => [
                        'day_of_week' => $s->dayOfWeek,
                        'start_time' => $s->startTime->format(),
                        'end_time' => $s->endTime->format(),
                    ], $res->schedules()),
                    'metadata' => $res->metadata(),
                ];
            }

            return new WP_REST_Response(['success' => true, 'data' => $data], 200);
        } catch (Throwable $e) {
            return RestErrorMapper::toWpError($e);
        }
    }

    public function create(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $resource = ResourceSchema::toResource($request);
            $repo = $this->container->get(ResourceRepositoryInterface::class);
            $repo->save($resource);

            return new WP_REST_Response([
                'success' => true,
                'data' => [
                    'resource_id' => $resource->id()->toString(),
                    'name' => $resource->name(),
                    'capacity' => $resource->capacity()->toInt(),
                    'status' => $resource->status()->value,
                ],
            ], 201);
        } catch (Throwable $e) {
            return RestErrorMapper::toWpError($e);
        }
    }
}
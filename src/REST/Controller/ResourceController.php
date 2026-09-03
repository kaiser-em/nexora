<?php

declare(strict_types=1);

namespace Silao\REST\Controller;

use Silao\Application\Service\ReplaceResourceService;
use Silao\Application\Service\UpdateResourceStatusService;
use Silao\Domain\Resource\Repository\ResourceRepositoryInterface;
use Silao\Domain\Resource\ValueObject\ResourceId;
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

    public function getOne(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $id = ResourceId::fromString((string) $request->get_param('id'));
            $repo = $this->container->get(ResourceRepositoryInterface::class);
            $res = $repo->findById($id);

            if ($res === null) {
                return new WP_Error('silao_rest_not_found', 'Resource not found.', ['status' => 404]);
            }

            return new WP_REST_Response([
                'success' => true,
                'data' => [
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
                ],
            ], 200);
        } catch (Throwable $e) {
            return RestErrorMapper::toWpError($e);
        }
    }

    public function create(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $command = ResourceSchema::toReplaceCommand($request);
            $service = $this->container->get(ReplaceResourceService::class);
            $dto = $service->execute($command);

            return new WP_REST_Response([
                'success' => true,
                'data' => [
                    'resource_id' => $dto->resourceId,
                    'name' => $dto->name,
                    'capacity' => $dto->capacity,
                    'status' => $dto->status,
                ],
            ], 201);
        } catch (Throwable $e) {
            return RestErrorMapper::toWpError($e);
        }
    }

    public function replace(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $idStr = (string) $request->get_param('id');
            $command = ResourceSchema::toReplaceCommand($request, $idStr);
            $service = $this->container->get(ReplaceResourceService::class);
            $dto = $service->execute($command);

            return new WP_REST_Response([
                'success' => true,
                'data' => [
                    'resource_id' => $dto->resourceId,
                    'name' => $dto->name,
                    'capacity' => $dto->capacity,
                    'status' => $dto->status,
                ],
            ], 200);
        } catch (Throwable $e) {
            return RestErrorMapper::toWpError($e);
        }
    }

    public function patch(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $body = $request->get_json_params();
            $disallowedKeys = array_diff(array_keys($body), ['status']);
            if (!empty($disallowedKeys)) {
                return new WP_Error('silao_rest_bad_request', 'PATCH only supports updating status field.', ['status' => 400]);
            }

            $command = ResourceSchema::toUpdateStatusCommand($request);
            $service = $this->container->get(UpdateResourceStatusService::class);
            $dto = $service->execute($command);

            return new WP_REST_Response([
                'success' => true,
                'data' => [
                    'resource_id' => $dto->resourceId,
                    'status' => $dto->status,
                ],
            ], 200);
        } catch (Throwable $e) {
            return RestErrorMapper::toWpError($e);
        }
    }
}
<?php

declare(strict_types=1);

namespace Silao\REST\Controller;

use Silao\Application\Blueprint\InstallBlueprintService;
use Silao\Blueprint\Contract\BlueprintRegistryInterface;
use Silao\Blueprint\Exception\BlueprintNotFoundException;
use Silao\REST\RestErrorMapper;
use Throwable;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class BlueprintController extends AbstractRestController
{
    public function getAll(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $registry = $this->container->get(BlueprintRegistryInterface::class);
            $blueprints = $registry->all();

            $data = array_map(static fn($b) => [
                'id' => $b->id,
                'version' => $b->version,
                'name' => $b->name,
                'description' => $b->description,
                'category' => $b->category,
            ], $blueprints);

            return new WP_REST_Response(['success' => true, 'data' => $data], 200);
        } catch (Throwable $e) {
            return RestErrorMapper::toWpError($e);
        }
    }

    public function getOne(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $registry = $this->container->get(BlueprintRegistryInterface::class);
            $raw = $registry->getRawDefinition((string) $request->get_param('id'));

            return new WP_REST_Response(['success' => true, 'data' => $raw], 200);
        } catch (Throwable $e) {
            if ($e instanceof BlueprintNotFoundException) {
                return new WP_Error('silao_rest_not_found', $e->getMessage(), ['status' => 404]);
            }
            return RestErrorMapper::toWpError($e);
        }
    }

    public function install(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $service = $this->container->get(InstallBlueprintService::class);
            $blueprintId = (string) $request->get_param('id');
            $targetSlug = (string) $request->get_param('target_slug');
            $status = (string) ($request->get_param('status') ?? 'published');
            $createResources = (bool) ($request->get_param('create_sample_resources') ?? true);

            $dto = $service->execute($blueprintId, $targetSlug, $status, $createResources);

            return new WP_REST_Response([
                'success' => true,
                'data' => [
                    'model_id' => $dto->modelId,
                    'slug' => $dto->slug,
                    'status' => $dto->status,
                    'blueprint_id' => $dto->blueprintId,
                    'blueprint_version' => $dto->blueprintVersion,
                    'installed_resources_count' => $dto->resourcesCreated,
                ],
            ], 201);
        } catch (Throwable $e) {
            return RestErrorMapper::toWpError($e);
        }
    }
}
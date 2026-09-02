<?php

declare(strict_types=1);

namespace Silao\REST\Controller;

use Silao\Application\Service\CheckAvailabilityService;
use Silao\REST\RestErrorMapper;
use Silao\REST\Schema\AvailabilitySchema;
use Throwable;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class AvailabilityController extends AbstractRestController
{
    public function check(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        if ($this->isHoneypotTriggered($request)) {
            return new WP_REST_Response(['success' => false], 200);
        }

        try {
            $command = AvailabilitySchema::toCommand($request);
            $service = $this->container->get(CheckAvailabilityService::class);
            $dto = $service->execute($command);

            return new WP_REST_Response([
                'success' => true,
                'data' => [
                    'is_available' => $dto->isAvailable,
                    'reason_code' => $dto->reasonCode,
                    'remaining_capacity' => $dto->remainingCapacity,
                ],
            ], 200);
        } catch (Throwable $e) {
            return RestErrorMapper::toWpError($e);
        }
    }
}
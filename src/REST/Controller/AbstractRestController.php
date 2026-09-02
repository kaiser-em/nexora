<?php

declare(strict_types=1);

namespace Silao\REST\Controller;

use Silao\Infrastructure\Container\ContainerInterface;
use WP_REST_Controller;
use WP_REST_Request;

abstract class AbstractRestController extends WP_REST_Controller
{
    public function __construct(
        protected readonly ContainerInterface $container
    ) {
        $this->namespace = 'silao/v1';
    }

    public function checkPublicPermission(): bool
    {
        return true;
    }

    public function checkManageBookingsPermission(): bool
    {
        return current_user_can('manage_silao_bookings');
    }

    public function checkManageModelsPermission(): bool
    {
        return current_user_can('manage_silao');
    }

    protected function isHoneypotTriggered(WP_REST_Request $request): bool
    {
        $hp = $request->get_param('_silao_hp');
        return is_string($hp) && trim($hp) !== '';
    }
}
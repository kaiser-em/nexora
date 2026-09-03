<?php

declare(strict_types=1);

namespace Silao\REST;

use Silao\Infrastructure\Container\ContainerInterface;
use Silao\REST\Controller\AvailabilityController;
use Silao\REST\Controller\BookingController;
use Silao\REST\Controller\BookingModelController;
use Silao\REST\Controller\CustomerController;
use Silao\REST\Controller\QuoteController;
use Silao\REST\Controller\ResourceController;
use WP_REST_Server;

final readonly class RestServer
{
    public function __construct(
        private ContainerInterface $container
    ) {
    }

    public function registerRoutes(): void
    {
        $quoteCtrl = $this->container->get(QuoteController::class);
        $availCtrl = $this->container->get(AvailabilityController::class);
        $bookCtrl = $this->container->get(BookingController::class);
        $modelCtrl = $this->container->get(BookingModelController::class);
        $resCtrl = $this->container->get(ResourceController::class);
        $custCtrl = $this->container->get(CustomerController::class);

        // 1. PUBLIC ROUTES (5 routes)
        register_rest_route('silao/v1', '/quotes', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$quoteCtrl, 'calculate'],
            'permission_callback' => [$quoteCtrl, 'checkPublicPermission'],
        ]);

        register_rest_route('silao/v1', '/availability/check', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$availCtrl, 'check'],
            'permission_callback' => [$availCtrl, 'checkPublicPermission'],
        ]);

        register_rest_route('silao/v1', '/bookings', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$bookCtrl, 'create'],
            'permission_callback' => [$bookCtrl, 'checkPublicPermission'],
        ]);

        register_rest_route('silao/v1', '/booking-models', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$modelCtrl, 'getPublished'],
            'permission_callback' => [$modelCtrl, 'checkPublicPermission'],
        ]);

        register_rest_route('silao/v1', '/booking-models/(?P<id>[a-zA-Z0-9_\-]+)', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$modelCtrl, 'getOne'],
            'permission_callback' => [$modelCtrl, 'checkPublicPermission'],
        ]);

        // 2. ADMIN BOOKINGS ROUTES (3 routes - manage_silao_bookings)
        register_rest_route('silao/v1', '/bookings', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$bookCtrl, 'getAll'],
            'permission_callback' => [$bookCtrl, 'checkManageBookingsPermission'],
        ]);

        register_rest_route('silao/v1', '/bookings/(?P<id>[a-zA-Z0-9_\-]+)', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$bookCtrl, 'getOne'],
            'permission_callback' => [$bookCtrl, 'checkManageBookingsPermission'],
        ]);

        register_rest_route('silao/v1', '/bookings/(?P<id>[a-zA-Z0-9_\-]+)/transition', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$bookCtrl, 'transition'],
            'permission_callback' => [$bookCtrl, 'checkManageBookingsPermission'],
        ]);

        // 3. ADMIN CONFIGURATION ROUTES (9 routes - manage_silao)
        register_rest_route('silao/v1', '/booking-models', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$modelCtrl, 'create'],
            'permission_callback' => [$modelCtrl, 'checkManageModelsPermission'],
        ]);

        register_rest_route('silao/v1', '/booking-models/(?P<id>[a-zA-Z0-9_\-]+)', [
            'methods' => 'PUT',
            'callback' => [$modelCtrl, 'replace'],
            'permission_callback' => [$modelCtrl, 'checkManageModelsPermission'],
        ]);

        register_rest_route('silao/v1', '/booking-models/(?P<id>[a-zA-Z0-9_\-]+)', [
            'methods' => 'PATCH',
            'callback' => [$modelCtrl, 'patch'],
            'permission_callback' => [$modelCtrl, 'checkManageModelsPermission'],
        ]);

        register_rest_route('silao/v1', '/resources', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$resCtrl, 'getAll'],
            'permission_callback' => [$resCtrl, 'checkManageModelsPermission'],
        ]);

        register_rest_route('silao/v1', '/resources', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$resCtrl, 'create'],
            'permission_callback' => [$resCtrl, 'checkManageModelsPermission'],
        ]);

        register_rest_route('silao/v1', '/resources/(?P<id>[a-zA-Z0-9_\-]+)', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$resCtrl, 'getOne'],
            'permission_callback' => [$resCtrl, 'checkManageModelsPermission'],
        ]);

        register_rest_route('silao/v1', '/resources/(?P<id>[a-zA-Z0-9_\-]+)', [
            'methods' => 'PUT',
            'callback' => [$resCtrl, 'replace'],
            'permission_callback' => [$resCtrl, 'checkManageModelsPermission'],
        ]);

        register_rest_route('silao/v1', '/resources/(?P<id>[a-zA-Z0-9_\-]+)', [
            'methods' => 'PATCH',
            'callback' => [$resCtrl, 'patch'],
            'permission_callback' => [$resCtrl, 'checkManageModelsPermission'],
        ]);

        register_rest_route('silao/v1', '/customers', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$custCtrl, 'getAll'],
            'permission_callback' => [$custCtrl, 'checkManageModelsPermission'],
        ]);
    }
}
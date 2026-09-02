<?php

declare(strict_types=1);

namespace Silao\Tests\Unit\REST;

use PHPUnit\Framework\TestCase;
use Silao\Infrastructure\Container\Container;
use Silao\Infrastructure\Container\Provider\RestServiceProvider;
use Silao\REST\RestServer;

final class RestRouteRegistrationTest extends TestCase
{
    public function testRestServerRegistersAllFourteenExpectedRoutes(): void
    {
        $GLOBALS['silao_registered_rest_routes'] = [];

        $container = new Container();
        $container->register(new RestServiceProvider());

        $server = $container->get(RestServer::class);
        $server->registerRoutes();

        /** @var array<string, array<string, mixed>> $registeredRoutes */
        $registeredRoutes = $GLOBALS['silao_registered_rest_routes'];

        $this->assertArrayHasKey('silao/v1', $registeredRoutes);
        $routes = $registeredRoutes['silao/v1'];

        // Public
        $this->assertArrayHasKey('/quotes', $routes);
        $this->assertArrayHasKey('/availability/check', $routes);
        $this->assertArrayHasKey('/bookings', $routes);
        $this->assertArrayHasKey('/booking-models', $routes);
        $this->assertArrayHasKey('/booking-models/(?P<id>[a-zA-Z0-9_\-]+)', $routes);

        // Admin Bookings
        $this->assertArrayHasKey('/bookings/(?P<id>[a-zA-Z0-9_\-]+)', $routes);
        $this->assertArrayHasKey('/bookings/(?P<id>[a-zA-Z0-9_\-]+)/transition', $routes);

        // Admin Config
        $this->assertArrayHasKey('/resources', $routes);
        $this->assertArrayHasKey('/customers', $routes);
    }
}
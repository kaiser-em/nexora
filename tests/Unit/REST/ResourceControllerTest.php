<?php

declare(strict_types=1);

namespace Silao\Tests\Unit\REST;

use PHPUnit\Framework\TestCase;
use Silao\Domain\Resource\Enum\ResourceStatus;
use Silao\Domain\Resource\Repository\ResourceRepositoryInterface;
use Silao\Domain\Resource\Resource;
use Silao\Domain\Resource\ValueObject\Capacity;
use Silao\Domain\Resource\ValueObject\ResourceId;
use Silao\Infrastructure\Container\Container;
use Silao\REST\Controller\ResourceController;
use WP_REST_Request;

final class ResourceControllerTest extends TestCase
{
    public function testGetAndCreateResources(): void
    {
        $res = new Resource(ResourceId::fromString('r1'), 'Van', Capacity::of(4), ResourceStatus::Active);

        $repo = $this->createMock(ResourceRepositoryInterface::class);
        $repo->method('findAllActive')->willReturn([$res]);
        $repo->expects($this->once())->method('save');

        $container = new Container();
        $container->instance(ResourceRepositoryInterface::class, $repo);

        $controller = new ResourceController($container);

        // 1. GET /resources
        $getRes = $controller->getAll(new WP_REST_Request('GET', '/silao/v1/resources'));
        $this->assertSame(200, $getRes->get_status());
        $this->assertCount(1, $getRes->get_data()['data']);

        // 2. POST /resources
        $postReq = new WP_REST_Request('POST', '/silao/v1/resources');
        $postReq->set_body_params([
            'resource_id' => 'r2',
            'name' => 'Bus',
            'capacity' => 20,
            'status' => 'active',
        ]);
        $postRes = $controller->create($postReq);
        $this->assertSame(201, $postRes->get_status());
        $this->assertSame('r2', $postRes->get_data()['data']['resource_id']);
    }
}
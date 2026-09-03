<?php

declare(strict_types=1);

namespace Silao\Tests\Unit\REST;

use PHPUnit\Framework\TestCase;
use Silao\Application\Service\ReplaceResourceService;
use Silao\Application\Service\UpdateResourceStatusService;
use Silao\Application\Transaction\TransactionManagerInterface;
use Silao\Domain\Resource\Enum\ResourceStatus;
use Silao\Domain\Resource\Repository\ResourceRepositoryInterface;
use Silao\Domain\Resource\Resource;
use Silao\Domain\Resource\ValueObject\Capacity;
use Silao\Domain\Resource\ValueObject\ResourceId;
use Silao\Infrastructure\Container\Container;
use Silao\REST\Controller\ResourceController;
use WP_REST_Request;
use WP_REST_Response;

final class ResourceControllerTest extends TestCase
{
    public function testGetAndCreateResources(): void
    {
        $res = new Resource(ResourceId::fromString('r1'), 'Van', Capacity::of(4), ResourceStatus::Active);

        $repo = $this->createMock(ResourceRepositoryInterface::class);
        $repo->method('findAllActive')->willReturn([$res]);
        $repo->expects($this->once())->method('save');

        $txManager = new class implements TransactionManagerInterface {
            public function transactional(callable $op): mixed { return $op(); }
        };

        $replaceService = new ReplaceResourceService($repo, $txManager);
        $updateStatusService = new UpdateResourceStatusService($repo, $txManager);

        $container = new Container();
        $container->instance(ResourceRepositoryInterface::class, $repo);
        $container->instance(ReplaceResourceService::class, $replaceService);
        $container->instance(UpdateResourceStatusService::class, $updateStatusService);

        $controller = new ResourceController($container);

        // 1. GET /resources
        $getRes = $controller->getAll(new WP_REST_Request('GET', '/silao/v1/resources'));
        $this->assertInstanceOf(WP_REST_Response::class, $getRes);
        $this->assertSame(200, $getRes->get_status());
        $data = $getRes->get_data();
        $this->assertIsArray($data);
        $this->assertCount(1, $data['data']);

        // 2. POST /resources
        $postReq = new WP_REST_Request('POST', '/silao/v1/resources');
        $postReq->set_body_params([
            'resource_id' => 'r2',
            'name' => 'Bus',
            'capacity' => 20,
            'status' => 'active',
        ]);
        $postRes = $controller->create($postReq);
        $this->assertInstanceOf(WP_REST_Response::class, $postRes);
        $this->assertSame(201, $postRes->get_status());
        $postData = $postRes->get_data();
        $this->assertIsArray($postData);
        $this->assertSame('r2', $postData['data']['resource_id']);
    }
}
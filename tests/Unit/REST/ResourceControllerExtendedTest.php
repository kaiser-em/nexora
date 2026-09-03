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

final class ResourceControllerExtendedTest extends TestCase
{
    public function testGetOnePutAndPatchResource(): void
    {
        $resource = new Resource(ResourceId::fromString('r1'), 'Initial Van', Capacity::of(4), ResourceStatus::Active);

        $repo = $this->createMock(ResourceRepositoryInterface::class);
        $repo->method('findById')->willReturn($resource);

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

        // 1. GET /resources/r1
        $getReq = new WP_REST_Request('GET', '/silao/v1/resources/r1');
        $getReq->set_param('id', 'r1');
        $getRes = $controller->getOne($getReq);
        $this->assertSame(200, $getRes->get_status());
        $this->assertSame('Initial Van', $getRes->get_data()['data']['name']);

        // 2. PUT /resources/r1 (Full replacement)
        $putReq = new WP_REST_Request('PUT', '/silao/v1/resources/r1');
        $putReq->set_param('id', 'r1');
        $putReq->set_body_params([
            'resource_id' => 'r1',
            'name' => 'Replaced Bus',
            'capacity' => 25,
            'status' => 'active',
        ]);
        $putRes = $controller->replace($putReq);
        $this->assertSame(200, $putRes->get_status());
        $this->assertSame('Replaced Bus', $putRes->get_data()['data']['name']);
        $this->assertSame(25, $putRes->get_data()['data']['capacity']);

        // 3. PATCH /resources/r1 (Status only)
        $patchReq = new WP_REST_Request('PATCH', '/silao/v1/resources/r1');
        $patchReq->set_param('id', 'r1');
        $patchReq->set_body_params(['status' => 'maintenance']);
        $patchRes = $controller->patch($patchReq);
        $this->assertSame(200, $patchRes->get_status());
        $this->assertSame('maintenance', $patchRes->get_data()['data']['status']);

        // 4. PATCH rejection when passing forbidden properties
        $badPatchReq = new WP_REST_Request('PATCH', '/silao/v1/resources/r1');
        $badPatchReq->set_param('id', 'r1');
        $badPatchReq->set_body_params(['status' => 'active', 'capacity' => 50]); // capacity forbidden in PATCH
        $badPatchRes = $controller->patch($badPatchReq);
        $this->assertSame(400, $badPatchRes->get_error_data()['status']);
    }
}
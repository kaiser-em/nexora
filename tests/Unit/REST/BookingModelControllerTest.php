<?php

declare(strict_types=1);

namespace Silao\Tests\Unit\REST;

use PHPUnit\Framework\TestCase;
use Silao\Domain\Common\ValueObject\Currency;
use Silao\Domain\Common\ValueObject\Money;
use Silao\Domain\Model\BookingModel;
use Silao\Domain\Model\Enum\BookingModelStatus;
use Silao\Domain\Model\Repository\BookingModelRepositoryInterface;
use Silao\Domain\Model\ValueObject\BookingModelId;
use Silao\Infrastructure\Container\Container;
use Silao\REST\Controller\BookingModelController;
use WP_REST_Request;

final class BookingModelControllerTest extends TestCase
{
    public function testPutAndPatchBookingModel(): void
    {
        $eur = Currency::EUR();
        $model = new BookingModel(
            BookingModelId::fromString('m1'),
            'transfer',
            'Transfer Initial',
            'Desc',
            Money::of(5000, $eur),
            BookingModelStatus::Draft
        );

        $repo = $this->createMock(BookingModelRepositoryInterface::class);
        $repo->method('findById')->willReturn($model);
        $repo->expects($this->exactly(2))->method('save');

        $container = new Container();
        $container->instance(BookingModelRepositoryInterface::class, $repo);

        $controller = new BookingModelController($container);

        // 1. PATCH
        $patchReq = new WP_REST_Request('PATCH', '/silao/v1/booking-models/m1');
        $patchReq->set_param('id', 'm1');
        $patchReq->set_body_params(['name' => 'Transfer Patched', 'status' => 'published']);
        $patchRes = $controller->patch($patchReq);
        $this->assertSame(200, $patchRes->get_status());
        $this->assertSame('published', $patchRes->get_data()['data']['status']);

        // 2. PUT (Full Replace)
        $putReq = new WP_REST_Request('PUT', '/silao/v1/booking-models/m1');
        $putReq->set_param('id', 'm1');
        $putReq->set_body_params([
            'model_id' => 'm1',
            'slug' => 'transfer-replaced',
            'name' => 'Transfer Replaced',
            'currency' => 'EUR',
            'base_price' => 7500,
            'status' => 'published',
        ]);
        $putRes = $controller->replace($putReq);
        $this->assertSame(200, $putRes->get_status());
        $this->assertSame('transfer-replaced', $putRes->get_data()['data']['slug']);
    }
}
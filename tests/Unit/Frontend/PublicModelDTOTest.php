<?php

declare(strict_types=1);

namespace Silao\Tests\Unit\Frontend;

use PHPUnit\Framework\TestCase;
use Silao\Domain\Common\ValueObject\Currency;
use Silao\Domain\Common\ValueObject\Money;
use Silao\Domain\Condition\Enum\ComparisonOperator;
use Silao\Domain\Condition\ValueObject\SingleCondition;
use Silao\Domain\Model\BookingModel;
use Silao\Domain\Model\Enum\BookingModelStatus;
use Silao\Domain\Model\Enum\FieldType;
use Silao\Domain\Model\Enum\OptionPricingType;
use Silao\Domain\Model\Enum\PricingCalculationBasis;
use Silao\Domain\Model\Enum\PricingTarget;
use Silao\Domain\Model\Enum\ResourceStrategyType;
use Silao\Domain\Model\Repository\BookingModelRepositoryInterface;
use Silao\Domain\Model\ValueObject\BookingModelId;
use Silao\Domain\Model\ValueObject\Field;
use Silao\Domain\Model\ValueObject\Option;
use Silao\Domain\Model\ValueObject\PricingRule;
use Silao\Domain\Resource\ValueObject\ResourceId;
use Silao\Infrastructure\Container\Container;
use Silao\REST\Controller\BookingModelController;
use WP_REST_Request;
use WP_REST_Response;

final class PublicModelDTOTest extends TestCase
{
    public function testPublicModelEndpointSanitizesAndHidesInternalPricingRules(): void
    {
        $eur = Currency::EUR();

        $model = new BookingModel(
            BookingModelId::fromString('m_vip'),
            'transfer-vip',
            'Transfer VIP',
            'Service premium',
            Money::of(5000, $eur),
            BookingModelStatus::Published,
            ResourceStrategyType::SingleSelect,
            [ResourceId::fromString('res_van')],
            [
                new Field('passengers', FieldType::Number, 'Passagers', true, 1, ['min' => 1, 'max' => 8]),
            ],
            [
                new Option('opt_seat', 'seat', 'Siège bébé', 'Homologué', OptionPricingType::PerUnit, Money::of(1000, $eur), 0, 3, false, 1),
            ],
            [
                // Sensitive internal rule: should NEVER be exposed in the public DTO
                new PricingRule('r_internal_secret', 10, new SingleCondition('passengers', ComparisonOperator::GreaterThan, 4), PricingTarget::Fee, PricingCalculationBasis::FixedAmount, Money::of(2000, $eur)),
            ]
        );

        $repo = $this->createMock(BookingModelRepositoryInterface::class);
        $repo->method('findAllPublished')->willReturn([$model]);

        $container = new Container();
        $container->instance(BookingModelRepositoryInterface::class, $repo);

        $controller = new BookingModelController($container);

        $response = $controller->getPublished(new WP_REST_Request('GET', '/silao/v1/booking-models'));
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertSame(200, $response->get_status());

        $data = $response->get_data();
        $this->assertIsArray($data);
        $this->assertCount(1, $data['data']);

        $publicModel = $data['data'][0];

        // 1. Assert public fields exist
        $this->assertSame('m_vip', $publicModel['model_id']);
        $this->assertSame('transfer-vip', $publicModel['slug']);
        $this->assertSame('Transfer VIP', $publicModel['name']);
        $this->assertSame('50.00 €', $publicModel['base_price']['formatted']);
        $this->assertCount(1, $publicModel['fields']);
        $this->assertSame('passengers', $publicModel['fields'][0]['name']);
        $this->assertCount(1, $publicModel['options']);
        $this->assertSame('opt_seat', $publicModel['options'][0]['id']);
        $this->assertSame('10.00 €', $publicModel['options'][0]['formatted_price']);

        // 2. Assert internal rules are strictly hidden
        $this->assertArrayNotHasKey('pricing_rules', $publicModel);
        $this->assertArrayNotHasKey('eligible_resource_ids', $publicModel);
        $this->assertArrayNotHasKey('raw_sql', $publicModel);
    }
}
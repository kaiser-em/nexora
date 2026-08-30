<?php

declare(strict_types=1);

namespace Nexora\Tests\Unit\Domain\Integration;

use Nexora\Domain\Booking\Booking;
use Nexora\Domain\Booking\ValueObject\BookingId;
use Nexora\Domain\Booking\ValueObject\BookingReference;
use Nexora\Domain\Booking\ValueObject\FormDataSnapshot;
use Nexora\Domain\Common\ValueObject\Currency;
use Nexora\Domain\Common\ValueObject\Money;
use Nexora\Domain\Common\ValueObject\ZonedDateTimeRange;
use Nexora\Domain\Customer\Customer;
use Nexora\Domain\Customer\ValueObject\CustomerId;
use Nexora\Domain\Model\BookingModel;
use Nexora\Domain\Model\Enum\ResourceStrategyType;
use Nexora\Domain\Model\ValueObject\BookingModelId;
use Nexora\Domain\Resource\Resource;
use Nexora\Domain\Resource\ValueObject\Capacity;
use Nexora\Domain\Resource\ValueObject\ResourceId;
use PHPUnit\Framework\TestCase;

final class AggregateBoundaryTest extends TestCase
{
    public function testBookingModelReferencesResourceOnlyById(): void
    {
        $model = new BookingModel(
            BookingModelId::fromString('m1'),
            'transfer-service',
            'Transfer Service',
            'Description',
            Money::of(5000, Currency::EUR()),
            resourceStrategy: ResourceStrategyType::SingleSelect,
            eligibleResourceIds: [ResourceId::fromString('res_van_1')]
        );

        $this->assertCount(1, $model->eligibleResourceIds());
        $this->assertInstanceOf(ResourceId::class, $model->eligibleResourceIds()['res_van_1']);
    }

    public function testBookingReferencesModelAndResourceByIdAndCustomerBySnapshot(): void
    {
        $customer = new Customer(
            CustomerId::fromString('c1'),
            \Nexora\Domain\Customer\ValueObject\Email::fromString('client@example.com'),
            'Alice',
            'Smith'
        );

        $resource = new Resource(
            ResourceId::fromString('res_1'),
            'Mercedes E-Class',
            Capacity::of(4)
        );

        $range = ZonedDateTimeRange::fromIsoStrings('2026-06-15 10:00:00', '2026-06-15 11:00:00', 'UTC');

        $booking = new Booking(
            BookingId::fromString('b1'),
            BookingReference::fromString('NEX-2026-0001'),
            $customer->toSnapshot(),
            BookingModelId::fromString('m1'),
            $resource->id(),
            $range,
            new FormDataSnapshot(['passengers' => 2])
        );

        $this->assertSame('m1', $booking->modelId()->toString());
        $this->assertSame('res_1', $booking->resourceId()?->toString());
        $this->assertSame('Alice Smith', $booking->customerSnapshot()->fullName());
    }
}
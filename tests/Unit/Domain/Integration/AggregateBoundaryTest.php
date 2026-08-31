<?php

declare(strict_types=1);

namespace Silao\Tests\Unit\Domain\Integration;

use Silao\Domain\Booking\Booking;
use Silao\Domain\Booking\ValueObject\BookingId;
use Silao\Domain\Booking\ValueObject\BookingReference;
use Silao\Domain\Booking\ValueObject\FormDataSnapshot;
use Silao\Domain\Common\ValueObject\Currency;
use Silao\Domain\Common\ValueObject\Money;
use Silao\Domain\Common\ValueObject\ZonedDateTimeRange;
use Silao\Domain\Customer\Customer;
use Silao\Domain\Customer\ValueObject\CustomerId;
use Silao\Domain\Model\BookingModel;
use Silao\Domain\Model\Enum\ResourceStrategyType;
use Silao\Domain\Model\ValueObject\BookingModelId;
use Silao\Domain\Resource\Resource;
use Silao\Domain\Resource\ValueObject\Capacity;
use Silao\Domain\Resource\ValueObject\ResourceId;
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
            \Silao\Domain\Customer\ValueObject\Email::fromString('client@example.com'),
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
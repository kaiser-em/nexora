<?php

declare(strict_types=1);

namespace Silao\Tests\Unit\Domain\Engine;

use PHPUnit\Framework\TestCase;
use Silao\Domain\Common\ValueObject\Currency;
use Silao\Domain\Common\ValueObject\Money;
use Silao\Domain\Common\ValueObject\TimeOfDay;
use Silao\Domain\Common\ValueObject\ZonedDateTimeRange;
use Silao\Domain\Engine\AvailabilityEngine;
use Silao\Domain\Model\BookingModel;
use Silao\Domain\Model\Enum\BookingModelStatus;
use Silao\Domain\Model\Enum\ResourceStrategyType;
use Silao\Domain\Model\ValueObject\BookingModelId;
use Silao\Domain\Resource\Enum\ResourceStatus;
use Silao\Domain\Resource\Resource;
use Silao\Domain\Resource\ValueObject\Capacity;
use Silao\Domain\Resource\ValueObject\ResourceId;
use Silao\Domain\Resource\ValueObject\Schedule;

final class AvailabilityEngineTest extends TestCase
{
    private Currency $eur;

    protected function setUp(): void
    {
        $this->eur = Currency::EUR();
    }

    public function testModelDraftIsUnavailable(): void
    {
        $model = new BookingModel(BookingModelId::fromString('m1'), 'm-draft', 'Draft Model', '', Money::of(1000, $this->eur), BookingModelStatus::Draft);
        $range = ZonedDateTimeRange::fromIsoStrings('2026-06-15 10:00:00', '2026-06-15 11:00:00', 'UTC');

        $result = AvailabilityEngine::check($model, null, $range);
        $this->assertFalse($result['is_available']);
        $this->assertSame('MODEL_NOT_PUBLISHED', $result['reason_code']);
    }

    public function testAvailableActiveResourceWithinSchedule(): void
    {
        $res = new Resource(ResourceId::fromString('r1'), 'Van', Capacity::of(4), ResourceStatus::Active, [
            Schedule::of(1, TimeOfDay::of(8, 0), TimeOfDay::of(18, 0)), // Monday
        ]);

        $model = new BookingModel(
            BookingModelId::fromString('m1'),
            'transfer',
            'Transfer',
            '',
            Money::of(1000, $this->eur),
            BookingModelStatus::Published,
            ResourceStrategyType::SingleSelect,
            [ResourceId::fromString('r1')]
        );

        // Monday 10:00 to 11:00 local in Paris (08:00 to 09:00 UTC)
        $range = ZonedDateTimeRange::fromIsoStrings('2026-06-15 10:00:00', '2026-06-15 11:00:00', 'Europe/Paris');

        $result = AvailabilityEngine::check($model, $res, $range, 1);
        $this->assertTrue($result['is_available']);
        $this->assertNull($result['reason_code']);
        $this->assertSame(4, $result['remaining_capacity']);
    }
}
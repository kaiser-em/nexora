<?php

declare(strict_types=1);

namespace Nexora\Tests\Unit\Domain\Common;

use DateTimeImmutable;
use DateTimeZone;
use Nexora\Domain\Common\ValueObject\BlackoutPeriod;
use Nexora\Domain\Common\ValueObject\ZonedDateTimeRange;
use PHPUnit\Framework\TestCase;

final class BlackoutPeriodTest extends TestCase
{
    private DateTimeZone $tz;

    protected function setUp(): void
    {
        $this->tz = new DateTimeZone('UTC');
    }

    public function testCreationAndReasonSanitization(): void
    {
        $range = ZonedDateTimeRange::fromIsoStrings('2026-12-25 00:00:00', '2026-12-26 00:00:00', 'UTC');
        $blackout = new BlackoutPeriod($range, '  <b>Christmas Closure</b>  ');

        $this->assertSame('Christmas Closure', $blackout->reason);
        $this->assertTrue($blackout->range->equals($range));
    }

    public function testIsBlockingOverlappingRange(): void
    {
        $range = ZonedDateTimeRange::fromIsoStrings('2026-12-25 00:00:00', '2026-12-26 00:00:00', 'UTC');
        $blackout = new BlackoutPeriod($range, 'Christmas Closure');

        $bookingRequest = ZonedDateTimeRange::fromIsoStrings('2026-12-25 10:00:00', '2026-12-25 12:00:00', 'UTC');
        $this->assertTrue($blackout->isBlocking($bookingRequest));

        $outsideRequest = ZonedDateTimeRange::fromIsoStrings('2026-12-26 00:00:00', '2026-12-26 02:00:00', 'UTC');
        $this->assertFalse($blackout->isBlocking($outsideRequest));
    }

    public function testContainsPointInTime(): void
    {
        $range = ZonedDateTimeRange::fromIsoStrings('2026-12-25 00:00:00', '2026-12-26 00:00:00', 'UTC');
        $blackout = new BlackoutPeriod($range);

        $this->assertTrue($blackout->contains(new DateTimeImmutable('2026-12-25 12:00:00', $this->tz)));
        $this->assertFalse($blackout->contains(new DateTimeImmutable('2026-12-26 00:00:00', $this->tz)));
    }

    public function testEquals(): void
    {
        $range1 = ZonedDateTimeRange::fromIsoStrings('2026-12-25 00:00:00', '2026-12-26 00:00:00', 'UTC');
        $range2 = ZonedDateTimeRange::fromIsoStrings('2026-12-25 00:00:00', '2026-12-26 00:00:00', 'UTC');

        $b1 = new BlackoutPeriod($range1, 'Maintenance');
        $b2 = new BlackoutPeriod($range2, 'Maintenance');
        $b3 = new BlackoutPeriod($range1, 'Holiday');

        $this->assertTrue($b1->equals($b2));
        $this->assertFalse($b1->equals($b3));
    }
}
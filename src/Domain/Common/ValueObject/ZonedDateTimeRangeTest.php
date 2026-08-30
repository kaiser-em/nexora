<?php

declare(strict_types=1);

namespace Nexora\Tests\Unit\Domain\Common;

use DateTimeImmutable;
use DateTimeZone;
use Nexora\Domain\Common\Enum\RoundingMode;
use Nexora\Domain\Common\Exception\InvalidDateTimeRangeException;
use Nexora\Domain\Common\ValueObject\ZonedDateTimeRange;
use PHPUnit\Framework\TestCase;

final class ZonedDateTimeRangeTest extends TestCase
{
    private DateTimeZone $tzParis;
    private DateTimeZone $tzUtc;

    protected function setUp(): void
    {
        $this->tzParis = new DateTimeZone('Europe/Paris');
        $this->tzUtc = new DateTimeZone('UTC');
    }

    public function testValidCreationAndUtcNormalization(): void
    {
        // 10:00 to 12:00 in Paris during Summer (UTC+2) -> 08:00 to 10:00 UTC
        $start = new DateTimeImmutable('2026-06-15 10:00:00', $this->tzParis);
        $end = new DateTimeImmutable('2026-06-15 12:00:00', $this->tzParis);

        $range = new ZonedDateTimeRange($start, $end, $this->tzParis);

        $this->assertSame('2026-06-15 08:00:00', $range->startsAtUtc->format('Y-m-d H:i:s'));
        $this->assertSame('2026-06-15 10:00:00', $range->endsAtUtc->format('Y-m-d H:i:s'));
        $this->assertSame('2026-06-15 10:00:00', $range->startsAtLocal()->format('Y-m-d H:i:s'));
        $this->assertSame('2026-06-15 12:00:00', $range->endsAtLocal()->format('Y-m-d H:i:s'));
        $this->assertSame('Europe/Paris', $range->timezone->getName());
    }

    public function testStartsAtAfterEndsAtThrowsException(): void
    {
        $start = new DateTimeImmutable('2026-06-15 12:00:00', $this->tzUtc);
        $end = new DateTimeImmutable('2026-06-15 10:00:00', $this->tzUtc);

        $this->expectException(InvalidDateTimeRangeException::class);
        new ZonedDateTimeRange($start, $end, $this->tzUtc);
    }

    public function testZeroDurationThrowsException(): void
    {
        $start = new DateTimeImmutable('2026-06-15 10:00:00', $this->tzUtc);
        $end = new DateTimeImmutable('2026-06-15 10:00:00', $this->tzUtc);

        $this->expectException(InvalidDateTimeRangeException::class);
        new ZonedDateTimeRange($start, $end, $this->tzUtc);
    }

    public function testFromIsoStringsWithInvalidTimezoneThrowsException(): void
    {
        $this->expectException(InvalidDateTimeRangeException::class);
        ZonedDateTimeRange::fromIsoStrings(
            '2026-06-15 10:00:00',
            '2026-06-15 12:00:00',
            'Invalid/Timezone'
        );
    }

    public function testDurationCalculations(): void
    {
        $start = new DateTimeImmutable('2026-06-15 10:00:00', $this->tzUtc);
        $end = new DateTimeImmutable('2026-06-15 11:30:00', $this->tzUtc); // 90 min (1.5h)

        $range = new ZonedDateTimeRange($start, $end, $this->tzUtc);

        $this->assertSame(90, $range->durationInMinutes());
        $this->assertSame(1, $range->durationInFullHours());
        $this->assertSame(2, $range->durationInHours(RoundingMode::HalfUp));   // 1.5 -> 2
        $this->assertSame(2, $range->durationInHours(RoundingMode::HalfEven)); // 1.5 -> 2 (odd Q=1 -> 2)

        // 30 min (0.5h)
        $range30 = new ZonedDateTimeRange(
            $start,
            new DateTimeImmutable('2026-06-15 10:30:00', $this->tzUtc),
            $this->tzUtc
        );
        $this->assertSame(30, $range30->durationInMinutes());
        $this->assertSame(0, $range30->durationInFullHours());
        $this->assertSame(1, $range30->durationInHours(RoundingMode::HalfUp));   // 0.5 -> 1
        $this->assertSame(0, $range30->durationInHours(RoundingMode::HalfEven)); // 0.5 -> 0 (even Q=0 -> 0)
    }

    /**
     * Formal Overlaps Test Matrix
     */
    public function testOverlapsMatrix(): void
    {
        $base = ZonedDateTimeRange::fromIsoStrings('2026-06-15 10:00:00', '2026-06-15 12:00:00', 'UTC');

        // 1. Identical
        $identical = ZonedDateTimeRange::fromIsoStrings('2026-06-15 10:00:00', '2026-06-15 12:00:00', 'UTC');
        $this->assertTrue($base->overlaps($identical));

        // 2. Inside
        $inside = ZonedDateTimeRange::fromIsoStrings('2026-06-15 10:30:00', '2026-06-15 11:30:00', 'UTC');
        $this->assertTrue($base->overlaps($inside));

        // 3. Enclosing
        $enclosing = ZonedDateTimeRange::fromIsoStrings('2026-06-15 09:00:00', '2026-06-15 13:00:00', 'UTC');
        $this->assertTrue($base->overlaps($enclosing));

        // 4. Overlap Start
        $overlapStart = ZonedDateTimeRange::fromIsoStrings('2026-06-15 09:00:00', '2026-06-15 11:00:00', 'UTC');
        $this->assertTrue($base->overlaps($overlapStart));

        // 5. Overlap End
        $overlapEnd = ZonedDateTimeRange::fromIsoStrings('2026-06-15 11:00:00', '2026-06-15 13:00:00', 'UTC');
        $this->assertTrue($base->overlaps($overlapEnd));

        // 6. Adjacent (EndsAt == StartsAt) -> NON-CONFLICT
        $adjacentAfter = ZonedDateTimeRange::fromIsoStrings('2026-06-15 12:00:00', '2026-06-15 14:00:00', 'UTC');
        $this->assertFalse($base->overlaps($adjacentAfter));

        // 7. Adjacent (StartsAt == EndsAt) -> NON-CONFLICT
        $adjacentBefore = ZonedDateTimeRange::fromIsoStrings('2026-06-15 08:00:00', '2026-06-15 10:00:00', 'UTC');
        $this->assertFalse($base->overlaps($adjacentBefore));

        // 8. Completely Disjoint
        $disjoint = ZonedDateTimeRange::fromIsoStrings('2026-06-15 15:00:00', '2026-06-15 17:00:00', 'UTC');
        $this->assertFalse($base->overlaps($disjoint));
    }

    public function testContainsPointInTime(): void
    {
        $range = ZonedDateTimeRange::fromIsoStrings('2026-06-15 10:00:00', '2026-06-15 12:00:00', 'UTC');

        $this->assertTrue($range->contains(new DateTimeImmutable('2026-06-15 10:00:00', $this->tzUtc)));
        $this->assertTrue($range->contains(new DateTimeImmutable('2026-06-15 11:00:00', $this->tzUtc)));
        $this->assertFalse($range->contains(new DateTimeImmutable('2026-06-15 12:00:00', $this->tzUtc))); // Upper bound exclusive
        $this->assertFalse($range->contains(new DateTimeImmutable('2026-06-15 09:59:59', $this->tzUtc)));
    }

    /**
     * DST Scenario 1: Spring Forward (01:30 to 03:30 in Paris -> 60 minutes)
     */
    public function testDstSpringForwardExactDuration(): void
    {
        // On 2026-03-29 in Paris, clock jumps from 02:00 to 03:00.
        // 01:30 is UTC+1 (00:30 UTC), 03:30 is UTC+2 (01:30 UTC). Duration = 60 minutes.
        $range = ZonedDateTimeRange::fromIsoStrings(
            '2026-03-29 01:30:00',
            '2026-03-29 03:30:00',
            'Europe/Paris'
        );

        $this->assertSame(60, $range->durationInMinutes());
        $this->assertSame(1, $range->durationInFullHours());
    }

    /**
     * DST Scenario 2: Fall Back (01:00 to 03:00 in Paris -> 180 minutes)
     */
    public function testDstFallBackExactDuration(): void
    {
        // On 2026-10-25 in Paris, clock falls back from 03:00 to 02:00.
        // 01:00 is UTC+2 (23:00 UTC previous day), 03:00 standard is UTC+1 (02:00 UTC). Duration = 180 minutes.
        $range = ZonedDateTimeRange::fromIsoStrings(
            '2026-10-25 01:00:00',
            '2026-10-25 03:00:00',
            'Europe/Paris'
        );

        $this->assertSame(180, $range->durationInMinutes());
        $this->assertSame(3, $range->durationInFullHours());
    }

    /**
     * DST Scenario 3: Explicit Ambiguity Offsets (+02:00 vs +01:00 -> 2 distinct UTC instants)
     */
    public function testDstAmbiguityExplicitOffsets(): void
    {
        $rangeSummer = ZonedDateTimeRange::fromIsoStrings(
            '2026-10-25T02:30:00+02:00',
            '2026-10-25T04:00:00+01:00',
            'Europe/Paris'
        );

        $rangeWinter = ZonedDateTimeRange::fromIsoStrings(
            '2026-10-25T02:30:00+01:00',
            '2026-10-25T04:00:00+01:00',
            'Europe/Paris'
        );

        // Summer offset (+02:00) starts at 00:30 UTC -> 150 min duration to 03:00 UTC
        $this->assertSame(150, $rangeSummer->durationInMinutes());

        // Winter offset (+01:00) starts at 01:30 UTC -> 90 min duration to 03:00 UTC
        $this->assertSame(90, $rangeWinter->durationInMinutes());

        // Distinct UTC start instants confirmed
        $this->assertFalse($rangeSummer->startsAtUtc == $rangeWinter->startsAtUtc);
        $this->assertSame(3600, $rangeWinter->startsAtUtc->getTimestamp() - $rangeSummer->startsAtUtc->getTimestamp());
    }

    /**
     * DST Scenario 4: ISO with explicit 'Z' (UTC)
     */
    public function testIsoExplicitUtc(): void
    {
        $range = ZonedDateTimeRange::fromIsoStrings(
            '2026-06-15T10:00:00Z',
            '2026-06-15T12:00:00Z',
            'UTC'
        );

        $this->assertSame('2026-06-15 10:00:00', $range->startsAtUtc->format('Y-m-d H:i:s'));
        $this->assertSame(120, $range->durationInMinutes());
    }

    /**
     * DST Scenario 5: ISO locale without offset resolved by timezone
     */
    public function testIsoLocalResolvedByTimezone(): void
    {
        // 10:00 in Paris during Summer is UTC+2 -> 08:00 UTC
        $range = ZonedDateTimeRange::fromIsoStrings(
            '2026-06-15 10:00:00',
            '2026-06-15 12:00:00',
            'Europe/Paris'
        );

        $this->assertSame('2026-06-15 08:00:00', $range->startsAtUtc->format('Y-m-d H:i:s'));
        $this->assertSame('2026-06-15 10:00:00', $range->endsAtUtc->format('Y-m-d H:i:s'));
    }
}
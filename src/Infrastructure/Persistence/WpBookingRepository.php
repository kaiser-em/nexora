<?php

declare(strict_types=1);

namespace Silao\Infrastructure\Persistence;

use Silao\Domain\Booking\Booking;
use Silao\Domain\Booking\Repository\BookingRepositoryInterface;
use Silao\Domain\Booking\ValueObject\BookingId;
use Silao\Domain\Booking\ValueObject\BookingReference;
use Silao\Domain\Common\ValueObject\ZonedDateTimeRange;
use Silao\Domain\Resource\ValueObject\ResourceId;
use Silao\Infrastructure\Database\TableNames;
use Silao\Infrastructure\Exception\PersistenceException;
use Silao\Infrastructure\Mapper\BookingMapper;
use Silao\Infrastructure\Mapper\PriceSnapshotMapper;

final class WpBookingRepository implements BookingRepositoryInterface
{
    private string $bookingsTable;
    private string $snapshotsTable;
    private string $eventsTable;
    private string $resourcesTable;

    /**
     * @param object $wpdb
     */
    public function __construct(
        private readonly object $wpdb,
        string $prefix
    ) {
        $this->bookingsTable = TableNames::bookings($prefix);
        $this->snapshotsTable = TableNames::priceSnapshots($prefix);
        $this->eventsTable = TableNames::bookingEvents($prefix);
        $this->resourcesTable = TableNames::resources($prefix);
    }

    /**
     * @throws PersistenceException
     */
    public function save(Booking $booking): void
    {
        $this->wpdb->query('START TRANSACTION');

        try {
            // 1. CONCURRENCY CONTROL: Resource Row-Level Exclusive Lock (InnoDB X-Lock)
            if ($booking->resourceId() !== null && ($booking->status()->isPending() || $booking->status()->isConfirmed())) {
                $this->assertResourceAvailabilityUnderLock($booking);
            }

            // 2. Persist or update primary booking row
            $bData = BookingMapper::toDatabase($booking);
            $bData['created_at_utc'] = $bData['updated_at_utc'];

            $bookingSql = $this->wpdb->prepare(
                "INSERT INTO {$this->bookingsTable}
                 (booking_id, reference, model_id, customer_id, resource_id, status, starts_at_utc, ends_at_utc, timezone, currency, form_data_json, customer_snapshot_json, created_at_utc, updated_at_utc)
                 VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
                 ON DUPLICATE KEY UPDATE
                 resource_id = VALUES(resource_id),
                 status = VALUES(status),
                 starts_at_utc = VALUES(starts_at_utc),
                 ends_at_utc = VALUES(ends_at_utc),
                 timezone = VALUES(timezone),
                 currency = VALUES(currency),
                 form_data_json = VALUES(form_data_json),
                 customer_snapshot_json = VALUES(customer_snapshot_json),
                 updated_at_utc = VALUES(updated_at_utc)",
                $bData['booking_id'],
                $bData['reference'],
                $bData['model_id'],
                $bData['customer_id'],
                $bData['resource_id'],
                $bData['status'],
                $bData['starts_at_utc'],
                $bData['ends_at_utc'],
                $bData['timezone'],
                $bData['currency'],
                $bData['form_data_json'],
                $bData['customer_snapshot_json'],
                $bData['created_at_utc'],
                $bData['updated_at_utc']
            );

            $res = $this->wpdb->query($bookingSql);
            if ($res === false) {
                throw new PersistenceException('SQL error inserting booking: ' . ($this->wpdb->last_error ?? 'Unknown error'));
            }

            // 3. Persist PriceSnapshot if available
            if ($booking->priceSnapshot() !== null) {
                $sData = PriceSnapshotMapper::toDatabase($booking->id()->toString(), $booking->priceSnapshot(), $booking->quote());
                $snapSql = $this->wpdb->prepare(
                    "INSERT INTO {$this->snapshotsTable}
                     (booking_id, currency, subtotal, fees, discounts, total, calculated_at_utc, lines_json, created_at_utc)
                     VALUES (%s, %s, %d, %d, %d, %d, %s, %s, %s)
                     ON DUPLICATE KEY UPDATE
                     currency = VALUES(currency),
                     subtotal = VALUES(subtotal),
                     fees = VALUES(fees),
                     discounts = VALUES(discounts),
                     total = VALUES(total),
                     calculated_at_utc = VALUES(calculated_at_utc),
                     lines_json = VALUES(lines_json)",
                    $sData['booking_id'],
                    $sData['currency'],
                    $sData['subtotal'],
                    $sData['fees'],
                    $sData['discounts'],
                    $sData['total'],
                    $sData['calculated_at_utc'],
                    $sData['lines_json'],
                    $sData['created_at_utc']
                );

                $snapRes = $this->wpdb->query($snapSql);
                if ($snapRes === false) {
                    throw new PersistenceException('SQL error inserting price snapshot: ' . ($this->wpdb->last_error ?? 'Unknown error'));
                }
            }

            // 4. EVENT IDEMPOTENCY: Insert only newly raised events
            $this->insertNewEventsIdempotently($booking);

            $this->wpdb->query('COMMIT');
        } catch (\Throwable $e) {
            $this->wpdb->query('ROLLBACK');
            throw new PersistenceException(
                sprintf('Transactional save failed for booking "%s": %s', $booking->id()->toString(), $e->getMessage()),
                0,
                $e
            );
        }
    }

    public function findById(BookingId $id): ?Booking
    {
        $bSql = $this->wpdb->prepare(
            "SELECT * FROM {$this->bookingsTable} WHERE booking_id = %s LIMIT 1",
            $id->toString()
        );
        $bRow = $this->wpdb->get_row($bSql, ARRAY_A);
        if ($bRow === null || !is_array($bRow)) {
            return null;
        }

        $sSql = $this->wpdb->prepare(
            "SELECT * FROM {$this->snapshotsTable} WHERE booking_id = %s LIMIT 1",
            $id->toString()
        );
        $sRow = $this->wpdb->get_row($sSql, ARRAY_A);
        $priceRow = is_array($sRow) ? $sRow : null;

        $eSql = $this->wpdb->prepare(
            "SELECT * FROM {$this->eventsTable} WHERE booking_id = %s ORDER BY occurred_at_utc ASC, id ASC",
            $id->toString()
        );
        $eRows = $this->wpdb->get_results($eSql, ARRAY_A);
        $eventRows = is_array($eRows) ? $eRows : [];

        return BookingMapper::toDomain($bRow, $priceRow, $eventRows);
    }

    public function findByReference(BookingReference $reference): ?Booking
    {
        $sql = $this->wpdb->prepare(
            "SELECT booking_id FROM {$this->bookingsTable} WHERE reference = %s LIMIT 1",
            $reference->toString()
        );
        $row = $this->wpdb->get_row($sql, ARRAY_A);
        if ($row === null || !is_array($row) || !isset($row['booking_id'])) {
            return null;
        }

        return $this->findById(BookingId::fromString((string) $row['booking_id']));
    }

    /**
     * @return array<Booking>
     */
    public function findActiveByResourceAndDateRange(ResourceId $resourceId, ZonedDateTimeRange $range): array
    {
        $sql = $this->wpdb->prepare(
            "SELECT booking_id FROM {$this->bookingsTable}
             WHERE resource_id = %s
               AND status IN ('pending', 'confirmed')
               AND starts_at_utc < %s
               AND ends_at_utc > %s
             ORDER BY starts_at_utc ASC",
            $resourceId->toString(),
            $range->endsAtUtc->format('Y-m-d H:i:s'),
            $range->startsAtUtc->format('Y-m-d H:i:s')
        );

        $rows = $this->wpdb->get_results($sql, ARRAY_A);
        if (!is_array($rows)) {
            return [];
        }

        $bookings = [];
        foreach ($rows as $row) {
            if (is_array($row) && isset($row['booking_id'])) {
                $b = $this->findById(BookingId::fromString((string) $row['booking_id']));
                if ($b !== null) {
                    $bookings[] = $b;
                }
            }
        }

        return $bookings;
    }

    /**
     * Lock the physical resource row to serialize concurrent booking transactions for this resource.
     *
     * @throws PersistenceException
     */
    private function assertResourceAvailabilityUnderLock(Booking $booking): void
    {
        $resourceIdStr = $booking->resourceId()?->toString();
        if ($resourceIdStr === null) {
            return;
        }

        // 1. Acquire exclusive X-Lock on the resource row
        $lockSql = $this->wpdb->prepare(
            "SELECT id, capacity FROM {$this->resourcesTable} WHERE resource_id = %s FOR UPDATE",
            $resourceIdStr
        );
        $resRow = $this->wpdb->get_row($lockSql, ARRAY_A);
        if ($resRow === null || !is_array($resRow)) {
            throw new PersistenceException(sprintf('Cannot lock non-existent resource "%s".', $resourceIdStr));
        }

        $totalCapacity = (int) ($resRow['capacity'] ?? 1);

        // 2. Under this exclusive lock, query active conflicting bookings
        $conflictSql = $this->wpdb->prepare(
            "SELECT booking_id FROM {$this->bookingsTable}
             WHERE resource_id = %s
               AND booking_id != %s
               AND status IN ('pending', 'confirmed')
               AND starts_at_utc < %s
               AND ends_at_utc > %s",
            $resourceIdStr,
            $booking->id()->toString(),
            $booking->dateTimeRange()->endsAtUtc->format('Y-m-d H:i:s'),
            $booking->dateTimeRange()->startsAtUtc->format('Y-m-d H:i:s')
        );

        $conflicts = $this->wpdb->get_results($conflictSql, ARRAY_A);
        $activeConflictsCount = is_array($conflicts) ? count($conflicts) : 0;

        if ($activeConflictsCount >= $totalCapacity) {
            throw new PersistenceException(
                sprintf('Resource "%s" is already booked by %d conflicting reservations.', $resourceIdStr, $activeConflictsCount)
            );
        }
    }

    private function insertNewEventsIdempotently(Booking $booking): void
    {
        $bookingIdStr = $booking->id()->toString();

        // 1. Query existing events for this booking
        $existingSql = $this->wpdb->prepare(
            "SELECT event_type, occurred_at_utc FROM {$this->eventsTable} WHERE booking_id = %s",
            $bookingIdStr
        );
        $existingRows = $this->wpdb->get_results($existingSql, ARRAY_A);

        $existingSet = [];
        if (is_array($existingRows)) {
            foreach ($existingRows as $row) {
                if (is_array($row)) {
                    $key = $row['event_type'] . '|' . $row['occurred_at_utc'];
                    $existingSet[$key] = true;
                }
            }
        }

        // 2. Insert only events not already in DB
        foreach ($booking->events() as $event) {
            $occurredAtStr = $event->occurredAt()->format('Y-m-d H:i:s');
            $eventKey = $event->type() . '|' . $occurredAtStr;

            if (!isset($existingSet[$eventKey])) {
                $metaJson = json_encode($event->metadata(), JSON_THROW_ON_ERROR);
                $insSql = $this->wpdb->prepare(
                    "INSERT INTO {$this->eventsTable} (booking_id, event_type, occurred_at_utc, metadata_json)
                     VALUES (%s, %s, %s, %s)",
                    $bookingIdStr,
                    $event->type(),
                    $occurredAtStr,
                    $metaJson
                );
                $this->wpdb->query($insSql);
                $existingSet[$eventKey] = true;
            }
        }
    }
}
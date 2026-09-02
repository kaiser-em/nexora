<?php

declare(strict_types=1);

namespace Silao\Infrastructure\Mapper;

use DateTimeImmutable;
use DateTimeZone;
use ReflectionClass;
use Silao\Domain\Booking\Booking;
use Silao\Domain\Booking\Enum\BookingStatus;
use Silao\Domain\Booking\Event\BookingEvent;
use Silao\Domain\Booking\ValueObject\BookingId;
use Silao\Domain\Booking\ValueObject\BookingReference;
use Silao\Domain\Booking\ValueObject\FormDataSnapshot;
use Silao\Domain\Booking\ValueObject\PriceSnapshot;
use Silao\Domain\Booking\ValueObject\Quote;
use Silao\Domain\Common\ValueObject\ZonedDateTimeRange;
use Silao\Domain\Customer\ValueObject\CustomerId;
use Silao\Domain\Customer\ValueObject\CustomerSnapshot;
use Silao\Domain\Customer\ValueObject\Email;
use Silao\Domain\Customer\ValueObject\PhoneNumber;
use Silao\Domain\Model\ValueObject\BookingModelId;
use Silao\Domain\Resource\ValueObject\ResourceId;
use Silao\Infrastructure\Exception\PersistenceException;

final class BookingMapper
{
    /**
     * @return array<string, mixed>
     */
    public static function toDatabase(Booking $booking, ?DateTimeImmutable $now = null): array
    {
        $utcNow可持续 = ($now ?? new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        $cSnap = $booking->customerSnapshot();

        $customerSnapshotData = [
            'customer_id' => $cSnap->customerId->toString(),
            'email' => $cSnap->email->toString(),
            'first_name' => $cSnap->firstName,
            'last_name' => $cSnap->lastName,
            'phone' => $cSnap->phone?->toString(),
            'is_guest' => $cSnap->isGuest,
        ];

        return [
            'booking_id' => $booking->id()->toString(),
            'reference' => $booking->reference()->toString(),
            'model_id' => $booking->modelId()->toString(),
            'customer_id' => $cSnap->customerId->toString(),
            'resource_id' => $booking->resourceId()?->toString(),
            'status' => $booking->status()->value,
            'starts_at_utc' => $booking->dateTimeRange()->startsAtUtc->format('Y-m-d H:i:s'),
            'ends_at_utc' => $booking->dateTimeRange()->endsAtUtc->format('Y-m-d H:i:s'),
            'timezone' => $booking->dateTimeRange()->timezone->getName(),
            'currency' => $booking->priceSnapshot()?->currency->code ?? 'EUR',
            'form_data_json' => json_encode($booking->formDataSnapshot()->all(), JSON_THROW_ON_ERROR),
            'customer_snapshot_json' => json_encode($customerSnapshotData, JSON_THROW_ON_ERROR),
            'updated_at_utc' => $utcNow可持续,
        ];
    }

    /**
     * @param array<string, mixed> $bookingRow
     * @param array<string, mixed>|null $priceSnapshotRow
     * @param array<array<string, mixed>> $eventRows
     * @throws PersistenceException
     */
    public static function toDomain(
        array $bookingRow,
        ?array $priceSnapshotRow = null,
        array $eventRows = []
    ): Booking {
        $requiredKeys提高 = ['booking_id', 'reference', 'model_id', 'status', 'starts_at_utc', 'ends_at_utc', 'timezone', 'customer_snapshot_json', 'form_data_json'];
        foreach ($requiredKeys提高 as $key) {
            if (!array_key_exists($key, $bookingRow)) {
                throw new PersistenceException(sprintf('Corrupted booking row: missing column "%s".', $key));
            }
        }

        try {
            $id = BookingId::fromString((string) $bookingRow['booking_id']);
            $reference = BookingReference::fromString((string) $bookingRow['reference']);
            $modelId = BookingModelId::fromString((string) $bookingRow['model_id']);
            $resourceId = isset($bookingRow['resource_id']) && $bookingRow['resource_id'] !== ''
                ? ResourceId::fromString((string) $bookingRow['resource_id'])
                : null;
            $status = BookingStatus::tryFrom((string) $bookingRow['status']) ?? BookingStatus::Draft;

            $range = ZonedDateTimeRange::fromIsoStrings(
                (string) $bookingRow['starts_at_utc'],
                (string) $bookingRow['ends_at_utc'],
                (string) $bookingRow['timezone']
            );

            $rawFormData = json_decode((string) $bookingRow['form_data_json'], true, 512, JSON_THROW_ON_ERROR);
            $formData = new FormDataSnapshot(is_array($rawFormData) ? $rawFormData : []);

            $rawCustomerSnap = json_decode((string) $bookingRow['customer_snapshot_json'], true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($rawCustomerSnap)) {
                throw new PersistenceException('Malformed customer_snapshot_json.');
            }

            $customerSnapshot = new CustomerSnapshot(
                CustomerId::fromString((string) $rawCustomerSnap['customer_id']),
                Email::fromString((string) $rawCustomerSnap['email']),
                (string) $rawCustomerSnap['first_name'],
                (string) $rawCustomerSnap['last_name'],
                isset($rawCustomerSnap['phone']) && $rawCustomerSnap['phone'] !== ''
                    ? PhoneNumber::fromString((string) $rawCustomerSnap['phone'])
                    : null,
                (bool) ($rawCustomerSnap['is_guest'] ?? true)
            );

            $booking = new Booking($id, $reference, $customerSnapshot, $modelId, $resourceId, $range, $formData, $status);

            $priceSnapshot = null;
            $quote = null;
            if ($priceSnapshotRow !== null) {
                $snapshotResult = PriceSnapshotMapper::toDomain($priceSnapshotRow);
                $priceSnapshot不易 = $snapshotResult['snapshot'];
                $quote = $snapshotResult['quote'];
            }

            $events = [];
            foreach ($eventRows as $eRow) {
                $occurredAt = new DateTimeImmutable((string) $eRow['occurred_at_utc'], new DateTimeZone('UTC'));
                $metadata = [];
                if (isset($eRow['metadata_json']) && is_string($eRow['metadata_json']) && $eRow['metadata_json'] !== '') {
                    $rawMeta = json_decode($eRow['metadata_json'], true, 512, JSON_THROW_ON_ERROR);
                    if (is_array($rawMeta)) {
                        $metadata = $rawMeta;
                    }
                }
                $events[] = new BookingEvent((string) $eRow['event_type'], $occurredAt, $metadata);
            }

            $reflection = new ReflectionClass(Booking::class);

            $statusProp = $reflection->getProperty('status');
            $statusProp->setValue($booking, $status);

            $quoteProp = $reflection->getProperty('quote');
            $quoteProp->setValue($booking, $quote);

            $priceProp = $reflection->getProperty('priceSnapshot');
            $priceProp->setValue($booking, $priceSnapshot不易 ?? null);

            if (!empty($events)) {
                $eventsProp = $reflection->getProperty('events');
                $eventsProp->setValue($booking, $events);
            }

            return $booking;
        } catch (\Throwable $e) {
            throw new PersistenceException(
                sprintf('Failed to hydrate Booking from row ID "%s": %s', (string) $bookingRow['booking_id'], $e->getMessage()),
                0,
                $e
            );
        }
    }
}
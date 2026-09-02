<?php

declare(strict_types=1);

namespace Silao\Infrastructure\Mapper;

use DateTimeImmutable;
use DateTimeZone;
use Silao\Domain\Booking\Enum\QuoteLineType;
use Silao\Domain\Booking\ValueObject\PriceSnapshot;
use Silao\Domain\Booking\ValueObject\Quote;
use Silao\Domain\Booking\ValueObject\QuoteLine;
use Silao\Domain\Common\ValueObject\Currency;
use Silao\Domain\Common\ValueObject\Money;
use Silao\Infrastructure\Exception\PersistenceException;

final class PriceSnapshotMapper
{
    /**
     * @return array<string, mixed>
     */
    public static function toDatabase(string $bookingId, PriceSnapshot $snapshot, ?Quote $quote = null): array
    {
        $linesData = [];
        if ($quote !== null) {
            foreach ($quote->lines() as $line) {
                $linesData[] = [
                    'id' => $line->id(),
                    'type' => $line->type()->value,
                    'description' => $line->description(),
                    'quantity' => $line->quantity(),
                    'unit_price' => $line->unitPrice()->amount,
                    'total' => $line->total()->amount,
                    'metadata' => $line->metadata(),
                ];
            }
        }

        return [
            'booking_id' => $bookingId,
            'currency' => $snapshot->currency->code,
            'subtotal' => $snapshot->subtotal->amount,
            'fees' => $snapshot->fees->amount,
            'discounts' => $snapshot->discounts->amount,
            'total' => $snapshot->total->amount,
            'calculated_at_utc' => $snapshot->calculatedAt->format('Y-m-d H:i:s'),
            'lines_json' => json_encode($linesData, JSON_THROW_ON_ERROR),
            'created_at_utc' => (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array{snapshot: PriceSnapshot, quote: ?Quote}
     * @throws PersistenceException
     */
    public static function toDomain(array $row): array
    {
        $requiredKeys = ['currency', 'subtotal', 'fees', 'discounts', 'total', 'calculated_at_utc'];
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $row)) {
                throw new PersistenceException(sprintf('Corrupted price_snapshot row: missing required column "%s".', $key));
            }
        }

        try {
            $currency = Currency::of((string) $row['currency']);
            $subtotal = Money::of((int) $row['subtotal'], $currency);
            $fees = Money::of((int) $row['fees'], $currency);
            $discounts = Money::of((int) $row['discounts'], $currency);
            $total = Money::of((int) $row['total'], $currency);
            $calculatedAt = new DateTimeImmutable((string) $row['calculated_at_utc'], new DateTimeZone('UTC'));

            $snapshot = new PriceSnapshot($currency, $subtotal, $fees, $discounts, $total, $calculatedAt);

            $quote = null;
            if (isset($row['lines_json']) && is_string($row['lines_json']) && $row['lines_json'] !== '' && $row['lines_json'] !== '[]') {
                $rawLines = json_decode($row['lines_json'], true, 512, JSON_THROW_ON_ERROR);
                if (is_array($rawLines) && !empty($rawLines)) {
                    $quoteLines = [];
                    foreach ($rawLines as $l) {
                        $type = QuoteLineType::tryFrom((string) $l['type']) ?? QuoteLineType::BasePrice;
                        $uPrice = Money::of((int) $l['unit_price'], $currency);
                        $lTotal = Money::of((int) $l['total'], $currency);
                        $quoteLines[] = new QuoteLine(
                            (string) $l['id'],
                            $type,
                            (string) $l['description'],
                            (int) $l['quantity'],
                            $uPrice,
                            $lTotal,
                            is_array($l['metadata'] ?? null) ? $l['metadata'] : []
                        );
                    }
                    $quote = new Quote($quoteLines, $subtotal, $fees, $discounts, $total, $currency);
                }
            }

            return ['snapshot' => $snapshot, 'quote' => $quote];
        } catch (\Throwable $e) {
            throw new PersistenceException(
                sprintf('Failed to hydrate PriceSnapshot from row: %s', $e->getMessage()),
                0,
                $e
            );
        }
    }
}
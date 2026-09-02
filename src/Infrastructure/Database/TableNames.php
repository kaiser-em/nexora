<?php

declare(strict_types=1);

namespace Silao\Infrastructure\Database;

final class TableNames
{
    public const string BOOKINGS = 'silao_bookings';
    public const string PRICE_SNAPSHOTS = 'silao_price_snapshots';
    public const string BOOKING_EVENTS = 'silao_booking_events';
    public const string CUSTOMERS = 'silao_customers';
    public const string RESOURCES = 'silao_resources';
    public const string MODELS = 'silao_models';

    public static function bookings(string $wpPrefix): string
    {
        return $wpPrefix . self::BOOKINGS;
    }

    public static function priceSnapshots(string $wpPrefix): string
    {
        return $wpPrefix . self::PRICE_SNAPSHOTS;
    }

    public static function bookingEvents(string $wpPrefix): string
    {
        return $wpPrefix . self::BOOKING_EVENTS;
    }

    public static function customers(string $wpPrefix): string
    {
        return $wpPrefix . self::CUSTOMERS;
    }

    public static function resources(string $wpPrefix): string
    {
        return $wpPrefix . self::RESOURCES;
    }

    public static function models(string $wpPrefix): string
    {
        return $wpPrefix . self::MODELS;
    }
}
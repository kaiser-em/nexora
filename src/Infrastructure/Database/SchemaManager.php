<?php

declare(strict_types=1);

namespace Silao\Infrastructure\Database;

use Silao\Infrastructure\Exception\PersistenceException;

final class SchemaManager
{
    public const string SCHEMA_VERSION = '1.0.0';
    public const string OPTION_DB_VERSION = 'silao_db_version';

    /**
     * @return array<string, string>
     */
    public static function getTableDefinitions(string $prefix): array
    {
        $bookings = TableNames::bookings($prefix);
        $snapshots = TableNames::priceSnapshots($prefix);
        $events = TableNames::bookingEvents($prefix);
        $customers = TableNames::customers($prefix);
        $resources = TableNames::resources($prefix);
        $models = TableNames::models($prefix);

        return [
            $bookings => "CREATE TABLE {$bookings} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  booking_id varchar(64) NOT NULL,
  reference varchar(64) NOT NULL,
  model_id varchar(64) NOT NULL,
  customer_id varchar(64) NOT NULL,
  resource_id varchar(64) DEFAULT NULL,
  status varchar(32) NOT NULL DEFAULT 'draft',
  starts_at_utc datetime NOT NULL,
  ends_at_utc datetime NOT NULL,
  timezone varchar(64) NOT NULL DEFAULT 'UTC',
  currency varchar(3) NOT NULL DEFAULT 'EUR',
  form_data_json longtext NOT NULL,
  customer_snapshot_json longtext NOT NULL,
  created_at_utc datetime NOT NULL,
  updated_at_utc datetime NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY booking_id (booking_id),
  UNIQUE KEY reference (reference),
  KEY idx_status_dates (status, starts_at_utc, ends_at_utc),
  KEY idx_resource_dates (resource_id, starts_at_utc, ends_at_utc),
  KEY idx_customer (customer_id),
  KEY idx_model (model_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

            $snapshots => "CREATE TABLE {$snapshots} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  booking_id varchar(64) NOT NULL,
  currency varchar(3) NOT NULL,
  subtotal bigint(20) NOT NULL,
  fees bigint(20) NOT NULL,
  discounts bigint(20) NOT NULL,
  total bigint(20) NOT NULL,
  calculated_at_utc datetime NOT NULL,
  lines_json longtext NOT NULL,
  created_at_utc datetime NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY booking_id (booking_id),
  KEY idx_calculated (calculated_at_utc)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

            $events => "CREATE TABLE {$events} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  booking_id varchar(64) NOT NULL,
  event_type varchar(64) NOT NULL,
  occurred_at_utc datetime NOT NULL,
  metadata_json longtext NOT NULL,
  PRIMARY KEY  (id),
  KEY idx_booking_events (booking_id, occurred_at_utc, event_type),
  KEY idx_event_type (event_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

            $customers => "CREATE TABLE {$customers} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  customer_id varchar(64) NOT NULL,
  wp_user_id bigint(20) unsigned DEFAULT NULL,
  email varchar(191) NOT NULL,
  first_name varchar(128) NOT NULL,
  last_name varchar(128) NOT NULL,
  phone varchar(32) DEFAULT NULL,
  created_at_utc datetime NOT NULL,
  updated_at_utc datetime NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY customer_id (customer_id),
  KEY idx_email (email),
  KEY idx_wp_user (wp_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

            $resources => "CREATE TABLE {$resources} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  resource_id varchar(64) NOT NULL,
  name varchar(255) NOT NULL,
  capacity int(11) NOT NULL,
  status varchar(32) NOT NULL DEFAULT 'active',
  schedules_json longtext NOT NULL,
  blackouts_json longtext NOT NULL,
  metadata_json longtext NOT NULL,
  created_at_utc datetime NOT NULL,
  updated_at_utc datetime NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY resource_id (resource_id),
  KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

            $models => "CREATE TABLE {$models} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  model_id varchar(64) NOT NULL,
  slug varchar(191) NOT NULL,
  name varchar(255) NOT NULL,
  description text NOT NULL,
  status varchar(32) NOT NULL DEFAULT 'draft',
  base_price_amount bigint(20) NOT NULL,
  base_price_currency varchar(3) NOT NULL,
  resource_strategy varchar(32) NOT NULL DEFAULT 'none',
  eligible_resources_json longtext NOT NULL,
  fields_json longtext NOT NULL,
  options_json longtext NOT NULL,
  pricing_rules_json longtext NOT NULL,
  created_at_utc datetime NOT NULL,
  updated_at_utc datetime NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY model_id (model_id),
  UNIQUE KEY slug (slug),
  KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
        ];
    }

    /**
     * Run migrations using dbDelta.
     *
     * @param callable(string): void $dbDeltaRunner Callable wrapping dbDelta()
     * @param callable(string, mixed): bool $optionUpdater Callable wrapping update_option()
     * @param callable(string, mixed): mixed $optionGetter Callable wrapping get_option()
     * @throws PersistenceException
     */
    public static function migrate(
        string $prefix,
        callable $dbDeltaRunner,
        callable $optionUpdater,
        callable $optionGetter
    ): void {
        $installedVersion = $optionGetter(self::OPTION_DB_VERSION, '0.0.0');

        if ($installedVersion === self::SCHEMA_VERSION) {
            return;
        }

        $definitions = self::getTableDefinitions($prefix);

        foreach ($definitions as $tableName => $sql) {
            try {
                $dbDeltaRunner($sql);
            } catch (\Throwable $e) {
                throw new PersistenceException(
                    sprintf('Failed to execute migration for table "%s": %s', $tableName, $e->getMessage()),
                    0,
                    $e
                );
            }
        }

        $updated = $optionUpdater(self::OPTION_DB_VERSION, self::SCHEMA_VERSION);
        if ($updated === false && $optionGetter(self::OPTION_DB_VERSION, null) !== self::SCHEMA_VERSION) {
            throw new PersistenceException('Failed to update database schema version option in WordPress.');
        }
    }
}
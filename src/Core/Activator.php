<?php

declare(strict_types=1);

namespace Silao\Core;

use Silao\Infrastructure\Database\SchemaManager;

final class Activator
{
    public static function activate(): void
    {
        self::checkRequirements();
        self::initializeDefaults();
        self::runDatabaseMigrations();
    }

    private static function checkRequirements(): void
    {
        if (version_compare(PHP_VERSION, SILAO_MIN_PHP_VERSION, '<')) {
            deactivate_plugins(SILAO_PLUGIN_BASENAME);
            wp_die(
                sprintf(
                    esc_html__('Silao requires PHP %2$s or higher. Your server is running PHP %1$s.', 'silao'),
                    PHP_VERSION,
                    SILAO_MIN_PHP_VERSION
                ),
                esc_html__('Plugin Activation Error', 'silao'),
                ['back_link' => true]
            );
        }

        global $wp_version;
        if (version_compare($wp_version, SILAO_MIN_WP_VERSION, '<')) {
            deactivate_plugins(SILAO_PLUGIN_BASENAME);
            wp_die(
                sprintf(
                    esc_html__('Silao requires WordPress %2$s or higher. Your site is running WordPress %1$s.', 'silao'),
                    $wp_version,
                    SILAO_MIN_WP_VERSION
                ),
                esc_html__('Plugin Activation Error', 'silao'),
                ['back_link' => true]
            );
        }
    }

    private static function initializeDefaults(): void
    {
        if (!get_option('silao_version')) {
            update_option('silao_version', SILAO_VERSION);
        }

        if (!get_option('silao_db_version')) {
            update_option('silao_db_version', SchemaManager::SCHEMA_VERSION);
        }
    }

    private static function runDatabaseMigrations(): void
    {
        global $wpdb;
        $prefix = isset($wpdb->prefix) && is_string($wpdb->prefix) ? $wpdb->prefix : 'wp_';

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        SchemaManager::migrate(
            $prefix,
            static function (string $sql): void {
                dbDelta($sql);
            },
            static function (string $key, mixed $value): bool {
                return update_option($key, $value);
            },
            static function (string $key, mixed $default = null): mixed {
                return get_option($key, $default);
            }
        );
    }
}
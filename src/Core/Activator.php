<?php

declare(strict_types=1);

namespace Nexora\Core;

defined('ABSPATH') || exit;

/**
 * Handles plugin activation lifecycle tasks.
 */
final class Activator
{
    /**
     * Run activation procedures.
     */
    public static function activate(): void
    {
        self::checkRequirements();
        self::initializeDefaults();
    }

    /**
     * Check minimum PHP and WordPress requirements.
     */
    private static function checkRequirements(): void
    {
        if (version_compare(PHP_VERSION, NEXORA_MIN_PHP_VERSION, '<')) {
            deactivate_plugins(NEXORA_PLUGIN_BASENAME);
            wp_die(
                sprintf(
                    /* translators: 1: Current PHP version, 2: Required PHP version */
                    esc_html__('NEXORA requires PHP %2$s or higher. Your server is running PHP %1$s.', 'nexora'),
                    PHP_VERSION,
                    NEXORA_MIN_PHP_VERSION
                ),
                esc_html__('Plugin Activation Error', 'nexora'),
                ['back_link' => true]
            );
        }

        global $wp_version;
        if (version_compare($wp_version, NEXORA_MIN_WP_VERSION, '<')) {
            deactivate_plugins(NEXORA_PLUGIN_BASENAME);
            wp_die(
                sprintf(
                    /* translators: 1: Current WP version, 2: Required WP version */
                    esc_html__('NEXORA requires WordPress %2$s or higher. Your site is running WordPress %1$s.', 'nexora'),
                    $wp_version,
                    NEXORA_MIN_WP_VERSION
                ),
                esc_html__('Plugin Activation Error', 'nexora'),
                ['back_link' => true]
            );
        }
    }

    /**
     * Store default options and schema version.
     */
    private static function initializeDefaults(): void
    {
        if (!get_option('nexora_version')) {
            update_option('nexora_version', NEXORA_VERSION);
        }

        if (!get_option('nexora_db_version')) {
            update_option('nexora_db_version', '1');
        }
    }
}
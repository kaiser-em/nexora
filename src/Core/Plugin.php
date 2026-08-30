<?php

declare(strict_types=1);

namespace Nexora\Core;

defined('ABSPATH') || exit;

/**
 * Core orchestrator and service registry container.
 */
final class Plugin
{
    private static ?self $instance = null;
    private bool $booted = false;

    /**
     * Singleton instance.
     */
    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
    }

    /**
     * Boot the plugin hooks and services.
     */
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->booted = true;
        $this->registerHooks();
    }

    /**
     * Register core WordPress hooks.
     */
    private function registerHooks(): void
    {
        add_action('init', [$this, 'onInit']);
        add_action('rest_api_init', [$this, 'onRestApiInit']);

        if (is_admin()) {
            add_action('admin_init', [$this, 'onAdminInit']);
        }
    }

    /**
     * Handle WordPress 'init' hook.
     */
    public function onInit(): void
    {
        load_plugin_textdomain('nexora', false, dirname(NEXORA_PLUGIN_BASENAME) . '/languages');
    }

    /**
     * Handle WordPress 'rest_api_init' hook.
     */
    public function onRestApiInit(): void
    {
        // REST routes will be registered here in upcoming phases
    }

    /**
     * Handle WordPress 'admin_init' hook.
     */
    public function onAdminInit(): void
    {
        // Admin initializations in upcoming phases
    }
}
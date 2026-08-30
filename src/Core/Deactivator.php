<?php

declare(strict_types=1);

namespace Nexora\Core;

defined('ABSPATH') || exit;

/**
 * Handles plugin deactivation lifecycle tasks.
 */
final class Deactivator
{
    /**
     * Run deactivation cleanup (no data destruction).
     */
    public static function deactivate(): void
    {
        // Flush transient caches if needed
        delete_transient('nexora_active_models');
    }
}
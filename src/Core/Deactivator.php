<?php

declare(strict_types=1);

namespace Silao\Core;

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
        delete_transient('silao_active_models');
    }
}
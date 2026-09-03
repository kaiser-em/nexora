<?php

declare(strict_types=1);

namespace Silao\Admin;

use Silao\Infrastructure\Database\SchemaManager;

final class AdminAssets
{
    private const array SILAO_HOOKS = [
        'toplevel_page_silao_dashboard',
        'silao_page_silao_booking_models',
        'silao_page_silao_bookings',
        'silao_page_silao_resources',
        'silao_page_silao_customers',
    ];

    public function enqueue(string $hookSuffix): void
    {
        if (!in_array($hookSuffix, self::SILAO_HOOKS, true)) {
            return;
        }

        $cssUrl = plugins_url('assets/css/admin.css', SILAO_PLUGIN_FILE);
        $jsUrl = plugins_url('assets/js/admin/silao-admin.js', SILAO_PLUGIN_FILE);

        wp_enqueue_style('silao-admin-css', $cssUrl, [], SILAO_VERSION);
        wp_enqueue_script('silao-admin-js', $jsUrl, [], SILAO_VERSION, true);

        // 100% Dynamic runtime configuration (Zero hardcoding)
        wp_localize_script('silao-admin-js', 'silaoAdminConfig', [
            'restUrl'      => esc_url_raw(rest_url('silao/v1')),
            'nonce'        => wp_create_nonce('wp_rest'),
            'system'       => [
                'pluginVersion' => SILAO_VERSION,
                'dbVersion'     => SchemaManager::SCHEMA_VERSION,
                'phpVersion'    => PHP_VERSION,
                'wpVersion'     => function_exists('get_bloginfo') ? get_bloginfo('version') : ($GLOBALS['wp_version'] ?? 'unknown'),
            ],
            'capabilities' => [
                'manageModels'   => current_user_can('manage_silao'),
                'manageBookings' => current_user_can('manage_silao_bookings'),
            ],
            'i18n'         => [
                'loading'       => esc_html__('Chargement...', 'silao'),
                'empty'         => esc_html__('Aucun élément trouvé.', 'silao'),
                'error'         => esc_html__('Une erreur est survenue lors de la communication avec le serveur.', 'silao'),
                'confirmAction' => esc_html__('Êtes-vous sûr de vouloir effectuer cette action ?', 'silao'),
            ],
        ]);
    }
}
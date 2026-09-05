<?php

declare(strict_types=1);

namespace Silao\Frontend;

final class FrontendAssets
{
    public function register(): void
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue']);
    }

    public function enqueue(): void
    {
        global $post;

        $shouldEnqueue = Shortcode::hasRendered();
        if (!$shouldEnqueue && is_a($post, 'WP_Post') && has_shortcode((string) $post->post_content, 'silao_booking')) {
            $shouldEnqueue = true;
        }

        if (!$shouldEnqueue) {
            return;
        }

        $cssUrl = plugins_url('assets/css/frontend.css', SILAO_PLUGIN_FILE);
        $jsUrl = plugins_url('assets/js/frontend/silao-frontend.js', SILAO_PLUGIN_FILE);

        wp_enqueue_style('silao-frontend-css', $cssUrl, [], SILAO_VERSION);
        wp_enqueue_script('silao-frontend-js', $jsUrl, [], SILAO_VERSION, ['in_footer' => true, 'strategy' => 'defer']);

        // Runtime config injection
        $configJson = wp_json_encode([
            'restUrl'   => esc_url_raw(rest_url('silao/v1')),
            'pluginVer' => SILAO_VERSION,
            'i18n'      => [
                'calculating'    => esc_html__('Calcul du devis...', 'silao'),
                'available'      => esc_html__('Créneau disponible', 'silao'),
                'unavailable'    => esc_html__('Ce créneau n\'est pas disponible.', 'silao'),
                'bookingSuccess' => esc_html__('Votre réservation a été enregistrée avec succès !', 'silao'),
                'genericError'   => esc_html__('Une erreur est survenue lors de la réservation.', 'silao'),
                'requiredField'  => esc_html__('Ce champ est obligatoire.', 'silao'),
            ],
        ]);

        wp_add_inline_script('silao-frontend-js', 'window.silaoFrontendConfig = ' . ($configJson ?: '{}') . ';', 'before');
        wp_set_script_translations('silao-frontend-js', 'silao', SILAO_PLUGIN_DIR . 'languages');
    }
}
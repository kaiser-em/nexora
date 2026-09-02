<?php
/**
 * Plugin Name:       Silao
 * Plugin URI:        https://silao.dev
 * Description:       Configurable Booking & Pricing Engine for WordPress. One engine. Any booking model.
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      8.3
 * Author:            Silao Team (Reich C)
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       silao
 * Domain Path:       /languages
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

// System Constants
define('SILAO_VERSION', '0.1.0');
define('SILAO_MIN_PHP_VERSION', '8.3');
define('SILAO_MIN_WP_VERSION', '6.0');
define('SILAO_PLUGIN_FILE', __FILE__);
define('SILAO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SILAO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SILAO_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Autoloader Safety Guard
$silao_autoloader = __DIR__ . '/vendor/autoload.php';

if (!file_exists($silao_autoloader)) {
    add_action('admin_notices', static function (): void {
        ?>
        <div class="notice notice-error">
            <p>
                <?php
                echo esc_html__(
                    'Silao: Composer dependencies are missing. Please run "composer install" in the plugin directory.',
                    'silao'
                );
                ?>
            </p>
        </div>
        <?php
    });
    return;
}

require_once $silao_autoloader;

// Lifecycle Hooks Registration
register_activation_hook(SILAO_PLUGIN_FILE, [Silao\Core\Activator::class, 'activate']);
register_deactivation_hook(SILAO_PLUGIN_FILE, [Silao\Core\Deactivator::class, 'deactivate']);

// Bootstrap Plugin
add_action('plugins_loaded', static function (): void {
    Silao\Core\Plugin::instance()->boot();
});
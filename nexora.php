<?php
/**
 * Plugin Name:       NEXORA
 * Plugin URI:        https://nexora.dev
 * Description:       Configurable Booking & Pricing Engine for WordPress. One engine. Any booking model.
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      8.3
 * Author:            Reich Carrecha
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       nexora
 * Domain Path:       /languages
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

// System Constants
define('NEXORA_VERSION', '0.1.0');
define('NEXORA_MIN_PHP_VERSION', '8.3');
define('NEXORA_MIN_WP_VERSION', '6.0');
define('NEXORA_PLUGIN_FILE', __FILE__);
define('NEXORA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('NEXORA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('NEXORA_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Autoloader Safety Guard
$nexora_autoloader = __DIR__ . '/vendor/autoload.php';

if (!file_exists($nexora_autoloader)) {
    add_action('admin_notices', static function (): void {
        ?>
        <div class="notice notice-error">
            <p>
                <?php
                echo esc_html__(
                    'NEXORA: Composer dependencies are missing. Please run "composer install" in the plugin directory.',
                    'nexora'
                );
                ?>
            </p>
        </div>
        <?php
    });
    return;
}

require_once $nexora_autoloader;

// Lifecycle Hooks Registration
register_activation_hook(NEXORA_PLUGIN_FILE, [Nexora\Core\Activator::class, 'activate']);
register_deactivation_hook(NEXORA_PLUGIN_FILE, [Nexora\Core\Deactivator::class, 'deactivate']);

// Bootstrap Plugin
add_action('plugins_loaded', static function (): void {
    Nexora\Core\Plugin::instance()->boot();
});
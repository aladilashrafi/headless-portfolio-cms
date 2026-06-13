<?php
/**
 * Plugin Name: Headless Portfolio CMS
 * Plugin URI:  https://wordpress.org/plugins/headless-portfolio-cms
 * Description: API-first headless portfolio CMS for WordPress. Powers modern frontends (Next.js, React, Astro, Nuxt, Gatsby) via a clean REST API.
 * Version:     1.1.0
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Author:      Al Adil Ashrafi
 * Author URI:  https://adilashrafi.com
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: headless-portfolio-cms
 * Domain Path: /languages
 */

defined('ABSPATH') || exit;

// ── Constants ──────────────────────────────────────────────────────────────────
define('HPCMS_VERSION', '1.1.0');
define('HPCMS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HPCMS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('HPCMS_PLUGIN_FILE', __FILE__);
define('HPCMS_API_NS', 'hpcms/v1');

// ── Autoloader ─────────────────────────────────────────────────────────────────
spl_autoload_register(function ($class) {
    $prefix = 'HPCMS\\';
    $base_dir = HPCMS_PLUGIN_DIR . 'includes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

// ── Bootstrap ──────────────────────────────────────────────────────────────────
function hpcms_bootstrap(): void
{
    HPCMS\CPT\Registry::init();
    HPCMS\Core\Taxonomies::init();
    HPCMS\Meta\Registry::init();
    HPCMS\Core\Settings::init();
    HPCMS\Core\CORS::init();
    HPCMS\API\Registry::init();
    HPCMS\Admin\Menu::init();
    HPCMS\Admin\Uninstaller::init();
    HPCMS\Core\Revalidator::init();

    // New in v1.1.0 — one-time settings migration (no-op after first run).
    HPCMS\Core\Migrator::run();
}
add_action('plugins_loaded', 'hpcms_bootstrap');

// ── Activation hook ────────────────────────────────────────────────────────────
register_activation_hook(__FILE__, 'hpcms_activate');
function hpcms_activate(): void
{
    // Register post types & taxonomies before flushing rewrites
    HPCMS\CPT\Registry::register_all();
    flush_rewrite_rules();

    // Generate API token if not already set
    if (!get_option('hpcms_api_token')) {
        update_option('hpcms_api_token', bin2hex(random_bytes(24)));
    }

    // Set defaults
    if (!get_option('hpcms_enable_api')) {
        update_option('hpcms_enable_api', '1');
    }
    if (!get_option('hpcms_enable_cors')) {
        update_option('hpcms_enable_cors', '1');
    }
    if (!get_option('hpcms_cache_duration')) {
        update_option('hpcms_cache_duration', '3600');
    }
}

// ── Deactivation hook ──────────────────────────────────────────────────────────
register_deactivation_hook(__FILE__, 'hpcms_deactivate');
function hpcms_deactivate(): void
{
    flush_rewrite_rules();
}

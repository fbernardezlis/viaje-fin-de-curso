<?php
/**
 * Plugin Name:       VFC WooCommerce
 * Plugin URI:        https://github.com/fbernardezlis/viaje-fin-de-curso
 * Description:       Integración de WooCommerce con VFC: QR por alumno+edición, sesión vinculada, cálculo del % al saldo del alumno y reglas de bloqueo/reembolso.
 * Version:           0.1.0
 * Requires at least: 6.6
 * Requires PHP:      8.1
 * Requires Plugins:  woocommerce, vfc-core
 * Author:            Dempo Digital Solutions
 * Text Domain:       vfc-woocommerce
 * Domain Path:       /languages
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('VFC_WOO_VERSION', '0.1.0');
define('VFC_WOO_FILE', __FILE__);
define('VFC_WOO_DIR', plugin_dir_path(__FILE__));
define('VFC_WOO_URL', plugin_dir_url(__FILE__));

require_once VFC_WOO_DIR . 'src/Autoloader.php';
\VFC\Woo\Autoloader::register();

add_action('before_woocommerce_init', static function (): void {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
});

add_action('plugins_loaded', static function (): void {
    \VFC\Woo\Plugin::instance()->boot();
});

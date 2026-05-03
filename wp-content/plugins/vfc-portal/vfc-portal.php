<?php
/**
 * Plugin Name:       VFC Portal
 * Plugin URI:        https://github.com/fbernardezlis/viaje-fin-de-curso
 * Description:       Portal frontend privado para alumnos, tutores y administradores de colegio. Sirve plantillas propias bajo /portal/*, expone REST especifico y genera la imagen QR de cada matricula.
 * Version:           0.1.0
 * Requires at least: 6.6
 * Requires PHP:      8.1
 * Requires Plugins:  vfc-core
 * Author:            Dempo Digital Solutions
 * Text Domain:       vfc-portal
 * Domain Path:       /languages
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('VFC_PORTAL_VERSION', '0.1.0');
define('VFC_PORTAL_FILE', __FILE__);
define('VFC_PORTAL_DIR', plugin_dir_path(__FILE__));
define('VFC_PORTAL_URL', plugin_dir_url(__FILE__));

require_once VFC_PORTAL_DIR . 'src/Autoloader.php';
\VFC\Portal\Autoloader::register();

if (is_file(VFC_PORTAL_DIR . 'vendor/autoload.php')) {
    require_once VFC_PORTAL_DIR . 'vendor/autoload.php';
}

register_activation_hook(__FILE__, [\VFC\Portal\Activator::class, 'activate']);
register_deactivation_hook(__FILE__, [\VFC\Portal\Deactivator::class, 'deactivate']);

add_action('plugins_loaded', static function (): void {
    \VFC\Portal\Plugin::instance()->boot();
});

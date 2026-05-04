<?php
/**
 * Plugin Name:       VFC Core
 * Plugin URI:        https://github.com/fbernardezlis/viaje-fin-de-curso
 * Description:       Núcleo del proyecto Viaje fin de curso: dominio (centros, ediciones, matrículas, tutores, saldos, liquidaciones), roles, REST y auditoría.
 * Version:           0.1.2
 * Requires at least: 6.6
 * Requires PHP:      8.1
 * Author:            Dempo Digital Solutions
 * Text Domain:       vfc-core
 * Domain Path:       /languages
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('VFC_CORE_VERSION', '0.1.2');
define('VFC_CORE_FILE', __FILE__);
define('VFC_CORE_DIR', plugin_dir_path(__FILE__));
define('VFC_CORE_URL', plugin_dir_url(__FILE__));

require_once VFC_CORE_DIR . 'src/Autoloader.php';
\VFC\Core\Autoloader::register();

register_activation_hook(__FILE__, [\VFC\Core\Activator::class, 'activate']);
register_deactivation_hook(__FILE__, [\VFC\Core\Deactivator::class, 'deactivate']);

add_action('plugins_loaded', static function (): void {
    \VFC\Core\Plugin::instance()->boot();
});

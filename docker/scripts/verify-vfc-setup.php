<?php
declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "Solo CLI.\n");
    exit(1);
}

$_SERVER['HTTP_HOST'] ??= 'localhost';
define('WP_USE_THEMES', false);

require '/var/www/html/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

$expectedRoles = ['vfc_super_admin', 'vfc_admin_colegio', 'vfc_alumno', 'vfc_tutor'];
$missing = [];
foreach ($expectedRoles as $role) {
    if (!get_role($role) instanceof WP_Role) {
        $missing[] = $role;
    }
}

echo "Roles VFC presentes: " . (empty($missing) ? 'OK' : 'FALTAN ' . implode(', ', $missing)) . "\n";

$expectedCaps = [
    'vfc_manage_centros',
    'vfc_approve_ediciones',
    'vfc_manage_ediciones',
    'vfc_manage_matriculas',
    'vfc_manage_centro_productos',
    'vfc_manage_liquidaciones',
    'vfc_view_audit',
    'vfc_export_audit',
    'vfc_view_own_balance',
    'vfc_manage_alias',
];

$super = get_role('vfc_super_admin');
$missingCaps = [];
if ($super instanceof WP_Role) {
    foreach ($expectedCaps as $cap) {
        if (!isset($super->capabilities[$cap]) || $super->capabilities[$cap] !== true) {
            $missingCaps[] = $cap;
        }
    }
}
echo "Capabilities en vfc_super_admin: " . (empty($missingCaps) ? 'OK' : 'FALTAN ' . implode(', ', $missingCaps)) . "\n";

$adminRole = get_role('administrator');
$adminMissing = [];
if ($adminRole instanceof WP_Role) {
    foreach ($expectedCaps as $cap) {
        if (!isset($adminRole->capabilities[$cap])) {
            $adminMissing[] = $cap;
        }
    }
}
echo "Capabilities en administrator: " . (empty($adminMissing) ? 'OK' : 'FALTAN ' . implode(', ', $adminMissing)) . "\n";

$dbVersion = get_option('vfc_db_version');
echo "vfc_db_version: " . ($dbVersion ?: 'NO SET') . "\n";

global $wpdb;
$tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}vfc_%'");
echo "Tablas VFC: " . count($tables) . "\n";

echo "Plugin VFC Core activo: " . (is_plugin_active('vfc-core/vfc-core.php') ? 'SI' : 'NO') . "\n";
echo "Plugin VFC WooCommerce activo: " . (is_plugin_active('vfc-woocommerce/vfc-woocommerce.php') ? 'SI' : 'NO') . "\n";
echo "Tema activo: " . get_stylesheet() . "\n";

<?php
declare(strict_types=1);

define('WP_USE_THEMES', false);
require '/var/www/html/wp-load.php';

if (!class_exists(\VFC\Core\Database\Migrations::class)) {
    fwrite(STDERR, "VFC Core no está cargado. Activa el plugin antes.\n");
    exit(1);
}

\VFC\Core\Database\Migrations::run();
echo "Schema actual: " . get_option(\VFC\Core\Database\Migrations::OPTION_VERSION, 'desconocido') . "\n";

global $wpdb;
$col = $wpdb->get_row(
    "SHOW COLUMNS FROM {$wpdb->prefix}vfc_movimientos_saldo LIKE 'liquidacion_item_id'",
    ARRAY_A
);
echo "Columna liquidacion_item_id en vfc_movimientos_saldo: " . ($col ? "OK ({$col['Type']})" : 'FALTA') . "\n";

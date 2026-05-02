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

if (!class_exists(\VFC\Core\Domain\Centro\CentroRepository::class)) {
    fwrite(STDERR, "VFC Core no cargado.\n");
    exit(1);
}

$repo = new \VFC\Core\Domain\Centro\CentroRepository();

// Limpia centros previos del smoke test
foreach ($repo->list(['per_page' => 100])['items'] as $c) {
    if (str_starts_with($c->slug, 'smoke-')) {
        $repo->delete((int) $c->id);
    }
}

$slug = 'smoke-' . substr(bin2hex(random_bytes(4)), 0, 6);

$centro = new \VFC\Core\Domain\Centro\Centro(
    id: null,
    nombre: 'Centro Smoke Test',
    slug: $slug,
    cif: 'B12345678',
    email: 'smoke@example.com',
    telefono: null,
    direccion: 'Calle Falsa 123',
    estado: 'activo'
);

$saved = $repo->save($centro);
echo "Creado centro id={$saved->id} slug={$saved->slug}\n";

$updated = new \VFC\Core\Domain\Centro\Centro(
    id: $saved->id,
    nombre: 'Centro Smoke Test (editado)',
    slug: $saved->slug,
    cif: 'B87654321',
    email: 'smoke@example.com',
    telefono: '600000000',
    direccion: 'Calle Falsa 123',
    estado: 'inactivo'
);
$saved = $repo->save($updated);
echo "Actualizado centro estado={$saved->estado} cif={$saved->cif}\n";

$result = $repo->list(['search' => 'Smoke', 'per_page' => 10]);
echo "Listado coincidente: " . count($result['items']) . "\n";

$repo->delete((int) $saved->id);
echo "Borrado centro id={$saved->id}\n";

global $wpdb;
$audit = (int) $wpdb->get_var(
    $wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}vfc_audit_log WHERE entidad_tipo = %s AND entidad_id = %d",
        'centro',
        $saved->id
    )
);
echo "Filas auditoria para ese centro: {$audit} (esperado 3: create+update+delete)\n";

echo "Smoke test OK\n";

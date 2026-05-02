<?php
declare(strict_types=1);

define('WP_USE_THEMES', false);
require '/var/www/html/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

use VFC\Core\Database\Schema;
use VFC\Core\Domain\Centro\Centro;
use VFC\Core\Domain\Centro\CentroRepository;
use VFC\Core\Domain\CentroProducto\CentroProductoRepository;
use VFC\Core\Domain\Edicion\Edicion;
use VFC\Core\Domain\Edicion\EdicionRepository;
use VFC\Core\Domain\Edicion\EstadoEdicion;
use VFC\Core\Domain\Liquidacion\LiquidacionRepository;
use VFC\Core\Domain\Matricula\MatriculaRepository;
use VFC\Core\Domain\Tutor\TutorAlumnoRepository;
use VFC\Core\Services\QrTokenService;
use VFC\Core\Services\UsersService;

if (!class_exists(EdicionRepository::class)) {
    fwrite(STDERR, "VFC Core no está cargado.\n");
    exit(1);
}

global $wpdb;
wp_set_current_user(1);

$line = static fn(string $msg) => fwrite(STDOUT, $msg . "\n");

$line("=== Smoke test Fase 1 ===");

$centroRepo = new CentroRepository();
$centro = $centroRepo->save(new Centro(
    id: null,
    nombre: 'IES Smoke',
    slug: 'ies-smoke-' . substr(md5((string) microtime(true)), 0, 6),
    cif: null, email: null, telefono: null, direccion: null, estado: 'activo'
));
$line("Centro creado: id={$centro->id} slug={$centro->slug}");

$edRepo = new EdicionRepository();
$ed = $edRepo->save(new Edicion(
    id: null,
    centroId: (int) $centro->id,
    nombre: 'Viaje Roma 2026',
    fechaInicio: '2026-06-01',
    fechaFin: '2026-06-07',
    estado: EstadoEdicion::BORRADOR
));
$line("Edición creada: id={$ed->id} estado={$ed->estado}");

$ed2 = $edRepo->transitionTo((int) $ed->id, EstadoEdicion::PENDIENTE);
$ed3 = $edRepo->transitionTo((int) $ed->id, EstadoEdicion::APROBADA);
$ed4 = $edRepo->transitionTo((int) $ed->id, EstadoEdicion::ACTIVA);
$line("Estados: borrador -> {$ed2->estado} -> {$ed3->estado} -> {$ed4->estado}");

$users = new UsersService();
$alumnoEmail = 'alumno+' . uniqid('', false) . '@example.com';
$tutorEmail = 'tutor+' . uniqid('', false) . '@example.com';
$alumnoId = $users->createAlumno($alumnoEmail, 'Ana', 'Smoke', (int) $centro->id);
$tutorId = $users->createTutor($tutorEmail, 'Marcos', 'Smoke');
$line("Usuarios creados: alumno={$alumnoId}, tutor={$tutorId}");

$tutorAlumno = new TutorAlumnoRepository();
$tutorAlumno->link($tutorId, $alumnoId);
$alumnos = $tutorAlumno->alumnosForTutor($tutorId);
$line("Alumnos del tutor: " . implode(',', $alumnos));

$matrRepo = new MatriculaRepository();
$created = $matrRepo->create((int) $ed->id, $alumnoId, 'Ana viaje 2026');
$matricula = $created['matricula'];
$token = $created['token'];
$line("Matrícula id={$matricula->id} alias={$matricula->alias}");
$line("Token (claro): {$token}");

$qr = new QrTokenService();
$ok = $qr->verify($token, $matricula->qrTokenHash);
$line("Token verifica: " . ($ok ? 'SI' : 'NO'));

$nuevoToken = $matrRepo->rotateToken((int) $matricula->id);
$matriculaTrasRotacion = $matrRepo->find((int) $matricula->id);
$rotaOk = !$qr->verify($token, (string) $matriculaTrasRotacion?->qrTokenHash)
    && $qr->verify($nuevoToken, (string) $matriculaTrasRotacion?->qrTokenHash);
$line("Rotación de token correcta: " . ($rotaOk ? 'SI' : 'NO'));

$cp = new CentroProductoRepository();
$cp->setEstado((int) $centro->id, 99999, false);
$status = $cp->isActivoForCentro((int) $centro->id, 99999);
$cp->setEstado((int) $centro->id, 99999, true);
$status2 = $cp->isActivoForCentro((int) $centro->id, 99999);
$line("Producto excluido luego activado: " . (!$status && $status2 ? 'OK' : 'FAIL'));

$movsTable = Schema::table(Schema::TABLE_MOVIMIENTOS_SALDO);
$now = current_time('mysql', true);
$wpdb->insert($movsTable, [
    'alumno_user_id' => $alumnoId,
    'edicion_id' => (int) $ed->id,
    'order_id' => 0,
    'tipo' => 'abono',
    'importe_sin_iva' => 12.3456,
    'estado' => 'CONFIRMADO',
    'fecha_pedido' => $now,
    'fecha_confirmacion' => $now,
    'created_at' => $now,
    'updated_at' => $now,
], ['%d', '%d', '%d', '%s', '%f', '%s', '%s', '%s', '%s', '%s']);
$wpdb->insert($movsTable, [
    'alumno_user_id' => $alumnoId,
    'edicion_id' => (int) $ed->id,
    'order_id' => 0,
    'tipo' => 'abono',
    'importe_sin_iva' => 5.6789,
    'estado' => 'CONFIRMADO',
    'fecha_pedido' => $now,
    'fecha_confirmacion' => $now,
    'created_at' => $now,
    'updated_at' => $now,
], ['%d', '%d', '%d', '%s', '%f', '%s', '%s', '%s', '%s', '%s']);
$line("Movimientos de prueba insertados (CONFIRMADO).");

$liqRepo = new LiquidacionRepository();
$pendientes = $liqRepo->pendientesPorCentro((int) $centro->id);
$line("Pendientes por centro: " . count($pendientes));
foreach ($pendientes as $p) {
    $line("  - alumno {$p['alumno_user_id']} edicion {$p['edicion_id']}: {$p['importe']} ({$p['n_movimientos']} movimientos)");
}

$liq = $liqRepo->create(
    (int) $centro->id,
    gmdate('Y-m-d'),
    'TRX-SMOKE-' . substr(md5((string) microtime(true)), 0, 6),
    'Liquidación de prueba',
    array_map(static fn($p) => ['alumno_user_id' => $p['alumno_user_id'], 'edicion_id' => $p['edicion_id']], $pendientes)
);
$line("Liquidación creada id={$liq->id} importe={$liq->importe}");

$pendientesTras = $liqRepo->pendientesPorCentro((int) $centro->id);
$line("Pendientes tras liquidar: " . count($pendientesTras) . ' (esperado 0)');

$auditRows = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}vfc_audit_log WHERE centro_id = %d",
    (int) $centro->id
));
$line("Filas de auditoría para el centro: {$auditRows}");

$server = rest_get_server();
$req = new WP_REST_Request('GET', '/vfc/v1/ediciones');
$resp = $server->dispatch($req);
$line("REST /ediciones status=" . $resp->get_status() . ' total=' . ($resp->get_headers()['X-VFC-Total'] ?? 'N/A'));

$req2 = new WP_REST_Request('GET', '/vfc/v1/matriculas');
$req2->set_query_params(['edicion_id' => (int) $ed->id]);
$resp2 = $server->dispatch($req2);
$line("REST /matriculas?edicion_id=" . (int) $ed->id . ' status=' . $resp2->get_status());

$req3 = new WP_REST_Request('GET', '/vfc/v1/audit-log');
$req3->set_query_params(['centro_id' => (int) $centro->id]);
$resp3 = $server->dispatch($req3);
$line("REST /audit-log?centro_id=" . (int) $centro->id . ' status=' . $resp3->get_status() . ' total=' . ($resp3->get_headers()['X-VFC-Total'] ?? 'N/A'));

$line("=== Smoke test Fase 1 OK ===");

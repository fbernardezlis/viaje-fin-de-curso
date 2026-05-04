<?php
declare(strict_types=1);

define('WP_USE_THEMES', false);
require '/var/www/html/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

use VFC\Core\Domain\Centro\Centro;
use VFC\Core\Domain\Centro\CentroAdminRepository;
use VFC\Core\Domain\Centro\CentroRepository;
use VFC\Core\Domain\Edicion\Edicion;
use VFC\Core\Domain\Edicion\EdicionRepository;
use VFC\Core\Domain\Edicion\EstadoEdicion;
use VFC\Core\Domain\Matricula\MatriculaRepository;
use VFC\Core\Domain\Saldo\SaldoRepository;
use VFC\Core\Domain\Tutor\TutorAlumnoRepository;
use VFC\Core\Roles\RolesInstaller;
use VFC\Core\Services\UsersService;
use VFC\Portal\Routing\Permissions;
use VFC\Portal\Services\DashboardService;
use VFC\Portal\Services\QrImageService;

if (!class_exists(\VFC\Portal\Plugin::class)) {
    fwrite(STDERR, "vfc-portal no esta cargado.\n");
    exit(1);
}

global $wpdb;
$line = static fn(string $msg) => fwrite(STDOUT, $msg . "\n");
$line("=== Smoke test Fase 3 ===");

// 1. Setup datos: centro, edicion ACTIVA, alumno, tutor, vinculo y matricula
$suffix = substr(md5((string) microtime(true)), 0, 6);
$centroRepo = new CentroRepository();
$centro = $centroRepo->save(new Centro(
    id: null,
    nombre: 'IES Phase3 ' . $suffix,
    slug: 'ies-phase3-' . $suffix,
    cif: null, email: 'p3@test.com', telefono: null, direccion: null, estado: 'activo'
));
$line("Centro id={$centro->id}");

$edRepo = new EdicionRepository();
$ed = $edRepo->save(new Edicion(
    id: null, centroId: (int) $centro->id, nombre: 'Viaje Phase3',
    fechaInicio: gmdate('Y-m-d'), fechaFin: gmdate('Y-m-d', time() + 30 * DAY_IN_SECONDS),
    estado: EstadoEdicion::BORRADOR
));
$edRepo->transitionTo((int) $ed->id, EstadoEdicion::PENDIENTE);
$edRepo->transitionTo((int) $ed->id, EstadoEdicion::APROBADA);
$edRepo->transitionTo((int) $ed->id, EstadoEdicion::ACTIVA);
$line("Edicion id={$ed->id} estado=activa");

$users = new UsersService();
$alumnoId = $users->createAlumno("phase3-alumno-{$suffix}@test.com", 'Alumno', 'P3', (int) $centro->id);
$tutorId = $users->createTutor("phase3-tutor-{$suffix}@test.com", 'Tutor', 'P3');
$line("Alumno id={$alumnoId} Tutor id={$tutorId}");

(new TutorAlumnoRepository())->link($tutorId, $alumnoId);

$matrRepo = new MatriculaRepository();
$created = $matrRepo->create((int) $ed->id, $alumnoId, 'Alumno P3 alias');
$matriculaId = (int) $created['matricula']->id;
$line("Matricula id={$matriculaId}");

// 2. Asigna admin de colegio
$adminColegioId = $users->createAlumno("phase3-admincolegio-{$suffix}@test.com", 'Admin', 'Colegio P3', (int) $centro->id);
// Cambiamos rol a admin colegio
$adminUser = get_user_by('id', $adminColegioId);
if ($adminUser instanceof WP_User) {
    $adminUser->set_role(RolesInstaller::ROLE_ADMIN_COLEGIO);
}
(new CentroAdminRepository())->assign((int) $centro->id, $adminColegioId);
$line("Admin colegio id={$adminColegioId} asignado al centro {$centro->id}");

// 3. SaldoRepository: insertar movimientos de prueba
$movsTable = $wpdb->prefix . 'vfc_movimientos_saldo';
$now = current_time('mysql', true);
$wpdb->insert($movsTable, [
    'alumno_user_id' => $alumnoId, 'edicion_id' => (int) $ed->id, 'order_id' => 99001,
    'line_item_id' => 1, 'tipo' => 'abono', 'importe_sin_iva' => 12.50,
    'estado' => 'CONFIRMADO', 'fecha_pedido' => $now, 'fecha_confirmacion' => $now,
    'created_at' => $now, 'updated_at' => $now,
], ['%d','%d','%d','%d','%s','%f','%s','%s','%s','%s','%s']);
$wpdb->insert($movsTable, [
    'alumno_user_id' => $alumnoId, 'edicion_id' => (int) $ed->id, 'order_id' => 99002,
    'line_item_id' => 1, 'tipo' => 'abono', 'importe_sin_iva' => 7.25,
    'estado' => 'BLOQUEADO', 'fecha_pedido' => $now,
    'fecha_liberacion' => gmdate('Y-m-d H:i:s', time() + DAY_IN_SECONDS * 5),
    'created_at' => $now, 'updated_at' => $now,
], ['%d','%d','%d','%d','%s','%f','%s','%s','%s','%s','%s']);

$saldoRepo = new SaldoRepository();
$saldo = $saldoRepo->saldoNeto($alumnoId);
$line('SaldoRepository: ' . json_encode($saldo));
$historial = $saldoRepo->historial(['alumno_user_id' => $alumnoId, 'per_page' => 10]);
$line('Historial total=' . $historial['total'] . ' items=' . count($historial['items']));

// 4. Permisos
$alumnoUser = get_user_by('id', $alumnoId);
$tutorUser = get_user_by('id', $tutorId);
$adminColUser = get_user_by('id', $adminColegioId);
$line('isAlumno(alumno)=' . var_export(Permissions::isAlumno($alumnoUser), true));
$line('isTutor(tutor)=' . var_export(Permissions::isTutor($tutorUser), true));
$line('isAdminColegio(admincolegio)=' . var_export(Permissions::isAdminColegio($adminColUser), true));
$line('canSeeAlumno(tutor->alumno)=' . var_export(Permissions::canSeeAlumno($tutorUser, $alumnoId), true) . ' (esperado true)');
$line('canSeeAlumno(tutor->otro=999999)=' . var_export(Permissions::canSeeAlumno($tutorUser, 999999), true) . ' (esperado false)');
$line('canSeeCentro(admincolegio)=' . var_export(Permissions::canSeeCentro($adminColUser, (int) $centro->id), true));

// 5. DashboardService
$service = new DashboardService(
    new CentroRepository(),
    new EdicionRepository(),
    new MatriculaRepository(),
    new \VFC\Core\Domain\CentroProducto\CentroProductoRepository(),
    new \VFC\Core\Domain\Liquidacion\LiquidacionRepository()
);
$snap = $service->snapshot((int) $centro->id);
$line('DashboardService: ediciones=' . $snap['resumen']['ediciones'] . ' alumnos=' . $snap['resumen']['alumnos'] . ' matriculas=' . $snap['resumen']['matriculas']);

// 6. REST: simulamos request al SaldoController logueando como alumno
wp_set_current_user($alumnoId);
$restServer = rest_get_server();
$req = new WP_REST_Request('GET', '/vfc/v1/portal/saldo');
$req->set_query_params(['alumno_id' => $alumnoId]);
$resp = $restServer->dispatch($req);
$line('REST /saldo status=' . $resp->get_status() . ' data=' . json_encode($resp->get_data()));

$req = new WP_REST_Request('GET', '/vfc/v1/portal/movimientos');
$req->set_query_params(['alumno_id' => $alumnoId, 'per_page' => 5]);
$resp = $restServer->dispatch($req);
$d = $resp->get_data();
$line('REST /movimientos status=' . $resp->get_status() . ' total=' . ($d['total'] ?? '?') . ' items=' . count($d['items'] ?? []));

$req = new WP_REST_Request('GET', '/vfc/v1/portal/movimientos');
$req->set_query_params(['alumno_id' => $alumnoId, 'per_page' => 10, 'estado' => 'BLOQUEADO']);
$resp = $restServer->dispatch($req);
$d = $resp->get_data();
$bloqueados = (int) ($d['total'] ?? 0);
$line('REST /movimientos?estado=BLOQUEADO total=' . $bloqueados . ' (esperado >= 1)');

// 7. mis-alumnos como tutor
wp_set_current_user($tutorId);
$req = new WP_REST_Request('GET', '/vfc/v1/portal/mis-alumnos');
$resp = $restServer->dispatch($req);
$d = $resp->get_data();
$line('REST /mis-alumnos status=' . $resp->get_status() . ' items=' . count($d['items'] ?? []));

// 8. dashboard centro como admin colegio
wp_set_current_user($adminColegioId);
$req = new WP_REST_Request('GET', '/vfc/v1/portal/centro/' . (int) $centro->id);
$resp = $restServer->dispatch($req);
$line('REST /centro/{id} status=' . $resp->get_status());

// 9. REST: forbidden cuando no logueado
wp_set_current_user(0);
$req = new WP_REST_Request('GET', '/vfc/v1/portal/saldo');
$req->set_query_params(['alumno_id' => $alumnoId]);
$resp = $restServer->dispatch($req);
$line('REST /saldo (sin auth) status=' . $resp->get_status() . ' (esperado 401)');

// 10. QrImageService: stream PNG
wp_set_current_user($alumnoId);
ob_start();
try {
    $hdrs_before = headers_list();
    (new QrImageService())->stream($matriculaId);
} catch (Throwable $e) {
    // exit dentro del stream se intercepta como excepcion en CLI? No siempre; capturamos cualquier salida.
}
$png = ob_get_clean() ?: '';
$line('QrImageService: bytes=' . strlen($png) . ' magic=' . substr(bin2hex(substr($png, 0, 8)), 0, 16));
$line('Esperado magic empieza por 89504e47 (PNG) si Endroid esta presente.');

$line('=== Fin smoke test Fase 3 ===');

<?php
declare(strict_types=1);

define('WP_USE_THEMES', false);
require '/var/www/html/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

use VFC\Core\Database\Schema;
use VFC\Core\Domain\Centro\Centro;
use VFC\Core\Domain\Centro\CentroRepository;
use VFC\Core\Domain\Edicion\Edicion;
use VFC\Core\Domain\Edicion\EdicionRepository;
use VFC\Core\Domain\Edicion\EstadoEdicion;
use VFC\Core\Domain\Matricula\MatriculaRepository;
use VFC\Core\Services\UsersService;
use VFC\Woo\Services\BeneficiarioSession;
use VFC\Woo\Services\CronService;
use VFC\Woo\Services\PercentageService;
use VFC\Woo\Services\SaldoService;

if (!class_exists('WooCommerce')) {
    fwrite(STDERR, "WooCommerce no esta cargado. Activa el plugin antes.\n");
    exit(1);
}
if (!class_exists(\VFC\Woo\Plugin::class)) {
    fwrite(STDERR, "vfc-woocommerce no esta cargado.\n");
    exit(1);
}

global $wpdb;
wp_set_current_user(1);

$line = static fn(string $msg) => fwrite(STDOUT, $msg . "\n");

$line("=== Smoke test Fase 2 ===");

update_option(PercentageService::OPTION_DEFAULT, 0);
update_option(PercentageService::OPTION_BLOQUEO_DIAS, 15);

$centroRepo = new CentroRepository();
$centro = $centroRepo->save(new Centro(
    id: null,
    nombre: 'IES Phase2 ' . substr(md5((string) microtime(true)), 0, 4),
    slug: 'ies-phase2-' . substr(md5((string) microtime(true)), 0, 6),
    cif: null, email: null, telefono: null, direccion: null, estado: 'activo'
));
$line("Centro id={$centro->id}");

$edRepo = new EdicionRepository();
$ed = $edRepo->save(new Edicion(
    id: null,
    centroId: (int) $centro->id,
    nombre: 'Viaje Phase2',
    fechaInicio: gmdate('Y-m-d'),
    fechaFin: gmdate('Y-m-d', time() + 30 * DAY_IN_SECONDS),
    estado: EstadoEdicion::BORRADOR
));
$edRepo->transitionTo((int) $ed->id, EstadoEdicion::PENDIENTE);
$edRepo->transitionTo((int) $ed->id, EstadoEdicion::APROBADA);
$edRepo->transitionTo((int) $ed->id, EstadoEdicion::ACTIVA);
$line("Edicion id={$ed->id} estado=activa");

$users = new UsersService();
$alumnoId = $users->createAlumno(
    'phase2-alumno-' . uniqid('', false) . '@example.com',
    'Alumno', 'Phase2', (int) $centro->id
);
$line("Alumno id={$alumnoId}");

$matrRepo = new MatriculaRepository();
$created = $matrRepo->create((int) $ed->id, $alumnoId, 'Phase2 alias');
$matricula = $created['matricula'];
$line("Matricula id={$matricula->id}");

$prod1 = new \WC_Product_Simple();
$prod1->set_name('Producto VFC A');
$prod1->set_regular_price('100');
$prod1->set_status('publish');
$prod1->save();
update_post_meta($prod1->get_id(), PercentageService::META_PRODUCT, 5.0);

$prod2 = new \WC_Product_Simple();
$prod2->set_name('Producto VFC B');
$prod2->set_regular_price('50');
$prod2->set_status('publish');
$prod2->save();
update_post_meta($prod2->get_id(), PercentageService::META_PRODUCT, 10.0);
$line("Productos: A={$prod1->get_id()} (5%), B={$prod2->get_id()} (10%)");

$pct = new PercentageService();
$line('PercentageService: A=' . $pct->forProduct($prod1->get_id()) . ' B=' . $pct->forProduct($prod2->get_id()));

$session = new BeneficiarioSession();
$session->issueCookie((int) $matricula->id);
$readId = $session->readMatriculaId();
$line('BeneficiarioSession: cookie=' . ($_COOKIE[BeneficiarioSession::COOKIE] ?? 'NULL'));
$line('BeneficiarioSession: readMatriculaId=' . var_export($readId, true) . ' (esperado ' . $matricula->id . ')');

$tampered = $_COOKIE[BeneficiarioSession::COOKIE];
$_COOKIE[BeneficiarioSession::COOKIE] = $tampered . 'x';
$line('BeneficiarioSession con cookie alterada: readMatriculaId=' . var_export($session->readMatriculaId(), true) . ' (esperado NULL)');
$_COOKIE[BeneficiarioSession::COOKIE] = $tampered;

$order = wc_create_order();
$order->add_product($prod1, 1);
$order->add_product($prod2, 2);
$order->update_meta_data('_vfc_matricula_id', (int) $matricula->id);
$order->update_meta_data('_vfc_alumno_user_id', $alumnoId);
$order->update_meta_data('_vfc_edicion_id', (int) $ed->id);
$order->calculate_totals();
$order->save();
$line("Order id={$order->get_id()} total={$order->get_total()}");

$saldo = new SaldoService();
$inserted = $saldo->abonarPedido($order);
$line("abonarPedido() insertados={$inserted} (esperado 2)");

$movsTable = Schema::table(Schema::TABLE_MOVIMIENTOS_SALDO);
$rows = $wpdb->get_results($wpdb->prepare(
    "SELECT id, line_item_id, importe_sin_iva, estado, fecha_liberacion FROM {$movsTable} WHERE order_id = %d ORDER BY id ASC",
    (int) $order->get_id()
), ARRAY_A);
foreach ($rows as $row) {
    $line("  movimiento id={$row['id']} item={$row['line_item_id']} importe={$row['importe_sin_iva']} estado={$row['estado']} libera={$row['fecha_liberacion']}");
}
$total = (float) $wpdb->get_var($wpdb->prepare(
    "SELECT SUM(importe_sin_iva) FROM {$movsTable} WHERE order_id = %d AND estado='BLOQUEADO'",
    (int) $order->get_id()
));
$expected = round(100 * 0.05 + 50 * 2 * 0.10, 4);
$line("Suma BLOQUEADO=" . number_format($total, 4) . " (esperado " . number_format($expected, 4) . ")");

$dup = $saldo->abonarPedido($order);
$line("abonarPedido() segunda llamada insertados={$dup} (esperado 0)");

$wpdb->query($wpdb->prepare(
    "UPDATE {$movsTable} SET fecha_liberacion = %s WHERE order_id = %d AND estado='BLOQUEADO'",
    gmdate('Y-m-d H:i:s', time() - DAY_IN_SECONDS),
    (int) $order->get_id()
));
$cron = new CronService();
$liberados = $cron->liberarBloqueados();
$line("liberarBloqueados() actualizados={$liberados}");
$rows = $wpdb->get_results($wpdb->prepare(
    "SELECT estado FROM {$movsTable} WHERE order_id = %d",
    (int) $order->get_id()
), ARRAY_A);
$estados = implode(',', array_column($rows, 'estado'));
$line("Estados tras cron: {$estados}");

$order = wc_get_order($order->get_id());
$items = array_values($order->get_items());
$itemA = $items[0];
$itemB = $items[1];

$refundParcial = wc_create_refund([
    'order_id' => $order->get_id(),
    'amount' => 50,
    'line_items' => [
        $itemA->get_id() => [
            'qty' => 0,
            'refund_total' => 50,
            'refund_tax' => [],
        ],
    ],
    'restock_items' => false,
]);
if (is_wp_error($refundParcial)) {
    $line('ERROR refund parcial: ' . $refundParcial->get_error_message());
} else {
    $line("Refund parcial id=" . $refundParcial->get_id() . " amount=" . $refundParcial->get_amount());
    do_action('woocommerce_order_refunded', $order->get_id(), $refundParcial->get_id());
}

$refundTotal = wc_create_refund([
    'order_id' => $order->get_id(),
    'amount' => 100,
    'line_items' => [
        $itemB->get_id() => [
            'qty' => 2,
            'refund_total' => 100,
            'refund_tax' => [],
        ],
    ],
    'restock_items' => false,
]);
if (is_wp_error($refundTotal)) {
    $line('ERROR refund total: ' . $refundTotal->get_error_message());
} else {
    $line("Refund total id=" . $refundTotal->get_id() . " amount=" . $refundTotal->get_amount());
    do_action('woocommerce_order_refunded', $order->get_id(), $refundTotal->get_id());
}

$rows = $wpdb->get_results($wpdb->prepare(
    "SELECT id, line_item_id, importe_sin_iva, estado, tipo, motivo FROM {$movsTable} WHERE order_id = %d ORDER BY id ASC",
    (int) $order->get_id()
), ARRAY_A);
$line("Movimientos finales:");
foreach ($rows as $row) {
    $line("  id={$row['id']} item={$row['line_item_id']} tipo={$row['tipo']} importe={$row['importe_sin_iva']} estado={$row['estado']} motivo=" . ($row['motivo'] ?? '-'));
}
$activeBalance = (float) $wpdb->get_var($wpdb->prepare(
    "SELECT COALESCE(SUM(importe_sin_iva),0) FROM {$movsTable}
     WHERE order_id = %d AND estado='CONFIRMADO' AND liquidacion_item_id IS NULL",
    (int) $order->get_id()
));
$line("Saldo neto activo (CONFIRMADO sin liquidar) tras refunds: " . number_format($activeBalance, 4));
$line("Esperado: 5*0.5 (item A residual) + 0 (item B totalmente revertido) = 2.5000");

$line("=== Fin smoke test Fase 2 ===");

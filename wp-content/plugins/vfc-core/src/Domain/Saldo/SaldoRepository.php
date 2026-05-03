<?php
declare(strict_types=1);

namespace VFC\Core\Domain\Saldo;

use VFC\Core\Database\Schema;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Consultas agregadas y paginadas sobre la tabla `wp_vfc_movimientos_saldo`.
 * Reutilizado por el portal y por reportes administrativos.
 */
final class SaldoRepository
{
    /**
     * Devuelve los importes agregados del alumno (opcionalmente filtrados por edicion).
     *
     * - `bloqueado`  = SUM(importe_sin_iva) WHERE estado='BLOQUEADO'
     * - `confirmado` = SUM(importe_sin_iva) WHERE estado='CONFIRMADO' AND liquidacion_item_id IS NULL
     * - `liquidado`  = SUM(importe_sin_iva) WHERE estado='CONFIRMADO' AND liquidacion_item_id IS NOT NULL
     * - `neto`       = bloqueado + confirmado (lo que sumara al alumno cuando se libere todo)
     *
     * @return array{bloqueado: float, confirmado: float, liquidado: float, neto: float}
     */
    public function saldoNeto(int $alumnoUserId, ?int $edicionId = null): array
    {
        global $wpdb;
        $table = Schema::table(Schema::TABLE_MOVIMIENTOS_SALDO);

        $where = "alumno_user_id = %d";
        $params = [$alumnoUserId];
        if ($edicionId !== null && $edicionId > 0) {
            $where .= " AND edicion_id = %d";
            $params[] = $edicionId;
        }

        $bloqueado = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(importe_sin_iva),0) FROM {$table} WHERE {$where} AND estado='BLOQUEADO'",
            ...$params
        ));
        $confirmado = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(importe_sin_iva),0) FROM {$table} WHERE {$where} AND estado='CONFIRMADO' AND liquidacion_item_id IS NULL",
            ...$params
        ));
        $liquidado = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(importe_sin_iva),0) FROM {$table} WHERE {$where} AND estado='CONFIRMADO' AND liquidacion_item_id IS NOT NULL",
            ...$params
        ));

        return [
            'bloqueado' => round($bloqueado, 4),
            'confirmado' => round($confirmado, 4),
            'liquidado' => round($liquidado, 4),
            'neto' => round($bloqueado + $confirmado, 4),
        ];
    }

    /**
     * Devuelve el historial paginado del alumno aplicando filtros opcionales.
     *
     * @param array{
     *     alumno_user_id?: int,
     *     edicion_id?: int,
     *     estado?: string,
     *     tipo?: string,
     *     from?: string,
     *     to?: string,
     *     per_page?: int,
     *     page?: int,
     *     order?: string
     * } $args
     *
     * @return array{items: array<int, array<string, mixed>>, total: int, page: int, per_page: int}
     */
    public function historial(array $args = []): array
    {
        global $wpdb;
        $table = Schema::table(Schema::TABLE_MOVIMIENTOS_SALDO);

        $where = ['1=1'];
        $params = [];

        if (!empty($args['alumno_user_id'])) {
            $where[] = 'alumno_user_id = %d';
            $params[] = (int) $args['alumno_user_id'];
        }
        if (!empty($args['edicion_id'])) {
            $where[] = 'edicion_id = %d';
            $params[] = (int) $args['edicion_id'];
        }
        if (!empty($args['estado']) && in_array((string) $args['estado'], ['BLOQUEADO', 'CONFIRMADO', 'REVERTIDO'], true)) {
            $where[] = 'estado = %s';
            $params[] = (string) $args['estado'];
        }
        if (!empty($args['tipo']) && in_array((string) $args['tipo'], ['abono', 'reverso'], true)) {
            $where[] = 'tipo = %s';
            $params[] = (string) $args['tipo'];
        }
        if (!empty($args['from'])) {
            $where[] = 'fecha_pedido >= %s';
            $params[] = (string) $args['from'];
        }
        if (!empty($args['to'])) {
            $where[] = 'fecha_pedido <= %s';
            $params[] = (string) $args['to'];
        }

        $perPage = max(1, min(100, (int) ($args['per_page'] ?? 20)));
        $page = max(1, (int) ($args['page'] ?? 1));
        $offset = ($page - 1) * $perPage;
        $order = strtoupper((string) ($args['order'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        $whereSql = implode(' AND ', $where);

        $totalSql = "SELECT COUNT(*) FROM {$table} WHERE {$whereSql}";
        $total = (int) ($params === []
            ? $wpdb->get_var($totalSql)
            : $wpdb->get_var($wpdb->prepare($totalSql, ...$params)));

        $itemsSql = "SELECT id, alumno_user_id, edicion_id, order_id, line_item_id, tipo, importe_sin_iva, estado, fecha_pedido, fecha_liberacion, fecha_confirmacion, motivo, liquidacion_item_id
                     FROM {$table}
                     WHERE {$whereSql}
                     ORDER BY id {$order}
                     LIMIT %d OFFSET %d";
        $allParams = array_merge($params, [$perPage, $offset]);
        $rows = $wpdb->get_results($wpdb->prepare($itemsSql, ...$allParams), ARRAY_A);

        $items = array_map(static function (array $row): array {
            return [
                'id' => (int) $row['id'],
                'alumno_user_id' => (int) $row['alumno_user_id'],
                'edicion_id' => (int) $row['edicion_id'],
                'order_id' => (int) $row['order_id'],
                'line_item_id' => $row['line_item_id'] !== null ? (int) $row['line_item_id'] : null,
                'tipo' => (string) $row['tipo'],
                'importe_sin_iva' => (float) $row['importe_sin_iva'],
                'estado' => (string) $row['estado'],
                'fecha_pedido' => (string) $row['fecha_pedido'],
                'fecha_liberacion' => $row['fecha_liberacion'],
                'fecha_confirmacion' => $row['fecha_confirmacion'],
                'motivo' => $row['motivo'],
                'liquidacion_item_id' => $row['liquidacion_item_id'] !== null ? (int) $row['liquidacion_item_id'] : null,
            ];
        }, (array) $rows);

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
        ];
    }
}

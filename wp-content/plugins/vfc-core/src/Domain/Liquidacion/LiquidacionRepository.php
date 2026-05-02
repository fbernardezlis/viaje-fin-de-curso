<?php
declare(strict_types=1);

namespace VFC\Core\Domain\Liquidacion;

use VFC\Core\Database\Schema;
use VFC\Core\Services\AuditService;

if (!defined('ABSPATH')) {
    exit;
}

final class LiquidacionRepository
{
    private const ENTIDAD = 'liquidacion';

    public function find(int $id): ?Liquidacion
    {
        global $wpdb;
        $table = Schema::table(Schema::TABLE_LIQUIDACIONES);
        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id),
            ARRAY_A
        );
        return is_array($row) ? Liquidacion::fromRow($row) : null;
    }

    /**
     * @return array<int, Liquidacion>
     */
    public function listByCentro(int $centroId): array
    {
        global $wpdb;
        $table = Schema::table(Schema::TABLE_LIQUIDACIONES);
        $rows = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$table} WHERE centro_id = %d ORDER BY fecha DESC, id DESC", $centroId),
            ARRAY_A
        );
        $out = [];
        foreach ((array) $rows as $row) {
            $out[] = Liquidacion::fromRow($row);
        }
        return $out;
    }

    /**
     * @return array<int, Liquidacion>
     */
    public function listAll(): array
    {
        global $wpdb;
        $table = Schema::table(Schema::TABLE_LIQUIDACIONES);
        $rows = $wpdb->get_results("SELECT * FROM {$table} ORDER BY fecha DESC, id DESC", ARRAY_A);
        $out = [];
        foreach ((array) $rows as $row) {
            $out[] = Liquidacion::fromRow($row);
        }
        return $out;
    }

    /**
     * Pendientes de liquidar para un centro: agrupa por alumno+edición la suma de movimientos
     * `estado=CONFIRMADO` sin `liquidacion_item_id`.
     *
     * @return array<int, array{alumno_user_id: int, edicion_id: int, importe: float, n_movimientos: int}>
     */
    public function pendientesPorCentro(int $centroId): array
    {
        global $wpdb;
        $movs = Schema::table(Schema::TABLE_MOVIMIENTOS_SALDO);
        $eds = Schema::table(Schema::TABLE_EDICIONES);

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT m.alumno_user_id, m.edicion_id,
                    SUM(m.importe_sin_iva) AS importe,
                    COUNT(*) AS n_movimientos
             FROM {$movs} m
             INNER JOIN {$eds} e ON e.id = m.edicion_id
             WHERE e.centro_id = %d
               AND m.estado = 'CONFIRMADO'
               AND m.liquidacion_item_id IS NULL
             GROUP BY m.alumno_user_id, m.edicion_id
             HAVING importe > 0",
            $centroId
        ), ARRAY_A);

        $out = [];
        foreach ((array) $rows as $row) {
            $out[] = [
                'alumno_user_id' => (int) $row['alumno_user_id'],
                'edicion_id' => (int) $row['edicion_id'],
                'importe' => (float) $row['importe'],
                'n_movimientos' => (int) $row['n_movimientos'],
            ];
        }
        return $out;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function itemsForLiquidacion(int $liquidacionId): array
    {
        global $wpdb;
        $items = Schema::table(Schema::TABLE_LIQUIDACION_ITEMS);
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$items} WHERE liquidacion_id = %d ORDER BY id ASC",
            $liquidacionId
        ), ARRAY_A);
        return array_map(static fn($r) => is_array($r) ? $r : [], (array) $rows);
    }

    /**
     * Crea una liquidación con sus items y marca los movimientos correspondientes como liquidados.
     *
     * @param array<int, array{alumno_user_id: int, edicion_id: int}> $seleccion claves alumno+edicion
     * @return Liquidacion
     * @throws \RuntimeException
     */
    public function create(int $centroId, string $fecha, ?string $referencia, ?string $notas, array $seleccion): Liquidacion
    {
        global $wpdb;
        if ($centroId <= 0) {
            throw new \RuntimeException(__('Centro obligatorio.', 'vfc-core'));
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) !== 1) {
            throw new \RuntimeException(__('Fecha inválida (formato YYYY-MM-DD).', 'vfc-core'));
        }
        if ($seleccion === []) {
            throw new \RuntimeException(__('No has seleccionado movimientos para liquidar.', 'vfc-core'));
        }

        $movs = Schema::table(Schema::TABLE_MOVIMIENTOS_SALDO);
        $eds = Schema::table(Schema::TABLE_EDICIONES);
        $liqs = Schema::table(Schema::TABLE_LIQUIDACIONES);
        $items = Schema::table(Schema::TABLE_LIQUIDACION_ITEMS);

        $wpdb->query('START TRANSACTION');

        try {
            $now = current_time('mysql', true);
            $insert = $wpdb->insert(
                $liqs,
                [
                    'centro_id' => $centroId,
                    'importe' => 0,
                    'fecha' => $fecha,
                    'referencia' => $referencia,
                    'notas' => $notas,
                    'registrada_por' => get_current_user_id() ?: null,
                    'estado' => 'registrada',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                ['%d', '%f', '%s', '%s', '%s', '%d', '%s', '%s', '%s']
            );
            if ($insert === false) {
                throw new \RuntimeException(__('No se pudo crear la cabecera de liquidación.', 'vfc-core'));
            }
            $liquidacionId = (int) $wpdb->insert_id;
            $totalImporte = 0.0;

            foreach ($seleccion as $sel) {
                $alumnoId = (int) ($sel['alumno_user_id'] ?? 0);
                $edicionId = (int) ($sel['edicion_id'] ?? 0);
                if ($alumnoId <= 0 || $edicionId <= 0) {
                    continue;
                }

                $belongs = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$eds} WHERE id = %d AND centro_id = %d",
                    $edicionId,
                    $centroId
                ));
                if ($belongs === 0) {
                    continue;
                }

                $importe = (float) $wpdb->get_var($wpdb->prepare(
                    "SELECT COALESCE(SUM(importe_sin_iva), 0)
                     FROM {$movs}
                     WHERE alumno_user_id = %d
                       AND edicion_id = %d
                       AND estado = 'CONFIRMADO'
                       AND liquidacion_item_id IS NULL",
                    $alumnoId,
                    $edicionId
                ));
                if ($importe <= 0) {
                    continue;
                }

                $itemInsert = $wpdb->insert(
                    $items,
                    [
                        'liquidacion_id' => $liquidacionId,
                        'alumno_user_id' => $alumnoId,
                        'edicion_id' => $edicionId,
                        'importe' => $importe,
                        'created_at' => $now,
                    ],
                    ['%d', '%d', '%d', '%f', '%s']
                );
                if ($itemInsert === false) {
                    throw new \RuntimeException(__('No se pudo crear un item de liquidación.', 'vfc-core'));
                }
                $itemId = (int) $wpdb->insert_id;

                $update = $wpdb->query($wpdb->prepare(
                    "UPDATE {$movs} SET liquidacion_item_id = %d, updated_at = %s
                     WHERE alumno_user_id = %d
                       AND edicion_id = %d
                       AND estado = 'CONFIRMADO'
                       AND liquidacion_item_id IS NULL",
                    $itemId,
                    $now,
                    $alumnoId,
                    $edicionId
                ));
                if ($update === false) {
                    throw new \RuntimeException(__('No se pudieron marcar los movimientos.', 'vfc-core'));
                }

                $totalImporte += $importe;
            }

            if ($totalImporte <= 0) {
                throw new \RuntimeException(__('No hay saldos confirmados pendientes para los criterios seleccionados.', 'vfc-core'));
            }

            $wpdb->update(
                $liqs,
                ['importe' => $totalImporte, 'updated_at' => current_time('mysql', true)],
                ['id' => $liquidacionId],
                ['%f', '%s'],
                ['%d']
            );

            $wpdb->query('COMMIT');
        } catch (\Throwable $e) {
            $wpdb->query('ROLLBACK');
            throw new \RuntimeException($e->getMessage(), 0, $e);
        }

        $saved = $this->find($liquidacionId);
        if ($saved === null) {
            throw new \RuntimeException(__('Liquidación no encontrada tras crear.', 'vfc-core'));
        }

        AuditService::log('create', self::ENTIDAD, $liquidacionId, [
            'centro_id' => $centroId,
            'importe' => $totalImporte,
            'fecha' => $fecha,
            'referencia' => $referencia,
        ], $centroId);

        return $saved;
    }
}

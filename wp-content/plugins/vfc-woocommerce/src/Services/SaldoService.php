<?php
declare(strict_types=1);

namespace VFC\Woo\Services;

use VFC\Core\Database\Schema;
use VFC\Core\Domain\Edicion\EdicionRepository;
use VFC\Core\Domain\Matricula\MatriculaRepository;
use VFC\Core\Services\AuditService;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Inserta y revierte movimientos de saldo a partir de pedidos / refunds de WooCommerce.
 *
 * Modelo:
 *  - Abono BLOQUEADO al completar pedido (con fecha_liberacion = fecha_pedido + N dias).
 *  - Refunds:
 *      * Si el abono original NO esta liquidado: se ajusta in-place (reduce importe; si 0 -> REVERTIDO).
 *      * Si el abono original YA esta liquidado: se inserta tipo=reverso (importe negativo) CONFIRMADO,
 *        que queda como saldo negativo libre y compensa en la siguiente liquidacion.
 *
 *  Idempotencia: meta `_vfc_movimientos_creados` (abono) y `_vfc_refunds_processed` (refund ids).
 */
final class SaldoService
{
    public const ENTIDAD = 'movimiento_saldo';
    private const META_ABONOS_DONE = '_vfc_movimientos_creados';
    private const META_REFUNDS_DONE = '_vfc_refunds_processed';

    private PercentageService $percent;
    private MatriculaRepository $matriculas;
    private EdicionRepository $ediciones;

    public function __construct(
        ?PercentageService $percent = null,
        ?MatriculaRepository $matriculas = null,
        ?EdicionRepository $ediciones = null
    ) {
        $this->percent = $percent ?? new PercentageService();
        $this->matriculas = $matriculas ?? new MatriculaRepository();
        $this->ediciones = $ediciones ?? new EdicionRepository();
    }

    /**
     * Crea movimientos BLOQUEADO para cada linea del pedido completado, si hay matricula asociada.
     *
     * @return int numero de movimientos insertados
     */
    public function abonarPedido(\WC_Order $order): int
    {
        if ((int) $order->get_meta(self::META_ABONOS_DONE) === 1) {
            return 0;
        }
        $matriculaId = (int) $order->get_meta('_vfc_matricula_id');
        if ($matriculaId <= 0) {
            return 0;
        }

        $matricula = $this->matriculas->find($matriculaId);
        if ($matricula === null) {
            return 0;
        }
        $edicion = $this->ediciones->find($matricula->edicionId);
        if ($edicion === null) {
            return 0;
        }

        global $wpdb;
        $table = Schema::table(Schema::TABLE_MOVIMIENTOS_SALDO);
        $now = current_time('mysql', true);
        $fechaPedido = $this->orderDate($order, $now);
        $bloqueoDias = $this->percent->bloqueoDias();
        $fechaLiberacion = self::addDays($fechaPedido, $bloqueoDias);

        $count = 0;
        foreach ($order->get_items() as $itemId => $item) {
            if (!$item instanceof \WC_Order_Item_Product) {
                continue;
            }
            $productId = (int) ($item->get_variation_id() ?: $item->get_product_id());
            $subtotal = (float) $item->get_subtotal();
            if ($subtotal <= 0 || $productId <= 0) {
                continue;
            }
            $pct = $this->percent->forProduct($productId);
            if ($pct <= 0) {
                continue;
            }
            $importe = round($subtotal * $pct / 100, 4);
            if ($importe <= 0) {
                continue;
            }

            $wpdb->insert(
                $table,
                [
                    'alumno_user_id' => (int) $matricula->alumnoUserId,
                    'edicion_id' => (int) $matricula->edicionId,
                    'order_id' => (int) $order->get_id(),
                    'line_item_id' => (int) $itemId,
                    'tipo' => 'abono',
                    'importe_sin_iva' => $importe,
                    'estado' => 'BLOQUEADO',
                    'fecha_pedido' => $fechaPedido,
                    'fecha_liberacion' => $fechaLiberacion,
                    'motivo' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                ['%d', '%d', '%d', '%d', '%s', '%f', '%s', '%s', '%s', '%s', '%s', '%s']
            );
            $count++;
        }

        $order->update_meta_data(self::META_ABONOS_DONE, 1);
        $order->save();

        AuditService::log(
            'abono_pedido',
            self::ENTIDAD,
            (int) $order->get_id(),
            [
                'order_id' => (int) $order->get_id(),
                'matricula_id' => $matriculaId,
                'movimientos' => $count,
                'fecha_liberacion' => $fechaLiberacion,
            ],
            $edicion->centroId
        );

        return $count;
    }

    /**
     * Procesa un reembolso ajustando los movimientos asociados al pedido original.
     *
     * @return int filas afectadas (updates + inserts)
     */
    public function revertirRefund(\WC_Order_Refund $refund): int
    {
        $parentId = (int) $refund->get_parent_id();
        if ($parentId <= 0) {
            return 0;
        }
        $order = wc_get_order($parentId);
        if (!$order instanceof \WC_Order) {
            return 0;
        }
        $refundId = (int) $refund->get_id();
        if ($this->refundAlreadyProcessed($order, $refundId)) {
            return 0;
        }
        $matriculaId = (int) $order->get_meta('_vfc_matricula_id');
        if ($matriculaId <= 0) {
            return 0;
        }

        global $wpdb;
        $table = Schema::table(Schema::TABLE_MOVIMIENTOS_SALDO);
        $now = current_time('mysql', true);
        $affected = 0;

        foreach ($refund->get_items() as $refItem) {
            if (!$refItem instanceof \WC_Order_Item_Product) {
                continue;
            }
            $origItemId = (int) $refItem->get_meta('_refunded_item_id');
            if ($origItemId <= 0) {
                continue;
            }
            $origItem = $order->get_item($origItemId);
            if (!$origItem instanceof \WC_Order_Item_Product) {
                continue;
            }
            $origSubtotal = (float) $origItem->get_subtotal();
            if ($origSubtotal <= 0) {
                continue;
            }
            $refundSubtotal = abs((float) $refItem->get_subtotal());
            if ($refundSubtotal <= 0) {
                continue;
            }
            $factor = min(1.0, $refundSubtotal / $origSubtotal);

            $abono = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$table} WHERE order_id = %d AND line_item_id = %d AND tipo = 'abono' LIMIT 1",
                (int) $order->get_id(),
                $origItemId
            ), ARRAY_A);
            if (!$abono) {
                continue;
            }

            $importeAbono = (float) $abono['importe_sin_iva'];
            $reversoImporte = -round($importeAbono * $factor, 4);
            if ($reversoImporte === 0.0) {
                continue;
            }

            $isLiquidated = !empty($abono['liquidacion_item_id']);

            if (!$isLiquidated) {
                $nuevoImporte = round($importeAbono + $reversoImporte, 4);
                if ($nuevoImporte <= 0.0001) {
                    $wpdb->update(
                        $table,
                        [
                            'estado' => 'REVERTIDO',
                            'importe_sin_iva' => 0,
                            'motivo' => 'refund:' . $refundId,
                            'updated_at' => $now,
                        ],
                        ['id' => (int) $abono['id']],
                        ['%s', '%f', '%s', '%s'],
                        ['%d']
                    );
                } else {
                    $wpdb->update(
                        $table,
                        [
                            'importe_sin_iva' => $nuevoImporte,
                            'motivo' => 'refund_parcial:' . $refundId,
                            'updated_at' => $now,
                        ],
                        ['id' => (int) $abono['id']],
                        ['%f', '%s', '%s'],
                        ['%d']
                    );
                }
                $affected++;
            } else {
                $wpdb->insert(
                    $table,
                    [
                        'alumno_user_id' => (int) $abono['alumno_user_id'],
                        'edicion_id' => (int) $abono['edicion_id'],
                        'order_id' => (int) $order->get_id(),
                        'line_item_id' => $origItemId,
                        'tipo' => 'reverso',
                        'importe_sin_iva' => $reversoImporte,
                        'estado' => 'CONFIRMADO',
                        'fecha_pedido' => (string) $abono['fecha_pedido'],
                        'fecha_confirmacion' => $now,
                        'motivo' => 'refund_post_liquidacion:' . $refundId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    ['%d', '%d', '%d', '%d', '%s', '%f', '%s', '%s', '%s', '%s', '%s', '%s']
                );
                $affected++;
            }
        }

        $this->markRefundProcessed($order, $refundId);

        AuditService::log(
            'refund_pedido',
            self::ENTIDAD,
            (int) $order->get_id(),
            [
                'order_id' => (int) $order->get_id(),
                'refund_id' => $refundId,
                'matricula_id' => $matriculaId,
                'movimientos_afectados' => $affected,
            ]
        );

        return $affected;
    }

    private function refundAlreadyProcessed(\WC_Order $order, int $refundId): bool
    {
        $list = (array) $order->get_meta(self::META_REFUNDS_DONE);
        return in_array($refundId, array_map('intval', $list), true);
    }

    private function markRefundProcessed(\WC_Order $order, int $refundId): void
    {
        $list = (array) $order->get_meta(self::META_REFUNDS_DONE);
        $list[] = $refundId;
        $order->update_meta_data(self::META_REFUNDS_DONE, array_values(array_unique(array_map('intval', $list))));
        $order->save();
    }

    private function orderDate(\WC_Order $order, string $fallback): string
    {
        $created = $order->get_date_created();
        if ($created instanceof \WC_DateTime) {
            return gmdate('Y-m-d H:i:s', $created->getTimestamp());
        }
        return $fallback;
    }

    private static function addDays(string $datetime, int $days): string
    {
        $ts = strtotime($datetime);
        if ($ts === false) {
            $ts = time();
        }
        return gmdate('Y-m-d H:i:s', $ts + $days * DAY_IN_SECONDS);
    }
}

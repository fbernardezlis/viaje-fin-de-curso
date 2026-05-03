<?php
declare(strict_types=1);

namespace VFC\Woo\WooHooks;

use VFC\Woo\Services\BeneficiarioSession;
use VFC\Woo\Services\SaldoService;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Conecta los hooks de WooCommerce con el SaldoService:
 *  - Snapshot de la matricula activa al crear el pedido.
 *  - Abono al completarse el pedido.
 *  - Reverso al reembolsarse (parcial o total).
 */
final class OrderHooks
{
    private BeneficiarioSession $session;
    private SaldoService $saldo;

    public function __construct(?BeneficiarioSession $session = null, ?SaldoService $saldo = null)
    {
        $this->session = $session ?? new BeneficiarioSession();
        $this->saldo = $saldo ?? new SaldoService();
    }

    public function register(): void
    {
        add_action('woocommerce_checkout_create_order', [$this, 'snapshotMatricula'], 20, 2);
        add_action('woocommerce_order_status_completed', [$this, 'onCompleted'], 10, 1);
        add_action('woocommerce_order_refunded', [$this, 'onRefunded'], 10, 2);
    }

    /**
     * Adjunta los datos de la matricula activa al pedido (snapshot independiente de la cookie).
     *
     * @param mixed $data ignorado
     */
    public function snapshotMatricula(\WC_Order $order, $data): void
    {
        $matricula = $this->session->readMatricula();
        if ($matricula === null) {
            return;
        }
        $order->update_meta_data('_vfc_matricula_id', (int) $matricula->id);
        $order->update_meta_data('_vfc_alumno_user_id', (int) $matricula->alumnoUserId);
        $order->update_meta_data('_vfc_edicion_id', (int) $matricula->edicionId);
        $order->update_meta_data('_vfc_alias', $matricula->alias);
    }

    public function onCompleted(int $orderId): void
    {
        $order = wc_get_order($orderId);
        if (!$order instanceof \WC_Order) {
            return;
        }
        $this->saldo->abonarPedido($order);
    }

    /**
     * @param int $orderId pedido original
     * @param int $refundId id del refund creado
     */
    public function onRefunded(int $orderId, int $refundId): void
    {
        $refund = wc_get_order($refundId);
        if (!$refund instanceof \WC_Order_Refund) {
            return;
        }
        $this->saldo->revertirRefund($refund);
    }
}

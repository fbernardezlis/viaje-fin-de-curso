<?php
declare(strict_types=1);

namespace VFC\Woo\WooHooks;

use VFC\Woo\Services\BeneficiarioSession;
use VFC\Woo\Services\PricingService;
use VFC\Woo\Services\SaldoService;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Conecta los hooks de WooCommerce con el SaldoService:
 *  - Snapshot de la matricula activa al crear el pedido.
 *  - Desglose VFC en lineas (checkout clasico, nuevos items y checkout Blocks / API).
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
        add_action('woocommerce_checkout_create_order_line_item', [$this, 'attachLinePricingCheckout'], 15, 4);
        add_action('woocommerce_new_order_item', [$this, 'attachLinePricingNewItem'], 10, 3);
        add_action('woocommerce_checkout_order_processed', [$this, 'attachLinePricingOrderProcessed'], 20, 2);
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

    /**
     * @param mixed $cart_item_key
     * @param mixed $values
     */
    public function attachLinePricingCheckout(\WC_Order_Item_Product $item, $cart_item_key, $values, \WC_Order $order): void
    {
        unset($order);
        if (!isset($values['data']) || !$values['data'] instanceof \WC_Product) {
            return;
        }
        PricingService::attachBreakdownToLineItem($item, $values['data']);
    }

    /**
     * @param int $item_id
     * @param mixed $item
     * @param int $order_id
     */
    public function attachLinePricingNewItem($item_id, $item, $order_id): void
    {
        unset($item_id, $order_id);
        if (!$item instanceof \WC_Order_Item_Product) {
            return;
        }
        $existing = $item->get_meta(PricingService::LINE_META_BASE_UNIT, true);
        if ($existing !== '' && $existing !== false && is_numeric($existing)) {
            return;
        }
        $product = $item->get_product();
        if (!$product instanceof \WC_Product) {
            return;
        }
        PricingService::attachBreakdownToLineItem($item, $product);
        $item->save();
    }

    /**
     * Respaldo tras finalizar checkout (p. ej. Blocks): rellena desglose si alguna linea quedo sin metadatos VFC.
     *
     * @param \WC_Order|int $order Pedido o ID (según versión de WooCommerce).
     * @param mixed         $posted_data ignorado
     */
    public function attachLinePricingOrderProcessed($order, $posted_data = null): void
    {
        unset($posted_data);
        if (!$order instanceof \WC_Order) {
            if (!is_numeric($order)) {
                return;
            }
            $loaded = wc_get_order((int) $order);
            if (!$loaded instanceof \WC_Order) {
                return;
            }
            $order = $loaded;
        }

        $changed = false;
        foreach ($order->get_items() as $item) {
            if (!$item instanceof \WC_Order_Item_Product) {
                continue;
            }
            $existing = $item->get_meta(PricingService::LINE_META_BASE_UNIT, true);
            if ($existing !== '' && $existing !== false && is_numeric($existing)) {
                continue;
            }
            $product = $item->get_product();
            if (!$product instanceof \WC_Product) {
                continue;
            }
            PricingService::attachBreakdownToLineItem($item, $product);
            $changed = true;
        }
        if ($changed) {
            $order->save();
        }
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

<?php
declare(strict_types=1);

namespace VFC\Woo\Frontend;

use VFC\Woo\Services\BeneficiarioSession;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Avisa en checkout cuando NO hay beneficiario activo: la compra no se vinculara a ningun
 * alumno. Mensaje informativo, no bloquea.
 */
final class CheckoutNotice
{
    private BeneficiarioSession $session;

    public function __construct(?BeneficiarioSession $session = null)
    {
        $this->session = $session ?? new BeneficiarioSession();
    }

    public function register(): void
    {
        add_action('woocommerce_review_order_before_payment', [$this, 'render']);
        add_action('woocommerce_before_cart', [$this, 'renderCart']);
    }

    public function render(): void
    {
        if ($this->session->readMatricula() !== null) {
            return;
        }
        wc_print_notice(
            __('Esta compra no se vinculará a ningún alumno. Si quieres que parte del importe se abone al saldo de un alumno, abre primero el enlace QR del alumno y vuelve a iniciar la compra.', 'vfc-woocommerce'),
            'notice'
        );
    }

    public function renderCart(): void
    {
        if ($this->session->readMatricula() !== null) {
            return;
        }
        wc_print_notice(
            __('Estás navegando sin alumno asociado. Las compras desde aquí no se vincularán a ningún saldo.', 'vfc-woocommerce'),
            'notice'
        );
    }
}

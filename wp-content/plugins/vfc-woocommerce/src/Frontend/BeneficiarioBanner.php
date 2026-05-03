<?php
declare(strict_types=1);

namespace VFC\Woo\Frontend;

use VFC\Core\Domain\Centro\CentroRepository;
use VFC\Core\Domain\Edicion\EdicionRepository;
use VFC\Woo\Services\BeneficiarioSession;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Banner sticky superior con "Comprando para {alias} ({centro})" y boton "Salir".
 */
final class BeneficiarioBanner
{
    private BeneficiarioSession $session;
    private EdicionRepository $ediciones;
    private CentroRepository $centros;

    public function __construct(
        ?BeneficiarioSession $session = null,
        ?EdicionRepository $ediciones = null,
        ?CentroRepository $centros = null
    ) {
        $this->session = $session ?? new BeneficiarioSession();
        $this->ediciones = $ediciones ?? new EdicionRepository();
        $this->centros = $centros ?? new CentroRepository();
    }

    public function register(): void
    {
        add_action('wp_head', [$this, 'styles']);
        add_action('wp_body_open', [$this, 'render']);
        add_action('wp_footer', [$this, 'renderFallback']);
    }

    public function styles(): void
    {
        if (is_admin()) {
            return;
        }
        echo '<style>
            .vfc-beneficiario-banner{position:sticky;top:0;z-index:9999;background:#0a3d62;color:#fff;padding:.6rem 1rem;font-size:.95rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem;}
            .vfc-beneficiario-banner strong{margin-right:.4rem;}
            .vfc-beneficiario-banner form{margin:0;}
            .vfc-beneficiario-banner button{background:#fff;color:#0a3d62;border:0;padding:.3rem .8rem;border-radius:4px;cursor:pointer;font-weight:600;}
            .vfc-beneficiario-banner button:hover{opacity:.9;}
        </style>';
    }

    public function render(): void
    {
        if (is_admin()) {
            return;
        }
        $matricula = $this->session->readMatricula();
        if ($matricula === null) {
            return;
        }
        $edicion = $this->ediciones->find($matricula->edicionId);
        $centro = $edicion !== null ? $this->centros->find($edicion->centroId) : null;

        $logoutUrl = esc_url(home_url('/qr-logout'));
        printf(
            '<div class="vfc-beneficiario-banner" role="status" aria-live="polite">'
            . '<div><strong>%s</strong> %s — <em>%s</em>%s</div>'
            . '<form method="post" action="%s"><button type="submit">%s</button></form>'
            . '</div>',
            esc_html__('Comprando para:', 'vfc-woocommerce'),
            esc_html($matricula->alias),
            esc_html($edicion?->nombre ?? '—'),
            $centro !== null ? ' &middot; ' . esc_html($centro->nombre) : '',
            $logoutUrl,
            esc_html__('Dejar de comprar para este alumno', 'vfc-woocommerce')
        );

        $GLOBALS['vfc_banner_rendered'] = true;
    }

    public function renderFallback(): void
    {
        if (!empty($GLOBALS['vfc_banner_rendered'])) {
            return;
        }
        $this->render();
    }
}

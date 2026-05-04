<?php
declare(strict_types=1);

namespace VFC\Core\Privacy;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Anade texto sugerido al editor de politica de privacidad de WordPress (Herramientas → Privacidad).
 */
final class PrivacyGuide
{
    public static function register(): void
    {
        if (!function_exists('wp_add_privacy_policy_content')) {
            return;
        }
        add_action('admin_init', [self::class, 'addSuggestedContent']);
    }

    public static function addSuggestedContent(): void
    {
        $html = '<div class="privacy-policy-tutorial"><h3>' . esc_html__('Viaje fin de curso (VFC Core / WooCommerce)', 'vfc-core') . '</h3>'
            . '<p><strong>' . esc_html__('Cookie de vinculacion de compras (funcional)', 'vfc-core') . '</strong> '
            . esc_html__(
                'Si el visitante escanea un QR o acepta vincular compras a un alumno, puede guardarse la cookie tecnica «vfc_beneficiario» (aprox. 30 dias, HttpOnly, SameSite=Lax) para recordar a que matricula aplican los pedidos y el saldo. Requiere consentimiento de cookies funcionales salvo que el responsable la clasifique como estrictamente necesaria segun su caso.',
                'vfc-core'
            )
            . '</p>'
            . '<p><strong>' . esc_html__('Cookie de preferencias RGPD', 'vfc-core') . '</strong> '
            . esc_html__(
                'La cookie «vfc_consent» almacena de forma firmada si ha aceptado categorias opcionales (funcional, analitica). Es HttpOnly.',
                'vfc-core'
            )
            . '</p>'
            . '<p><strong>' . esc_html__('Portal / sesion WordPress', 'vfc-core') . '</strong> '
            . esc_html__(
                'Las sesiones de acceso al portal (/portal/*) y WordPress pueden usar cookies de autenticacion estandar. WooCommerce puede usar cookies de carrito y sesion de tienda.',
                'vfc-core'
            )
            . '</p>'
            . '<p><em>' . esc_html__(
                'Este texto es orientativo: debe revisarlo quien sea responsable del tratamiento con asesoramiento juridico.',
                'vfc-core'
            ) . '</em></p></div>';

        wp_add_privacy_policy_content('vfc-core', $html);
    }
}

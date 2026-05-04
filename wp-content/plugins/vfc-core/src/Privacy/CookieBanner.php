<?php
declare(strict_types=1);

namespace VFC\Core\Privacy;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Barra de consentimiento de cookies en el front (wp_footer).
 */
final class CookieBanner
{
    public function register(): void
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue'], 20);
        add_action('wp_footer', [$this, 'render'], 5);
    }

    public function enqueue(): void
    {
        if (!$this->shouldDisplay()) {
            return;
        }
        if (is_admin() || is_feed() || is_embed()) {
            return;
        }

        wp_enqueue_style(
            'vfc-privacy-banner',
            VFC_CORE_URL . 'assets/privacy/cookie-banner.css',
            [],
            VFC_CORE_VERSION
        );
        wp_enqueue_script(
            'vfc-privacy-banner',
            VFC_CORE_URL . 'assets/privacy/cookie-banner.js',
            [],
            VFC_CORE_VERSION,
            true
        );
        wp_localize_script('vfc-privacy-banner', 'VFC_PRIVACY', [
            'restUrl' => esc_url_raw(rest_url('vfc/v1/privacy/consent')),
            'nonce' => wp_create_nonce('wp_rest'),
        ]);
    }

    public function render(): void
    {
        if (!$this->shouldDisplay()) {
            return;
        }
        if (is_admin() || is_feed() || is_embed()) {
            return;
        }

        $urlCookies = LegalPages::urlCookies();
        $urlPrivacy = LegalPages::urlPrivacy();
        $linksHtml = '';
        if ($urlCookies !== '') {
            $linksHtml .= sprintf(
                '<a href="%s">%s</a>',
                esc_url($urlCookies),
                esc_html__('Política de cookies', 'vfc-core')
            );
        } else {
            $linksHtml .= esc_html__('Política de cookies (publique la página de borrador en el escritorio)', 'vfc-core');
        }
        if ($urlPrivacy !== '') {
            $linksHtml .= ($linksHtml !== '' ? ' · ' : '')
                . sprintf(
                    '<a href="%s">%s</a>',
                    esc_url($urlPrivacy),
                    esc_html__('Privacidad', 'vfc-core')
                );
        }

        echo '<div id="vfc-cookie-banner" role="dialog" aria-modal="false" aria-labelledby="vfc-cookie-banner-title" aria-live="polite">';
        echo '<div class="vfc-cookie-inner">';
        echo '<p id="vfc-cookie-banner-title"><strong>' . esc_html__('Uso de cookies', 'vfc-core') . '</strong></p>';
        echo '<p>' . esc_html__(
            'Usamos cookies necesarias para el funcionamiento del sitio. Con su permiso, usamos también cookies funcionales para vincular compras a un alumno (QR) y, si las activa, cookies analíticas.',
            'vfc-core'
        ) . ' ' . wp_kses(
            $linksHtml,
            [
                'a' => [
                    'href' => [],
                ],
            ]
        ) . '</p>';

        echo '<div class="vfc-cookie-actions">';
        echo '<button type="button" class="vfc-cookie-btn-secondary" data-vfc-consent="necessary">' . esc_html__('Solo necesarias', 'vfc-core') . '</button>';
        echo '<button type="button" class="vfc-cookie-btn-primary" data-vfc-consent="functional">' . esc_html__('Necesarias y funcionales', 'vfc-core') . '</button>';
        echo '<button type="button" class="vfc-cookie-btn-ghost" id="vfc-cookie-open-prefs">' . esc_html__('Configurar', 'vfc-core') . '</button>';
        echo '</div>';

        echo '<div id="vfc-cookie-prefs" hidden>';
        echo '<p>' . esc_html__('Active o desactive las categorías opcionales:', 'vfc-core') . '</p>';
        echo '<label><input type="checkbox" id="vfc-cookie-opt-functional" checked> ' . esc_html__('Funcionales (vinculación de compras, vfc_beneficiario)', 'vfc-core') . '</label>';
        echo '<label><input type="checkbox" id="vfc-cookie-opt-analytics"> ' . esc_html__('Analíticas (solo si el sitio carga herramientas de medición compatibles)', 'vfc-core') . '</label>';
        echo '<div class="vfc-cookie-actions">';
        echo '<button type="button" class="vfc-cookie-btn-primary" id="vfc-cookie-save-prefs">' . esc_html__('Guardar preferencias', 'vfc-core') . '</button>';
        echo '<button type="button" class="vfc-cookie-btn-ghost" id="vfc-cookie-close-prefs">' . esc_html__('Cerrar', 'vfc-core') . '</button>';
        echo '<button type="button" class="vfc-cookie-btn-ghost" id="vfc-cookie-revoke">' . esc_html__('Revocar todo lo opcional', 'vfc-core') . '</button>';
        echo '</div></div>';

        echo '</div></div>';
    }

    private function shouldDisplay(): bool
    {
        if (!apply_filters('vfc_privacy_show_cookie_banner', true)) {
            return false;
        }
        if (isset($_GET['vfc_cookie_prefs']) && (string) $_GET['vfc_cookie_prefs'] === '1') {
            return true;
        }
        return ConsentService::readState() === null;
    }
}

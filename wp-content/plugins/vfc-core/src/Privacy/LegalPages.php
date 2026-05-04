<?php
declare(strict_types=1);

namespace VFC\Core\Privacy;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Crea borradores de paginas legales (una sola vez). Deben revisarse con asesoramiento juridico antes de publicar.
 */
final class LegalPages
{
    private const OPTION_FLAG = 'vfc_legal_pages_installed';
    private const OPT_PRIVACY = 'vfc_legal_privacy_post_id';
    private const OPT_AVISO = 'vfc_legal_aviso_post_id';
    private const OPT_COOKIES = 'vfc_legal_cookies_post_id';

    public static function installOnce(): void
    {
        if (get_option(self::OPTION_FLAG)) {
            return;
        }

        $privacy = self::insertPage(
            __('Política de privacidad (VFC)', 'vfc-core'),
            'politica-privacidad-vfc',
            self::defaultPrivacyContent()
        );
        $aviso = self::insertPage(
            __('Aviso legal (VFC)', 'vfc-core'),
            'aviso-legal-vfc',
            self::defaultAvisoContent()
        );
        $cookies = self::insertPage(
            __('Política de cookies (VFC)', 'vfc-core'),
            'politica-cookies-vfc',
            self::defaultCookiesContent()
        );

        if ($privacy > 0) {
            update_option(self::OPT_PRIVACY, $privacy);
        }
        if ($aviso > 0) {
            update_option(self::OPT_AVISO, $aviso);
        }
        if ($cookies > 0) {
            update_option(self::OPT_COOKIES, $cookies);
        }

        update_option(self::OPTION_FLAG, '1');
    }

    public static function urlPrivacy(): string
    {
        return self::permalink((int) get_option(self::OPT_PRIVACY, 0));
    }

    public static function urlAviso(): string
    {
        return self::permalink((int) get_option(self::OPT_AVISO, 0));
    }

    public static function urlCookies(): string
    {
        return self::permalink((int) get_option(self::OPT_COOKIES, 0));
    }

    private static function permalink(int $postId): string
    {
        if ($postId <= 0) {
            return '';
        }
        $url = get_permalink($postId);
        return is_string($url) ? $url : '';
    }

    private static function insertPage(string $title, string $slug, string $content): int
    {
        $q = new \WP_Query([
            'post_type' => 'page',
            'name' => $slug,
            'posts_per_page' => 1,
            'post_status' => 'any',
            'fields' => 'ids',
            'no_found_rows' => true,
        ]);
        if ($q->have_posts()) {
            return (int) $q->posts[0];
        }

        $id = wp_insert_post([
            'post_title' => $title,
            'post_name' => $slug,
            'post_status' => 'draft',
            'post_type' => 'page',
            'post_content' => $content,
        ], true);

        return is_int($id) ? $id : 0;
    }

    private static function defaultPrivacyContent(): string
    {
        return '<!-- wp:paragraph -->'
            . '<p>' . esc_html__(
                '[Sustituir por el texto definitivo tras asesoramiento.] Responsable del tratamiento: [nombre / CIF / contacto]. Finalidades: gestion de matriculas, compras vinculadas a alumnos, saldos y comunicaciones del servicio. Legitimacion: ejecucion de medidas precontractuales y del contrato, interes legitimo donde proceda, consentimiento cuando sea requerido.',
                'vfc-core'
            ) . '</p>'
            . '<!-- /wp:paragraph -->';
    }

    private static function defaultAvisoContent(): string
    {
        return '<!-- wp:paragraph -->'
            . '<p>' . esc_html__(
                '[Sustituir por el texto definitivo.] Titular del sitio, datos registrales, condiciones de uso y limitacion de responsabilidad segun la normativa aplicable.',
                'vfc-core'
            ) . '</p>'
            . '<!-- /wp:paragraph -->';
    }

    private static function defaultCookiesContent(): string
    {
        $rows = [
            [
                'name' => 'wordpress_* / wordpress_logged_in_*',
                'purpose' => __('Autenticacion y sesion de WordPress (necesarias para administracion y, si aplica, acceso al portal).', 'vfc-core'),
                'duration' => __('Sesion o segun configuracion.', 'vfc-core'),
            ],
            [
                'name' => 'woocommerce_* / wp_woocommerce_*',
                'purpose' => __('Carrito, sesion de tienda y funciones de WooCommerce cuando se usa la tienda.', 'vfc-core'),
                'duration' => __('Definido por WooCommerce.', 'vfc-core'),
            ],
            [
                'name' => 'vfc_consent',
                'purpose' => __('Almacena de forma firmada sus preferencias de cookies (categorias aceptadas).', 'vfc-core'),
                'duration' => __('Aproximadamente 13 meses.', 'vfc-core'),
            ],
            [
                'name' => 'vfc_beneficiario',
                'purpose' => __('Vincula la compra en la tienda a la matricula del alumno cuando ha escaneado el QR o ha aceptado el flujo equivalente (cookie funcional).', 'vfc-core'),
                'duration' => __('Aproximadamente 30 dias.', 'vfc-core'),
            ],
        ];

        $table = '<figure class="wp-block-table"><table><thead><tr>'
            . '<th>' . esc_html__('Cookie / prefijo', 'vfc-core') . '</th>'
            . '<th>' . esc_html__('Finalidad', 'vfc-core') . '</th>'
            . '<th>' . esc_html__('Duracion orientativa', 'vfc-core') . '</th>'
            . '</tr></thead><tbody>';

        foreach ($rows as $row) {
            $table .= '<tr><td>' . esc_html($row['name']) . '</td><td>' . esc_html((string) $row['purpose']) . '</td><td>' . esc_html((string) $row['duration']) . '</td></tr>';
        }

        $table .= '</tbody></table></figure>';

        $intro = '<!-- wp:paragraph -->'
            . '<p>' . esc_html__(
                'A continuacion se resumen cookies y tecnologias similares usadas en este sitio en relacion con Viaje fin de curso (VFC). La lista puede ampliarse con plugins adicionales. Revise este borrador con asesoramiento juridico antes de publicarlo.',
                'vfc-core'
            ) . '</p><!-- /wp:paragraph -->';

        return $intro . $table;
    }
}

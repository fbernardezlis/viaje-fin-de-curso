<?php
declare(strict_types=1);

namespace VFC\Portal\Views;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Helpers de render: layout principal y partes (header/footer/flash).
 */
final class Layout
{
    /**
     * Renderiza una pagina completa.
     *
     * @param array<string, mixed> $data variables disponibles en el template
     */
    public static function render(string $title, string $page, array $data = []): void
    {
        $tpl = VFC_PORTAL_DIR . 'templates/pages/' . $page . '.php';
        if (!is_file($tpl)) {
            status_header(500);
            echo '<h1>VFC Portal: template not found: ' . esc_html($page) . '</h1>';
            return;
        }

        $portalUser = is_user_logged_in() ? wp_get_current_user() : null;
        $vars = array_merge(
            [
                'page_title' => $title,
                'page_slug' => $page,
                'rest_root' => esc_url_raw(rest_url('vfc/v1/portal/')),
                'rest_nonce' => wp_create_nonce('wp_rest'),
                'portal_user' => $portalUser,
            ],
            $data
        );
        extract($vars, EXTR_SKIP);

        require VFC_PORTAL_DIR . 'templates/layout.php';
    }
}

<?php
declare(strict_types=1);

namespace VFC\Portal\Routing;

use VFC\Portal\Services\QrImageService;
use VFC\Portal\Views\AlumnoView;
use VFC\Portal\Views\ColegioView;
use VFC\Portal\Views\LoginView;
use VFC\Portal\Views\PublicQrView;
use VFC\Portal\Views\ResetView;
use VFC\Portal\Views\TutorView;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Router del portal:
 *  - Registra rewrite rules /portal/* y /portal/qr/* y /portal/qr-image/*.
 *  - Anade query vars y enruta las vistas en `template_include`.
 *  - Encola CSS/JS solo en el contexto del portal.
 */
final class PortalRouter
{
    public const QV_VIEW = 'vfc_portal_view';
    public const QV_PARAM = 'vfc_portal_param';

    public const VIEW_LOGIN = 'login';
    public const VIEW_LOGOUT = 'logout';
    public const VIEW_RESET = 'reset';
    public const VIEW_ALUMNO = 'alumno';
    public const VIEW_TUTOR = 'tutor';
    public const VIEW_COLEGIO = 'colegio';
    public const VIEW_PUBLIC_QR = 'public-qr';
    public const VIEW_QR_IMAGE = 'qr-image';

    public function register(): void
    {
        add_action('init', [self::class, 'registerRewrites']);
        add_filter('query_vars', [self::class, 'queryVars']);
        add_action('template_redirect', [$this, 'dispatch']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public static function registerRewrites(): void
    {
        $views = [
            self::VIEW_LOGIN,
            self::VIEW_LOGOUT,
            self::VIEW_RESET,
            self::VIEW_ALUMNO,
            self::VIEW_TUTOR,
            self::VIEW_COLEGIO,
        ];
        foreach ($views as $v) {
            add_rewrite_rule(
                '^portal/' . $v . '/?$',
                'index.php?' . self::QV_VIEW . '=' . $v,
                'top'
            );
            add_rewrite_rule(
                '^portal/' . $v . '/([^/]+)/?$',
                'index.php?' . self::QV_VIEW . '=' . $v . '&' . self::QV_PARAM . '=$matches[1]',
                'top'
            );
        }
        add_rewrite_rule(
            '^portal/?$',
            'index.php?' . self::QV_VIEW . '=' . self::VIEW_LOGIN,
            'top'
        );
        add_rewrite_rule(
            '^portal/qr/([A-Za-z0-9_-]+)/?$',
            'index.php?' . self::QV_VIEW . '=' . self::VIEW_PUBLIC_QR . '&' . self::QV_PARAM . '=$matches[1]',
            'top'
        );
        add_rewrite_rule(
            '^portal/qr-image/(\d+)\.png/?$',
            'index.php?' . self::QV_VIEW . '=' . self::VIEW_QR_IMAGE . '&' . self::QV_PARAM . '=$matches[1]',
            'top'
        );
    }

    /**
     * @param array<int, string> $vars
     * @return array<int, string>
     */
    public static function queryVars(array $vars): array
    {
        $vars[] = self::QV_VIEW;
        $vars[] = self::QV_PARAM;
        return $vars;
    }

    public function dispatch(): void
    {
        $view = (string) get_query_var(self::QV_VIEW);
        if ($view === '') {
            return;
        }
        $param = (string) get_query_var(self::QV_PARAM);

        switch ($view) {
            case self::VIEW_LOGIN:
                (new LoginView())->render();
                exit;
            case self::VIEW_LOGOUT:
                wp_logout();
                wp_safe_redirect(home_url('/portal/login'));
                exit;
            case self::VIEW_RESET:
                (new ResetView())->render();
                exit;
            case self::VIEW_ALUMNO:
                (new AlumnoView())->render($param);
                exit;
            case self::VIEW_TUTOR:
                (new TutorView())->render($param);
                exit;
            case self::VIEW_COLEGIO:
                (new ColegioView())->render($param);
                exit;
            case self::VIEW_PUBLIC_QR:
                (new PublicQrView())->render($param);
                exit;
            case self::VIEW_QR_IMAGE:
                (new QrImageService())->stream((int) $param);
                exit;
        }
    }

    public function enqueueAssets(): void
    {
        $view = $this->detectPortalViewForAssets();
        if ($view === '') {
            return;
        }

        wp_enqueue_style(
            'vfc-portal',
            VFC_PORTAL_URL . 'assets/css/portal.css',
            [],
            VFC_PORTAL_VERSION
        );
        wp_enqueue_script(
            'vfc-portal',
            VFC_PORTAL_URL . 'assets/js/portal.js',
            [],
            VFC_PORTAL_VERSION,
            true
        );
        wp_localize_script('vfc-portal', 'VFC_PORTAL', [
            'restRoot' => esc_url_raw(rest_url('vfc/v1/portal/')),
            'nonce' => wp_create_nonce('wp_rest'),
        ]);

        if ($view === self::VIEW_ALUMNO || $view === self::VIEW_TUTOR) {
            wp_enqueue_script(
                'vfc-portal-saldo',
                VFC_PORTAL_URL . 'assets/js/saldo.js',
                ['vfc-portal'],
                VFC_PORTAL_VERSION,
                true
            );
            wp_enqueue_script(
                'vfc-portal-copy',
                VFC_PORTAL_URL . 'assets/js/copy.js',
                ['vfc-portal'],
                VFC_PORTAL_VERSION,
                true
            );
        }
        if ($view === self::VIEW_TUTOR) {
            wp_enqueue_script(
                'vfc-portal-switcher',
                VFC_PORTAL_URL . 'assets/js/switcher.js',
                ['vfc-portal'],
                VFC_PORTAL_VERSION,
                true
            );
        }
    }

    /**
     * Resuelve la vista del portal para encolar CSS/JS durante wp_head().
     * En algunos entornos get_query_var puede llegar vacío en wp_enqueue_scripts;
     * se usa la ruta de la petición como respaldo (incl. instalación en subdirectorio).
     */
    private function detectPortalViewForAssets(): string
    {
        $fromQuery = (string) get_query_var(self::QV_VIEW);
        if ($fromQuery !== '') {
            return $fromQuery;
        }

        $path = (string) wp_parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
        if ($path === '') {
            return '';
        }

        $homePath = wp_parse_url(home_url(), PHP_URL_PATH);
        if (is_string($homePath) && $homePath !== '' && $homePath !== '/') {
            $homePath = untrailingslashit($homePath);
            if ($homePath !== '' && str_starts_with($path, $homePath)) {
                $path = substr($path, strlen($homePath));
                if ($path === '' || !str_starts_with($path, '/')) {
                    $path = '/' . ltrim($path, '/');
                }
            }
        }

        $norm = untrailingslashit($path);
        if (preg_match('#^/portal/qr-image/\d+\.png$#', $norm) === 1) {
            return self::VIEW_QR_IMAGE;
        }
        if (preg_match('#^/portal/qr/[^/]+$#', $norm) === 1) {
            return self::VIEW_PUBLIC_QR;
        }
        if (preg_match('#^/portal/(login|logout|reset|alumno|colegio)$#', $norm, $m) === 1) {
            return $m[1];
        }
        if (preg_match('#^/portal/tutor(?:/\d+)?$#', $norm) === 1) {
            return self::VIEW_TUTOR;
        }
        if ($norm === '/portal') {
            return self::VIEW_LOGIN;
        }

        return '';
    }
}

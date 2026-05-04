<?php
declare(strict_types=1);

namespace VFC\Woo\Rest;

use VFC\Core\Domain\Edicion\EdicionRepository;
use VFC\Core\Domain\Edicion\EstadoEdicion;
use VFC\Core\Domain\Matricula\MatriculaRepository;
use VFC\Core\Privacy\ConsentService;
use VFC\Core\Services\AuditService;
use VFC\Core\Services\QrTokenService;
use VFC\Woo\Services\BeneficiarioSession;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Endpoints `/qr/{token}` y `/qr-logout` con rewrite rules.
 *
 * - GET /qr/{token}: valida el token y emite la cookie de beneficiario; redirige.
 * - GET|POST /qr-logout: limpia la cookie y redirige a home.
 */
final class QrEndpoint
{
    private const QV_TOKEN = 'vfc_qr_token';
    private const QV_LOGOUT = 'vfc_qr_logout';

    public function register(): void
    {
        add_action('init', [self::class, 'registerRewrites']);
        add_filter('query_vars', [self::class, 'queryVars']);
        add_action('template_redirect', [$this, 'handle']);
    }

    public static function registerRewrites(): void
    {
        add_rewrite_rule('^qr/([A-Za-z0-9_-]+)/?$', 'index.php?' . self::QV_TOKEN . '=$matches[1]', 'top');
        add_rewrite_rule('^qr-logout/?$', 'index.php?' . self::QV_LOGOUT . '=1', 'top');
    }

    /**
     * @param array<int, string> $vars
     * @return array<int, string>
     */
    public static function queryVars(array $vars): array
    {
        $vars[] = self::QV_TOKEN;
        $vars[] = self::QV_LOGOUT;
        return $vars;
    }

    public function handle(): void
    {
        $logout = get_query_var(self::QV_LOGOUT);
        if ($logout) {
            $this->handleLogout();
            return;
        }

        $token = get_query_var(self::QV_TOKEN);
        if ($token === '' || $token === null) {
            return;
        }
        if (!is_string($token)) {
            $this->redirectHome(['vfc_qr' => 'invalid']);
        }
        $this->handleAttach((string) $token);
    }

    private function handleAttach(string $token): void
    {
        $qr = new QrTokenService();
        $hash = $qr->hash($token);

        $matriculas = new MatriculaRepository();
        $matricula = $matriculas->findByTokenHash($hash);
        if ($matricula === null) {
            $this->redirectHome(['vfc_qr' => 'invalid']);
            return;
        }

        $ediciones = new EdicionRepository();
        $edicion = $ediciones->find($matricula->edicionId);
        if ($edicion === null || $edicion->estado !== EstadoEdicion::ACTIVA) {
            AuditService::log(
                'attach_beneficiario_rechazado',
                'matricula',
                (int) $matricula->id,
                ['motivo' => 'edicion_no_activa', 'estado' => $edicion?->estado],
                $edicion?->centroId
            );
            $this->redirectHome(['vfc_qr' => 'inactiva']);
            return;
        }

        if (!ConsentService::allowsFunctional()) {
            wp_safe_redirect(
                home_url('/portal/qr/' . rawurlencode($token) . '?consent_required=1')
            );
            exit;
        }

        (new BeneficiarioSession($matriculas, $ediciones))->issueCookie((int) $matricula->id);

        AuditService::log(
            'attach_beneficiario',
            'matricula',
            (int) $matricula->id,
            ['edicion_id' => $matricula->edicionId, 'alumno_user_id' => $matricula->alumnoUserId],
            $edicion->centroId
        );

        $this->redirectHome(['vfc_qr' => 'ok']);
    }

    private function handleLogout(): void
    {
        $session = new BeneficiarioSession();
        $matriculaId = $session->readMatriculaId();
        $session->clearCookie();
        AuditService::log(
            'detach_beneficiario',
            'matricula',
            $matriculaId,
            []
        );
        $this->redirectHome(['vfc_qr' => 'salido']);
    }

    /**
     * @param array<string, string> $params
     */
    private function redirectHome(array $params): void
    {
        $url = add_query_arg($params, home_url('/'));
        wp_safe_redirect($url);
        exit;
    }
}

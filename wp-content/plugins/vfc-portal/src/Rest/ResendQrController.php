<?php
declare(strict_types=1);

namespace VFC\Portal\Rest;

use VFC\Core\Domain\Matricula\MatriculaRepository;
use VFC\Portal\Routing\Permissions;
use VFC\Portal\Services\ResendQrService;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * POST /vfc/v1/portal/qr/resend -> reenvía el correo con el mismo enlace QR.
 */
final class ResendQrController
{
    private const NS = 'vfc/v1/portal';

    public function register(): void
    {
        register_rest_route(self::NS, '/qr/resend', [
            'methods' => 'POST',
            'callback' => [$this, 'post'],
            'permission_callback' => [$this, 'authorize'],
            'args' => [
                'matricula_id' => ['type' => 'integer', 'required' => true],
            ],
        ]);
    }

    public function authorize(\WP_REST_Request $request): bool|\WP_Error
    {
        if (!is_user_logged_in()) {
            return new \WP_Error('rest_forbidden', __('Sesión requerida.', 'vfc-portal'), ['status' => 401]);
        }
        $matriculaId = (int) $request->get_param('matricula_id');
        $matricula = (new MatriculaRepository())->find($matriculaId);
        if ($matricula === null) {
            return new \WP_Error('not_found', __('Matrícula no encontrada.', 'vfc-portal'), ['status' => 404]);
        }
        $user = wp_get_current_user();
        if (!Permissions::canSeeAlumno($user, (int) $matricula->alumnoUserId)) {
            return new \WP_Error('rest_forbidden', __('No autorizado.', 'vfc-portal'), ['status' => 403]);
        }
        return true;
    }

    public function post(\WP_REST_Request $request): \WP_REST_Response
    {
        $matriculaId = (int) $request->get_param('matricula_id');
        (new ResendQrService())->resend($matriculaId);
        return new \WP_REST_Response(['ok' => true]);
    }
}

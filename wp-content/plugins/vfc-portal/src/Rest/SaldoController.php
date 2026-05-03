<?php
declare(strict_types=1);

namespace VFC\Portal\Rest;

use VFC\Core\Domain\Saldo\SaldoRepository;
use VFC\Portal\Routing\Permissions;

if (!defined('ABSPATH')) {
    exit;
}

final class SaldoController
{
    private const NS = 'vfc/v1/portal';

    public function register(): void
    {
        register_rest_route(self::NS, '/saldo', [
            'methods' => 'GET',
            'callback' => [$this, 'get'],
            'permission_callback' => [$this, 'authorize'],
            'args' => [
                'alumno_id' => ['type' => 'integer', 'required' => false],
                'edicion_id' => ['type' => 'integer', 'required' => false],
            ],
        ]);
    }

    public function authorize(\WP_REST_Request $request): bool|\WP_Error
    {
        if (!is_user_logged_in()) {
            return new \WP_Error('rest_forbidden', __('Sesión requerida.', 'vfc-portal'), ['status' => 401]);
        }
        $user = wp_get_current_user();
        $alumnoId = (int) $request->get_param('alumno_id');
        if ($alumnoId === 0) {
            $alumnoId = (int) $user->ID;
        }
        if (!Permissions::canSeeAlumno($user, $alumnoId)) {
            return new \WP_Error('rest_forbidden', __('No autorizado.', 'vfc-portal'), ['status' => 403]);
        }
        return true;
    }

    public function get(\WP_REST_Request $request): \WP_REST_Response
    {
        $user = wp_get_current_user();
        $alumnoId = (int) $request->get_param('alumno_id');
        if ($alumnoId === 0) {
            $alumnoId = (int) $user->ID;
        }
        $edicionId = (int) $request->get_param('edicion_id');

        $repo = new SaldoRepository();
        $saldo = $repo->saldoNeto($alumnoId, $edicionId > 0 ? $edicionId : null);

        return new \WP_REST_Response([
            'alumno_id' => $alumnoId,
            'edicion_id' => $edicionId > 0 ? $edicionId : null,
            'saldo' => $saldo,
        ]);
    }
}

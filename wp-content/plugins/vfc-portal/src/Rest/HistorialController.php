<?php
declare(strict_types=1);

namespace VFC\Portal\Rest;

use VFC\Core\Domain\Saldo\SaldoRepository;
use VFC\Portal\Routing\Permissions;

if (!defined('ABSPATH')) {
    exit;
}

final class HistorialController
{
    private const NS = 'vfc/v1/portal';

    public function register(): void
    {
        register_rest_route(self::NS, '/movimientos', [
            'methods' => 'GET',
            'callback' => [$this, 'get'],
            'permission_callback' => [$this, 'authorize'],
            'args' => [
                'alumno_id' => ['type' => 'integer', 'required' => false],
                'edicion_id' => ['type' => 'integer', 'required' => false],
                'estado' => ['type' => 'string', 'required' => false],
                'tipo' => ['type' => 'string', 'required' => false],
                'from' => ['type' => 'string', 'required' => false],
                'to' => ['type' => 'string', 'required' => false],
                'page' => ['type' => 'integer', 'required' => false, 'default' => 1],
                'per_page' => ['type' => 'integer', 'required' => false, 'default' => 20],
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

        $repo = new SaldoRepository();
        $result = $repo->historial([
            'alumno_user_id' => $alumnoId,
            'edicion_id' => (int) $request->get_param('edicion_id'),
            'estado' => (string) $request->get_param('estado'),
            'tipo' => (string) $request->get_param('tipo'),
            'from' => (string) $request->get_param('from'),
            'to' => (string) $request->get_param('to'),
            'page' => (int) ($request->get_param('page') ?: 1),
            'per_page' => (int) ($request->get_param('per_page') ?: 20),
        ]);

        return new \WP_REST_Response($result);
    }
}

<?php
declare(strict_types=1);

namespace VFC\Portal\Rest;

use VFC\Core\Domain\Centro\CentroRepository;
use VFC\Core\Domain\CentroProducto\CentroProductoRepository;
use VFC\Core\Domain\Edicion\EdicionRepository;
use VFC\Core\Domain\Liquidacion\LiquidacionRepository;
use VFC\Core\Domain\Matricula\MatriculaRepository;
use VFC\Portal\Routing\Permissions;
use VFC\Portal\Services\DashboardService;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * GET /vfc/v1/portal/centro/{id} -> dashboard del admin de colegio.
 */
final class CentroController
{
    private const NS = 'vfc/v1/portal';

    public function register(): void
    {
        register_rest_route(self::NS, '/centro/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get'],
            'permission_callback' => [$this, 'authorize'],
        ]);
    }

    public function authorize(\WP_REST_Request $request): bool|\WP_Error
    {
        if (!is_user_logged_in()) {
            return new \WP_Error('rest_forbidden', __('Sesión requerida.', 'vfc-portal'), ['status' => 401]);
        }
        $centroId = (int) $request->get_param('id');
        $user = wp_get_current_user();
        if (!Permissions::canSeeCentro($user, $centroId)) {
            return new \WP_Error('rest_forbidden', __('No autorizado para este centro.', 'vfc-portal'), ['status' => 403]);
        }
        return true;
    }

    public function get(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
    {
        $centroId = (int) $request->get_param('id');
        $service = new DashboardService(
            new CentroRepository(),
            new EdicionRepository(),
            new MatriculaRepository(),
            new CentroProductoRepository(),
            new LiquidacionRepository()
        );
        $data = $service->snapshot($centroId);
        if ($data === null) {
            return new \WP_Error('not_found', __('Centro no encontrado.', 'vfc-portal'), ['status' => 404]);
        }
        return new \WP_REST_Response($data);
    }
}

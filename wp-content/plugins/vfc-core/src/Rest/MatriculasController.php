<?php
declare(strict_types=1);

namespace VFC\Core\Rest;

use VFC\Core\Domain\Matricula\MatriculaRepository;
use VFC\Core\Roles\Capabilities;
use WP_REST_Request;
use WP_REST_Response;

if (!defined('ABSPATH')) {
    exit;
}

final class MatriculasController
{
    public const NAMESPACE_ROUTE = 'vfc/v1';

    private MatriculaRepository $repo;

    public function __construct(?MatriculaRepository $repo = null)
    {
        $this->repo = $repo ?? new MatriculaRepository();
    }

    public function register(): void
    {
        register_rest_route(self::NAMESPACE_ROUTE, '/matriculas', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'index'],
                'permission_callback' => [$this, 'canRead'],
                'args' => [
                    'edicion_id' => ['type' => 'integer'],
                    'alumno_user_id' => ['type' => 'integer'],
                ],
            ],
        ]);
    }

    public function canRead(): bool
    {
        return current_user_can(Capabilities::MANAGE_MATRICULAS);
    }

    public function index(WP_REST_Request $req): WP_REST_Response
    {
        $edicionId = (int) $req->get_param('edicion_id');
        $alumnoId = (int) $req->get_param('alumno_user_id');

        if ($edicionId > 0) {
            $items = $this->repo->listByEdicion($edicionId);
        } elseif ($alumnoId > 0) {
            $items = $this->repo->listByAlumno($alumnoId);
        } else {
            return new WP_REST_Response(['code' => 'missing_filter', 'message' => 'edicion_id o alumno_user_id requerido'], 400);
        }

        $payload = array_map(static fn($m) => $m->toArray(), $items);
        return new WP_REST_Response($payload, 200);
    }
}

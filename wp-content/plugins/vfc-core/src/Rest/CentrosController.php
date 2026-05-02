<?php
declare(strict_types=1);

namespace VFC\Core\Rest;

use VFC\Core\Domain\Centro\CentroRepository;
use VFC\Core\Roles\Capabilities;
use WP_REST_Request;
use WP_REST_Response;

if (!defined('ABSPATH')) {
    exit;
}

final class CentrosController
{
    public const NAMESPACE_ROUTE = 'vfc/v1';

    private CentroRepository $repo;

    public function __construct(?CentroRepository $repo = null)
    {
        $this->repo = $repo ?? new CentroRepository();
    }

    public function register(): void
    {
        register_rest_route(self::NAMESPACE_ROUTE, '/centros', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'index'],
                'permission_callback' => [$this, 'canRead'],
                'args' => [
                    'search' => ['type' => 'string', 'required' => false],
                    'estado' => ['type' => 'string', 'required' => false, 'enum' => ['activo', 'inactivo']],
                    'per_page' => ['type' => 'integer', 'required' => false, 'minimum' => 1, 'maximum' => 100, 'default' => 20],
                    'page' => ['type' => 'integer', 'required' => false, 'minimum' => 1, 'default' => 1],
                ],
            ],
        ]);

        register_rest_route(self::NAMESPACE_ROUTE, '/centros/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'show'],
                'permission_callback' => [$this, 'canRead'],
                'args' => [
                    'id' => ['type' => 'integer', 'required' => true],
                ],
            ],
        ]);
    }

    public function canRead(): bool
    {
        return current_user_can(Capabilities::MANAGE_CENTROS);
    }

    public function index(WP_REST_Request $request): WP_REST_Response
    {
        $args = [
            'search' => (string) $request->get_param('search'),
            'estado' => (string) $request->get_param('estado'),
            'per_page' => (int) $request->get_param('per_page'),
            'page' => (int) $request->get_param('page'),
        ];

        $result = $this->repo->list(array_filter($args, static fn($v) => $v !== '' && $v !== 0));

        $items = array_map(static fn($c) => $c->toArray(), $result['items']);

        $response = new WP_REST_Response($items, 200);
        $response->header('X-VFC-Total', (string) $result['total']);
        return $response;
    }

    public function show(WP_REST_Request $request): WP_REST_Response
    {
        $id = (int) $request->get_param('id');
        $centro = $this->repo->find($id);
        if ($centro === null) {
            return new WP_REST_Response(['message' => __('Centro no encontrado.', 'vfc-core')], 404);
        }
        return new WP_REST_Response($centro->toArray(), 200);
    }
}

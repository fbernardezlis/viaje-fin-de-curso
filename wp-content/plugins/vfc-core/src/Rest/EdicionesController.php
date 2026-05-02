<?php
declare(strict_types=1);

namespace VFC\Core\Rest;

use VFC\Core\Domain\Edicion\EdicionRepository;
use VFC\Core\Roles\Capabilities;
use WP_REST_Request;
use WP_REST_Response;

if (!defined('ABSPATH')) {
    exit;
}

final class EdicionesController
{
    public const NAMESPACE_ROUTE = 'vfc/v1';

    private EdicionRepository $repo;

    public function __construct(?EdicionRepository $repo = null)
    {
        $this->repo = $repo ?? new EdicionRepository();
    }

    public function register(): void
    {
        register_rest_route(self::NAMESPACE_ROUTE, '/ediciones', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'index'],
                'permission_callback' => [$this, 'canRead'],
                'args' => [
                    'page' => ['type' => 'integer', 'default' => 1],
                    'per_page' => ['type' => 'integer', 'default' => 20],
                    'estado' => ['type' => 'string'],
                    'centro_id' => ['type' => 'integer'],
                    'search' => ['type' => 'string'],
                ],
            ],
        ]);
        register_rest_route(self::NAMESPACE_ROUTE, '/ediciones/(?P<id>\d+)', [
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
        return current_user_can(Capabilities::MANAGE_EDICIONES)
            || current_user_can(Capabilities::APPROVE_EDICIONES);
    }

    public function index(WP_REST_Request $req): WP_REST_Response
    {
        $args = [
            'per_page' => (int) $req->get_param('per_page'),
            'page' => (int) $req->get_param('page'),
        ];
        if ($req->get_param('estado')) {
            $args['estado'] = (string) $req->get_param('estado');
        }
        if ($req->get_param('centro_id')) {
            $args['centro_id'] = (int) $req->get_param('centro_id');
        }
        if ($req->get_param('search')) {
            $args['search'] = (string) $req->get_param('search');
        }

        $list = $this->repo->list($args);
        $items = array_map(static fn($e) => $e->toArray(), $list['items']);

        $resp = new WP_REST_Response($items, 200);
        $resp->header('X-VFC-Total', (string) $list['total']);
        return $resp;
    }

    public function show(WP_REST_Request $req): WP_REST_Response
    {
        $id = (int) $req->get_param('id');
        $edicion = $this->repo->find($id);
        if ($edicion === null) {
            return new WP_REST_Response(['code' => 'not_found'], 404);
        }
        return new WP_REST_Response($edicion->toArray(), 200);
    }
}

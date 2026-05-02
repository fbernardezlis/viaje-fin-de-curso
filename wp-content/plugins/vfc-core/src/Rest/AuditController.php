<?php
declare(strict_types=1);

namespace VFC\Core\Rest;

use VFC\Core\Roles\Capabilities;
use VFC\Core\Services\AuditService;
use WP_REST_Request;
use WP_REST_Response;

if (!defined('ABSPATH')) {
    exit;
}

final class AuditController
{
    public const NAMESPACE_ROUTE = 'vfc/v1';

    public function register(): void
    {
        register_rest_route(self::NAMESPACE_ROUTE, '/audit-log', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'index'],
                'permission_callback' => [$this, 'canRead'],
                'args' => [
                    'centro_id' => ['type' => 'integer'],
                    'accion' => ['type' => 'string'],
                    'entidad' => ['type' => 'string'],
                    'from' => ['type' => 'string'],
                    'to' => ['type' => 'string'],
                    'page' => ['type' => 'integer', 'default' => 1],
                    'per_page' => ['type' => 'integer', 'default' => 50],
                ],
            ],
        ]);
    }

    public function canRead(): bool
    {
        return current_user_can(Capabilities::VIEW_AUDIT);
    }

    public function index(WP_REST_Request $req): WP_REST_Response
    {
        $args = [
            'page' => (int) $req->get_param('page'),
            'per_page' => (int) $req->get_param('per_page'),
        ];
        foreach (['centro_id', 'accion', 'entidad', 'from', 'to'] as $key) {
            $value = $req->get_param($key);
            if ($value !== null && $value !== '') {
                $args[$key] = $value;
            }
        }

        $list = AuditService::list($args);
        $resp = new WP_REST_Response($list['items'], 200);
        $resp->header('X-VFC-Total', (string) $list['total']);
        return $resp;
    }
}

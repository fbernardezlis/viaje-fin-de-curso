<?php
declare(strict_types=1);

namespace VFC\Core\Privacy;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * POST /wp-json/vfc/v1/privacy/consent — guarda preferencias (cookie HTTP-only).
 */
final class ConsentRestController
{
    public const NAMESPACE_ROUTE = 'vfc/v1';

    public function register(): void
    {
        register_rest_route(self::NAMESPACE_ROUTE, '/privacy/consent', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'save'],
                'permission_callback' => [$this, 'verifyNonce'],
                'args' => [
                    'functional' => [
                        'type' => 'boolean',
                        'required' => false,
                        'default' => false,
                    ],
                    'analytics' => [
                        'type' => 'boolean',
                        'required' => false,
                        'default' => false,
                    ],
                    'revoke' => [
                        'type' => 'boolean',
                        'required' => false,
                        'default' => false,
                    ],
                ],
            ],
        ]);
    }

    public function verifyNonce(WP_REST_Request $request): bool
    {
        $nonce = (string) $request->get_header('X-WP-Nonce');
        return $nonce !== '' && (bool) wp_verify_nonce($nonce, 'wp_rest');
    }

    public function save(WP_REST_Request $req): WP_REST_Response|WP_Error
    {
        if ($req->get_param('revoke')) {
            ConsentService::revokeOptionalCookies();
            return new WP_REST_Response([
                'ok' => true,
                'functional' => false,
                'analytics' => false,
                'revoked' => true,
            ], 200);
        }

        $functional = (bool) $req->get_param('functional');
        $analytics = (bool) $req->get_param('analytics');

        ConsentService::setPreferences($functional, $analytics);

        return new WP_REST_Response([
            'ok' => true,
            'functional' => $functional,
            'analytics' => $analytics,
        ], 200);
    }
}

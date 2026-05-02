<?php
declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "Solo CLI.\n");
    exit(1);
}

$_SERVER['HTTP_HOST'] ??= 'localhost';
define('WP_USE_THEMES', false);

require '/var/www/html/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

// Login programático como administrador (user id 1).
wp_set_current_user(1);

$server = rest_get_server();
$request = new WP_REST_Request('GET', '/vfc/v1/centros');
$request->set_query_params(['per_page' => 5]);
$response = $server->dispatch($request);

echo "Status: " . $response->get_status() . "\n";
$data = $response->get_data();
echo "Items: " . (is_array($data) ? count($data) : 'N/A') . "\n";
echo "Header X-VFC-Total: " . ($response->get_headers()['X-VFC-Total'] ?? 'N/A') . "\n";

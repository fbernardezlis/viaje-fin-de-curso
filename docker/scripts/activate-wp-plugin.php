<?php
declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "Solo CLI.\n");
    exit(1);
}

$relative = $argv[1] ?? '';
if ($relative === '') {
    fwrite(STDERR, "Uso: php activate-wp-plugin.php plugin-dir/archivo.php\n");
    exit(1);
}

$_SERVER['HTTP_HOST'] ??= 'localhost';
define('WP_USE_THEMES', false);
define('WP_ADMIN', true);

require '/var/www/html/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

$result = activate_plugin($relative, '', false, false);
if (is_wp_error($result)) {
    fwrite(STDERR, $result->get_error_message() . "\n");
    exit(1);
}

echo "Plugin activado: {$relative}\n";

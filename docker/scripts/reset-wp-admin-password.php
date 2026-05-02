<?php
/**
 * Uso en el contenedor WordPress:
 *   php /tmp/reset-wp-admin-password.php 'NuevaContraseñaSegura' [user_id]
 * No dejar este archivo accesible por web.
 */
declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "Solo CLI.\n");
    exit(1);
}

define('WP_USE_THEMES', false);
require '/var/www/html/wp-load.php';

$pass = $argv[1] ?? '';
$userId = isset($argv[2]) ? (int) $argv[2] : 1;

if ($pass === '') {
    fwrite(STDERR, "Uso: php reset-wp-admin-password.php 'nueva_contraseña' [user_id]\n");
    exit(1);
}

$user = get_userdata($userId);
if (!$user) {
    fwrite(STDERR, "Usuario ID {$userId} no encontrado.\n");
    exit(1);
}

wp_set_password($pass, $userId);
echo 'Contraseña actualizada para: ' . $user->user_login . " (ID {$userId})\n";

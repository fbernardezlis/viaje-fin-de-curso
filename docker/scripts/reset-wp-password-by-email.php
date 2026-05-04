<?php
/**
 * Restablece la contraseña de un usuario WordPress por email.
 *
 * Uso en el contenedor:
 *   php /tmp/reset-wp-password-by-email.php correo@dominio.com
 *     → genera una contraseña aleatoria y la muestra por stdout (una vez).
 *   php /tmp/reset-wp-password-by-email.php correo@dominio.com 'TuClaveElegida'
 *
 * No dejar este archivo accesible por web.
 */
declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "Solo CLI.\n");
    exit(1);
}

define('WP_USE_THEMES', false);
require '/var/www/html/wp-load.php';

$email = isset($argv[1]) ? trim((string) $argv[1]) : '';
if ($email === '' || !is_email($email)) {
    fwrite(STDERR, "Uso: php reset-wp-password-by-email.php email@valido.com ['clave_opcional']\n");
    exit(1);
}

$user = get_user_by('email', $email);
if (!$user instanceof WP_User) {
    fwrite(STDERR, "No hay usuario con el email: {$email}\n");
    exit(1);
}

$pass = $argv[2] ?? null;
if ($pass === null || $pass === '') {
    $bytes = random_bytes(12);
    $pass = rtrim(strtr(base64_encode($bytes), '+/', 'aB'), '=') . '0aZ!';
}

wp_set_password((string) $pass, (int) $user->ID);

echo "Usuario: {$user->user_login}\n";
echo "Email:   {$user->user_email}\n";
echo "ID:      {$user->ID}\n";
echo "Nueva contraseña: {$pass}\n";
echo "(Guárdala ahora; no se volverá a mostrar en el log del contenedor salvo que repitas el comando.)\n";

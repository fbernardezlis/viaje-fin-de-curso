<?php
/**
 * Solo entornos de desarrollo local.
 *
 * Uso (dentro del contenedor, desde /tmp o copiado):
 *   php reset-alumno-dev-password.php 'TuClaveSegura' [login_o_email]
 *
 * Si omites el segundo argumento, usa el último usuario con rol vfc_alumno.
 */
declare(strict_types=1);

if ($argc < 2 || (string) $argv[1] === '') {
    fwrite(STDERR, "Uso: php reset-alumno-dev-password.php 'NuevaClave' [login_o_email]\n");
    exit(1);
}

define('WP_USE_THEMES', false);
require '/var/www/html/wp-load.php';

$pass = (string) $argv[1];
$needle = isset($argv[2]) ? (string) $argv[2] : '';

$user = null;
if ($needle !== '') {
    $user = get_user_by('login', $needle) ?: get_user_by('email', $needle);
    if (!$user instanceof WP_User) {
        fwrite(STDERR, "Usuario no encontrado: {$needle}\n");
        exit(1);
    }
    if (!in_array('vfc_alumno', (array) $user->roles, true)) {
        fwrite(STDERR, "El usuario no tiene rol vfc_alumno.\n");
        exit(1);
    }
} else {
    $alumnos = get_users([
        'role' => 'vfc_alumno',
        'number' => 1,
        'orderby' => 'ID',
        'order' => 'DESC',
    ]);
    if ($alumnos === []) {
        fwrite(STDERR, "No hay ningún usuario con rol vfc_alumno. Crea uno desde wp-admin o con UsersService.\n");
        exit(1);
    }
    $user = $alumnos[0];
}

wp_set_password($pass, (int) $user->ID);

echo "Listo. Usa estas credenciales en /portal/login:\n\n";
echo "  Usuario (login): " . $user->user_login . "\n";
echo "  Email:           " . $user->user_email . "\n";
echo "  Contraseña:      (la que pasaste por CLI)\n";

<?php
declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "Solo CLI.\n");
    exit(1);
}

$slug = $argv[1] ?? '';
if ($slug === '') {
    fwrite(STDERR, "Uso: php activate-theme.php nombre-de-carpeta\n");
    exit(1);
}

$_SERVER['HTTP_HOST'] ??= 'localhost';
define('WP_USE_THEMES', false);

require '/var/www/html/wp-load.php';

$theme = wp_get_theme($slug);
if (!$theme->exists()) {
    fwrite(STDERR, "Tema {$slug} no encontrado.\n");
    exit(1);
}

switch_theme($slug);
echo "Tema activado: {$slug}\n";

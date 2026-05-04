<?php
declare(strict_types=1);

/**
 * Descarga Kadence desde wordpress.org y lo descomprime en wp-content/themes/.
 * Desde la raíz del repo (solo wp-content está montado en el contenedor):
 *
 *   docker cp docker/scripts/install-kadence-theme.php vfc-wordpress:/tmp/install-kadence-theme.php
 *   docker exec vfc-wordpress php /tmp/install-kadence-theme.php
 *   docker exec vfc-wordpress rm -f /tmp/install-kadence-theme.php
 *
 * Luego activa el hijo:
 *
 *   docker cp docker/scripts/activate-theme.php vfc-wordpress:/tmp/activate-theme.php
 *   docker exec vfc-wordpress php /tmp/activate-theme.php vfc-portal
 *   docker exec vfc-wordpress rm -f /tmp/activate-theme.php
 */

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "Solo CLI.\n");
    exit(1);
}

$_SERVER['HTTP_HOST'] ??= 'localhost';
define('WP_USE_THEMES', false);

require '/var/www/html/wp-load.php';

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/misc.php';

$url = 'https://downloads.wordpress.org/theme/kadence.latest-stable.zip';
echo "Descargando Kadence…\n";
$tmp = download_url($url);
if (is_wp_error($tmp)) {
    fwrite(STDERR, 'Error descarga: ' . $tmp->get_error_message() . "\n");
    exit(1);
}

$themesDir = WP_CONTENT_DIR . '/themes';
echo "Descomprimiendo en {$themesDir}…\n";
$result = unzip_file($tmp, $themesDir);
@unlink($tmp);

if (is_wp_error($result)) {
    fwrite(STDERR, 'Error unzip: ' . $result->get_error_message() . "\n");
    exit(1);
}

echo "Kadence instalado en {$themesDir}/kadence\n";
echo "Activa el tema hijo con activate-theme.php vfc-portal (ver comentario de cabecera de este script).\n";

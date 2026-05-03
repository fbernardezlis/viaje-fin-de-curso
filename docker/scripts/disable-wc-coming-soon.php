<?php
/**
 * Desactiva el modo "Próximamente" de WooCommerce (pantalla magenta con el texto
 * "¡Disculpa este desastre! Estamos trabajando en algo increíble, ¡vuelve pronto!").
 *
 * Equivale a: WooCommerce → Ajustes → Visibilidad del sitio → En vivo.
 *
 * Uso (contenedor):
 *   php /tmp/disable-wc-coming-soon.php
 */
declare(strict_types=1);

define('WP_USE_THEMES', false);
require '/var/www/html/wp-load.php';

if (!class_exists(\WooCommerce::class)) {
    fwrite(STDERR, "WooCommerce no está cargado o no está activo.\n");
    exit(1);
}

$before = get_option('woocommerce_coming_soon', '(no definida)');
update_option('woocommerce_coming_soon', 'no');
$after = get_option('woocommerce_coming_soon');

echo "woocommerce_coming_soon: antes=" . var_export($before, true) . " después=" . var_export($after, true) . "\n";
echo "Listo. Recarga la portada en el navegador (Ctrl+F5). Si usas caché de hosting/CDN, purgala.\n";

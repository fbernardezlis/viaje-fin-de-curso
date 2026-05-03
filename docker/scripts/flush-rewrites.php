<?php
declare(strict_types=1);

define('WP_USE_THEMES', false);
require '/var/www/html/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

echo 'before permalink_structure: ' . (get_option('permalink_structure') ?: '(plain)') . "\n";

if (!get_option('permalink_structure')) {
    update_option('permalink_structure', '/%postname%/');
    echo "updated permalink_structure to /%postname%/\n";
}

echo 'vfc-portal active: ' . (is_plugin_active('vfc-portal/vfc-portal.php') ? 'yes' : 'no') . "\n";

flush_rewrite_rules(true);
echo "flush_rewrite_rules(true) done\n";

echo 'after permalink_structure: ' . get_option('permalink_structure') . "\n";

// En PHP-CLI Apache no está presente: WordPress a veces deja el bloque vacío en .htaccess.
// Sin RewriteEngine, /portal/* devuelve 404 a nivel Apache antes de llegar a index.php.
require_once ABSPATH . 'wp-admin/includes/misc.php';
$htaccess = ABSPATH . '.htaccess';
$insertion = [
    '<IfModule mod_rewrite.c>',
    'RewriteEngine On',
    'RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]',
    'RewriteBase /',
    'RewriteRule ^index\.php$ - [L]',
    'RewriteCond %{REQUEST_FILENAME} !-f',
    'RewriteCond %{REQUEST_FILENAME} !-d',
    'RewriteRule . /index.php [L]',
    '</IfModule>',
];
if (insert_with_markers($htaccess, 'WordPress', $insertion)) {
    echo ".htaccess: reglas mod_rewrite escritas con insert_with_markers\n";
} else {
    echo ".htaccess: no se pudo escribir (permisos). Revisa " . $htaccess . "\n";
}

<?php
declare(strict_types=1);

define('WP_USE_THEMES', false);
require '/var/www/html/wp-load.php';

echo 'siteurl=' . get_option('siteurl') . "\n";
echo 'home=' . get_option('home') . "\n";
echo 'stylesheet=' . get_option('stylesheet') . "\n";
echo 'template=' . get_option('template') . "\n";
echo 'show_on_front=' . get_option('show_on_front') . "\n";
echo 'page_on_front=' . (string) get_option('page_on_front') . "\n";

$theme = wp_get_theme();
if ($theme->errors() && $theme->errors()->has_errors()) {
    echo 'theme_errors=' . wp_json_encode($theme->errors()->get_error_messages()) . "\n";
} else {
    echo 'theme_ok=' . $theme->get_stylesheet() . "\n";
}

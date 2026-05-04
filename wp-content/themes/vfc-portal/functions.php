<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

add_action('after_setup_theme', static function (): void {
    load_child_theme_textdomain('vfc-portal', get_stylesheet_directory() . '/languages');
});

add_action('wp_enqueue_scripts', static function (): void {
    // Kadence registra y encola `kadence-global` en el front; el hijo va después.
    wp_enqueue_style(
        'vfc-portal-style',
        get_stylesheet_directory_uri() . '/style.css',
        ['kadence-global'],
        wp_get_theme()->get('Version')
    );
}, 20);

<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

add_action('after_setup_theme', static function (): void {
    load_child_theme_textdomain('vfc-portal', get_stylesheet_directory() . '/languages');
});

add_action('wp_enqueue_scripts', static function (): void {
    $parent = 'twentytwentyfive-style';
    wp_enqueue_style(
        $parent,
        get_template_directory_uri() . '/style.css',
        [],
        wp_get_theme(get_template())->get('Version')
    );

    wp_enqueue_style(
        'vfc-portal-style',
        get_stylesheet_directory_uri() . '/style.css',
        [$parent],
        wp_get_theme()->get('Version')
    );
});

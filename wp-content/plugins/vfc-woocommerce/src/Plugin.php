<?php
declare(strict_types=1);

namespace VFC\Woo;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Bootstrap del plugin VFC WooCommerce. En Fase 0 solo expone el contenedor
 * y la verificación de dependencias; los servicios reales se añaden en Fase 2.
 */
final class Plugin
{
    private static ?self $instance = null;

    private bool $booted = false;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }
        $this->booted = true;

        load_plugin_textdomain('vfc-woocommerce', false, dirname(plugin_basename(VFC_WOO_FILE)) . '/languages');

        add_action('admin_notices', [$this, 'maybeShowDependencyNotice']);
    }

    public function maybeShowDependencyNotice(): void
    {
        if (!current_user_can('activate_plugins')) {
            return;
        }
        $missing = [];
        if (!class_exists('WooCommerce')) {
            $missing[] = 'WooCommerce';
        }
        if (!class_exists(\VFC\Core\Plugin::class)) {
            $missing[] = 'VFC Core';
        }
        if ($missing === []) {
            return;
        }
        printf(
            '<div class="notice notice-error"><p><strong>VFC WooCommerce</strong>: %s %s.</p></div>',
            esc_html__('Faltan dependencias activas:', 'vfc-woocommerce'),
            esc_html(implode(', ', $missing))
        );
    }

    private function __construct()
    {
    }
}

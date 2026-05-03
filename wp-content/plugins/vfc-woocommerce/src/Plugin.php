<?php
declare(strict_types=1);

namespace VFC\Woo;

use VFC\Woo\Admin\SettingsPage;
use VFC\Woo\Frontend\BeneficiarioBanner;
use VFC\Woo\Frontend\CatalogFilter;
use VFC\Woo\Frontend\CheckoutNotice;
use VFC\Woo\Rest\QrEndpoint;
use VFC\Woo\Services\CronService;
use VFC\Woo\WooHooks\OrderHooks;
use VFC\Woo\WooHooks\ProductMetaPanel;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Bootstrap del plugin VFC WooCommerce. Solo arranca los modulos
 * cuando WooCommerce y vfc-core estan presentes.
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

        if (!$this->dependenciesReady()) {
            add_action('admin_notices', [$this, 'maybeShowDependencyNotice']);
            return;
        }

        (new QrEndpoint())->register();
        (new CatalogFilter())->register();
        (new BeneficiarioBanner())->register();
        (new CheckoutNotice())->register();
        (new OrderHooks())->register();
        (new ProductMetaPanel())->register();
        (new CronService())->register();

        if (is_admin()) {
            (new SettingsPage())->register();
        }
    }

    public function maybeShowDependencyNotice(): void
    {
        if (!current_user_can('activate_plugins')) {
            return;
        }
        $missing = $this->missingDependencies();
        if ($missing === []) {
            return;
        }
        printf(
            '<div class="notice notice-error"><p><strong>VFC WooCommerce</strong>: %s %s.</p></div>',
            esc_html__('Faltan dependencias activas:', 'vfc-woocommerce'),
            esc_html(implode(', ', $missing))
        );
    }

    private function dependenciesReady(): bool
    {
        return $this->missingDependencies() === [];
    }

    /**
     * @return array<int, string>
     */
    private function missingDependencies(): array
    {
        $missing = [];
        if (!class_exists('WooCommerce')) {
            $missing[] = 'WooCommerce';
        }
        if (!class_exists(\VFC\Core\Plugin::class)) {
            $missing[] = 'VFC Core';
        }
        return $missing;
    }

    private function __construct()
    {
    }
}

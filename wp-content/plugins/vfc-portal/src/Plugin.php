<?php
declare(strict_types=1);

namespace VFC\Portal;

use VFC\Portal\Auth\AuthController;
use VFC\Portal\Rest\CentroController;
use VFC\Portal\Rest\HistorialController;
use VFC\Portal\Rest\MisAlumnosController;
use VFC\Portal\Rest\ResendQrController;
use VFC\Portal\Rest\SaldoController;
use VFC\Portal\Routing\PortalRouter;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Bootstrap del plugin VFC Portal.
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

        load_plugin_textdomain('vfc-portal', false, dirname(plugin_basename(VFC_PORTAL_FILE)) . '/languages');

        if (!$this->dependenciesReady()) {
            add_action('admin_notices', [$this, 'maybeShowDependencyNotice']);
            return;
        }

        (new PortalRouter())->register();
        (new AuthController())->register();

        add_action('rest_api_init', static function (): void {
            (new SaldoController())->register();
            (new HistorialController())->register();
            (new MisAlumnosController())->register();
            (new CentroController())->register();
            (new ResendQrController())->register();
        });
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
            '<div class="notice notice-error"><p><strong>VFC Portal</strong>: %s %s.</p></div>',
            esc_html__('Faltan dependencias activas:', 'vfc-portal'),
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
        if (!class_exists(\VFC\Core\Plugin::class)) {
            $missing[] = 'VFC Core';
        }
        return $missing;
    }

    private function __construct()
    {
    }
}

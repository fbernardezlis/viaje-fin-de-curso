<?php
declare(strict_types=1);

namespace VFC\Core;

use VFC\Core\Admin\Pages\AlumnosListPage;
use VFC\Core\Admin\Pages\AuditPage;
use VFC\Core\Admin\Pages\CentroProductosPage;
use VFC\Core\Admin\Pages\CentrosListPage;
use VFC\Core\Admin\Pages\EdicionesListPage;
use VFC\Core\Admin\Pages\LiquidacionesListPage;
use VFC\Core\Admin\Pages\MatriculasListPage;
use VFC\Core\Admin\Pages\TutoresListPage;
use VFC\Core\Database\Migrations;
use VFC\Core\Privacy\ConsentRestController;
use VFC\Core\Privacy\CookieBanner;
use VFC\Core\Privacy\LegalPages;
use VFC\Core\Privacy\PrivacyGuide;
use VFC\Core\Rest\AuditController;
use VFC\Core\Rest\CentrosController;
use VFC\Core\Rest\EdicionesController;
use VFC\Core\Rest\MatriculasController;
use VFC\Core\Services\QrEmailService;
use VFC\Core\Services\UsersService;

if (!defined('ABSPATH')) {
    exit;
}

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

        load_plugin_textdomain('vfc-core', false, dirname(plugin_basename(VFC_CORE_FILE)) . '/languages');

        Migrations::maybeUpgrade();
        LegalPages::installOnce();

        UsersService::registerHooks();
        QrEmailService::registerHooks();
        PrivacyGuide::register();
        (new CookieBanner())->register();

        if (is_admin()) {
            (new CentrosListPage())->register();
            (new EdicionesListPage())->register();
            (new AlumnosListPage())->register();
            (new TutoresListPage())->register();
            (new MatriculasListPage())->register();
            (new CentroProductosPage())->register();
            (new AuditPage())->register();
            (new LiquidacionesListPage())->register();
        }

        add_action('rest_api_init', static function (): void {
            (new CentrosController())->register();
            (new EdicionesController())->register();
            (new MatriculasController())->register();
            (new AuditController())->register();
            (new ConsentRestController())->register();
        });
    }

    private function __construct()
    {
    }
}

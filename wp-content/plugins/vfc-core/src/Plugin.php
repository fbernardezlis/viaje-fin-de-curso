<?php
declare(strict_types=1);

namespace VFC\Core;

use VFC\Core\Admin\Pages\CentrosListPage;
use VFC\Core\Database\Migrations;
use VFC\Core\Rest\CentrosController;

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

        if (is_admin()) {
            (new CentrosListPage())->register();
        }

        add_action('rest_api_init', static function (): void {
            (new CentrosController())->register();
        });
    }

    private function __construct()
    {
    }
}

<?php
declare(strict_types=1);

namespace VFC\Core;

use VFC\Core\Database\Migrations;
use VFC\Core\Roles\RolesInstaller;

if (!defined('ABSPATH')) {
    exit;
}

final class Activator
{
    public static function activate(): void
    {
        Migrations::run();
        RolesInstaller::install();
        flush_rewrite_rules();
    }
}

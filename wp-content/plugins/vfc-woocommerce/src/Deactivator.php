<?php
declare(strict_types=1);

namespace VFC\Woo;

use VFC\Woo\Services\CronService;

if (!defined('ABSPATH')) {
    exit;
}

final class Deactivator
{
    public static function deactivate(): void
    {
        CronService::unscheduleEvent();
        flush_rewrite_rules();
    }
}

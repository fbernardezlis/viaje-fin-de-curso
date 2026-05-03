<?php
declare(strict_types=1);

namespace VFC\Woo;

use VFC\Woo\Rest\QrEndpoint;
use VFC\Woo\Services\CronService;

if (!defined('ABSPATH')) {
    exit;
}

final class Activator
{
    public static function activate(): void
    {
        QrEndpoint::registerRewrites();
        flush_rewrite_rules();

        CronService::scheduleEvent();
    }
}

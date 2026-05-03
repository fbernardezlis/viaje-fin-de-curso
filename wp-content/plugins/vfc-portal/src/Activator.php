<?php
declare(strict_types=1);

namespace VFC\Portal;

use VFC\Portal\Routing\PortalRouter;

if (!defined('ABSPATH')) {
    exit;
}

final class Activator
{
    public static function activate(): void
    {
        PortalRouter::registerRewrites();
        flush_rewrite_rules();
    }
}

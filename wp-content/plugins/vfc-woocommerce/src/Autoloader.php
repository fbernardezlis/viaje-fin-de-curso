<?php
declare(strict_types=1);

namespace VFC\Woo;

if (!defined('ABSPATH')) {
    exit;
}

final class Autoloader
{
    private const NAMESPACE_PREFIX = 'VFC\\Woo\\';

    public static function register(): void
    {
        spl_autoload_register([self::class, 'load']);
    }

    public static function load(string $class): void
    {
        if (!str_starts_with($class, self::NAMESPACE_PREFIX)) {
            return;
        }

        $relative = substr($class, strlen(self::NAMESPACE_PREFIX));
        $path = VFC_WOO_DIR . 'src/' . str_replace('\\', '/', $relative) . '.php';

        if (is_file($path)) {
            require_once $path;
        }
    }
}

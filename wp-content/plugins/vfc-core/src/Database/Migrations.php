<?php
declare(strict_types=1);

namespace VFC\Core\Database;

if (!defined('ABSPATH')) {
    exit;
}

final class Migrations
{
    public const OPTION_VERSION = 'vfc_db_version';
    public const TARGET_VERSION = '1.2.0';

    /**
     * Ejecuta dbDelta en activación o cuando hay desfase de versión.
     */
    public static function run(): void
    {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        foreach (Schema::definitions() as $sql) {
            dbDelta($sql);
        }

        update_option(self::OPTION_VERSION, self::TARGET_VERSION, false);
    }

    /**
     * Comprueba versión guardada y aplica migraciones si difiere.
     * Se llama en cada `plugins_loaded` para sobrevivir a clones de carpeta sin reactivar.
     */
    public static function maybeUpgrade(): void
    {
        $current = get_option(self::OPTION_VERSION);
        if ($current === self::TARGET_VERSION) {
            return;
        }
        self::run();
    }
}

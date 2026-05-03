<?php
declare(strict_types=1);

namespace VFC\Woo\Services;

use VFC\Core\Database\Schema;
use VFC\Core\Services\AuditService;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Cron diario que pasa movimientos `BLOQUEADO` a `CONFIRMADO` cuando ya ha vencido
 * la fecha de liberacion. Tambien expone una ejecucion manual desde Ajustes.
 */
final class CronService
{
    public const HOOK = 'vfc_cron_liberar_bloqueados';

    public function register(): void
    {
        add_action(self::HOOK, [$this, 'liberarBloqueados']);
        add_action('init', [self::class, 'ensureScheduled'], 20);
    }

    public static function ensureScheduled(): void
    {
        if (!wp_next_scheduled(self::HOOK)) {
            self::scheduleEvent();
        }
    }

    public static function scheduleEvent(): void
    {
        if (!wp_next_scheduled(self::HOOK)) {
            wp_schedule_event(time() + 60, 'daily', self::HOOK);
        }
    }

    public static function unscheduleEvent(): void
    {
        $ts = wp_next_scheduled(self::HOOK);
        while ($ts !== false) {
            wp_unschedule_event($ts, self::HOOK);
            $ts = wp_next_scheduled(self::HOOK);
        }
    }

    /**
     * Libera (BLOQUEADO -> CONFIRMADO) los movimientos cuya fecha_liberacion ha vencido.
     *
     * @return int filas afectadas
     */
    public function liberarBloqueados(): int
    {
        global $wpdb;
        $table = Schema::table(Schema::TABLE_MOVIMIENTOS_SALDO);
        $now = current_time('mysql', true);

        $count = (int) $wpdb->query($wpdb->prepare(
            "UPDATE {$table}
             SET estado = 'CONFIRMADO',
                 fecha_confirmacion = %s,
                 updated_at = %s
             WHERE estado = 'BLOQUEADO'
               AND fecha_liberacion IS NOT NULL
               AND fecha_liberacion <= %s",
            $now,
            $now,
            $now
        ));

        if ($count > 0) {
            AuditService::log('liberar_bloqueados', 'movimiento_saldo', null, [
                'filas_actualizadas' => $count,
                'momento' => $now,
            ]);
        }

        return $count;
    }
}

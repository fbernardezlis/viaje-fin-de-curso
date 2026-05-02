<?php
declare(strict_types=1);

namespace VFC\Core\Services;

use VFC\Core\Database\Schema;

if (!defined('ABSPATH')) {
    exit;
}

final class AuditService
{
    /**
     * Registra una acción de auditoría.
     *
     * @param array<string, mixed> $datos
     */
    public static function log(
        string $accion,
        string $entidadTipo,
        ?int $entidadId,
        array $datos = [],
        ?int $centroId = null
    ): void {
        global $wpdb;

        $table = Schema::table(Schema::TABLE_AUDIT_LOG);

        $wpdb->insert(
            $table,
            [
                'fecha' => current_time('mysql', true),
                'actor_user_id' => get_current_user_id() ?: null,
                'accion' => $accion,
                'entidad_tipo' => $entidadTipo,
                'entidad_id' => $entidadId,
                'centro_id' => $centroId,
                'datos' => self::encodeDatos($datos),
                'ip' => self::detectIp(),
                'user_agent' => self::detectUserAgent(),
            ],
            ['%s', '%d', '%s', '%s', '%d', '%d', '%s', '%s', '%s']
        );
    }

    /**
     * @param array<string, mixed> $datos
     */
    private static function encodeDatos(array $datos): ?string
    {
        if ($datos === []) {
            return null;
        }
        $json = wp_json_encode($datos);
        return is_string($json) ? $json : null;
    }

    private static function detectIp(): ?string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        if (!is_string($ip) || $ip === '') {
            return null;
        }
        return substr($ip, 0, 45);
    }

    private static function detectUserAgent(): ?string
    {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
        if (!is_string($ua) || $ua === '') {
            return null;
        }
        return substr(sanitize_text_field($ua), 0, 255);
    }
}

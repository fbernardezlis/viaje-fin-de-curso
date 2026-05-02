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
     * Lista entradas de auditoría con filtros opcionales.
     *
     * @param array{centro_id?: int, accion?: string, entidad?: string, from?: string, to?: string, per_page?: int, page?: int} $args
     * @return array{items: array<int, array<string, mixed>>, total: int}
     */
    public static function list(array $args = []): array
    {
        global $wpdb;
        $table = Schema::table(Schema::TABLE_AUDIT_LOG);

        $perPage = max(1, (int) ($args['per_page'] ?? 50));
        $page = max(1, (int) ($args['page'] ?? 1));
        $offset = ($page - 1) * $perPage;

        [$whereSql, $params] = self::buildWhere($args);

        $totalSql = "SELECT COUNT(*) FROM {$table} WHERE {$whereSql}";
        $total = (int) ($params === []
            ? $wpdb->get_var($totalSql)
            : $wpdb->get_var($wpdb->prepare($totalSql, $params)));

        $listSql = "SELECT * FROM {$table} WHERE {$whereSql} ORDER BY id DESC LIMIT %d OFFSET %d";
        $listParams = array_merge($params, [$perPage, $offset]);
        $rows = $wpdb->get_results($wpdb->prepare($listSql, $listParams), ARRAY_A);

        return ['items' => array_map('self::ensureArray', (array) $rows), 'total' => $total];
    }

    /**
     * Itera todas las filas que cumplen los filtros (sin paginación) en lotes.
     *
     * @param array{centro_id?: int, accion?: string, entidad?: string, from?: string, to?: string} $args
     * @return iterable<int, array<string, mixed>>
     */
    public static function iterate(array $args = []): iterable
    {
        global $wpdb;
        $table = Schema::table(Schema::TABLE_AUDIT_LOG);
        [$whereSql, $params] = self::buildWhere($args);

        $batch = 500;
        $offset = 0;
        while (true) {
            $sql = "SELECT * FROM {$table} WHERE {$whereSql} ORDER BY id ASC LIMIT %d OFFSET %d";
            $rows = $wpdb->get_results(
                $wpdb->prepare($sql, array_merge($params, [$batch, $offset])),
                ARRAY_A
            );
            if (!$rows) {
                break;
            }
            foreach ($rows as $row) {
                yield $row;
            }
            $offset += $batch;
            if (count($rows) < $batch) {
                break;
            }
        }
    }

    /**
     * @param array{centro_id?: int, accion?: string, entidad?: string, from?: string, to?: string} $args
     * @return array{0: string, 1: array<int, mixed>}
     */
    private static function buildWhere(array $args): array
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($args['centro_id'])) {
            $where[] = 'centro_id = %d';
            $params[] = (int) $args['centro_id'];
        }
        if (!empty($args['accion'])) {
            $where[] = 'accion = %s';
            $params[] = (string) $args['accion'];
        }
        if (!empty($args['entidad'])) {
            $where[] = 'entidad_tipo = %s';
            $params[] = (string) $args['entidad'];
        }
        if (!empty($args['from'])) {
            $where[] = 'fecha >= %s';
            $params[] = (string) $args['from'];
        }
        if (!empty($args['to'])) {
            $where[] = 'fecha <= %s';
            $params[] = (string) $args['to'];
        }

        return [implode(' AND ', $where), $params];
    }

    /**
     * @param mixed $row
     * @return array<string, mixed>
     */
    private static function ensureArray($row): array
    {
        return is_array($row) ? $row : [];
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

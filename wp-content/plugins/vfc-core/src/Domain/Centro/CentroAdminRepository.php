<?php
declare(strict_types=1);

namespace VFC\Core\Domain\Centro;

use VFC\Core\Database\Schema;
use VFC\Core\Services\AuditService;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Vincula usuarios (admins de colegio) con centros (relacion N:N) en `wp_vfc_centro_admins`.
 */
final class CentroAdminRepository
{
    private const ENTIDAD = 'centro_admin';

    public function assign(int $centroId, int $userId): void
    {
        if ($centroId <= 0 || $userId <= 0) {
            throw new \RuntimeException(__('Centro y usuario son obligatorios.', 'vfc-core'));
        }
        if ($this->isAdminOfCentro($userId, $centroId)) {
            return;
        }
        global $wpdb;
        $table = Schema::table(Schema::TABLE_CENTRO_ADMINS);
        $wpdb->insert(
            $table,
            [
                'centro_id' => $centroId,
                'user_id' => $userId,
                'created_at' => current_time('mysql', true),
            ],
            ['%d', '%d', '%s']
        );
        AuditService::log('assign', self::ENTIDAD, null, [
            'centro_id' => $centroId,
            'user_id' => $userId,
        ], $centroId);
    }

    public function unassign(int $centroId, int $userId): void
    {
        global $wpdb;
        $table = Schema::table(Schema::TABLE_CENTRO_ADMINS);
        $wpdb->delete(
            $table,
            ['centro_id' => $centroId, 'user_id' => $userId],
            ['%d', '%d']
        );
        AuditService::log('unassign', self::ENTIDAD, null, [
            'centro_id' => $centroId,
            'user_id' => $userId,
        ], $centroId);
    }

    public function isAdminOfCentro(int $userId, int $centroId): bool
    {
        global $wpdb;
        $table = Schema::table(Schema::TABLE_CENTRO_ADMINS);
        $count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE centro_id = %d AND user_id = %d",
            $centroId,
            $userId
        ));
        return $count > 0;
    }

    /**
     * @return array<int, int>
     */
    public function listCentrosForUser(int $userId): array
    {
        global $wpdb;
        $table = Schema::table(Schema::TABLE_CENTRO_ADMINS);
        $rows = $wpdb->get_col($wpdb->prepare(
            "SELECT centro_id FROM {$table} WHERE user_id = %d ORDER BY centro_id ASC",
            $userId
        ));
        return array_map('intval', (array) $rows);
    }

    /**
     * @return array<int, int>
     */
    public function listAdminsForCentro(int $centroId): array
    {
        global $wpdb;
        $table = Schema::table(Schema::TABLE_CENTRO_ADMINS);
        $rows = $wpdb->get_col($wpdb->prepare(
            "SELECT user_id FROM {$table} WHERE centro_id = %d ORDER BY user_id ASC",
            $centroId
        ));
        return array_map('intval', (array) $rows);
    }
}

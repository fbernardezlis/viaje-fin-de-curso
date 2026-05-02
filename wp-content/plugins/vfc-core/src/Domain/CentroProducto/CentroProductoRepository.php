<?php
declare(strict_types=1);

namespace VFC\Core\Domain\CentroProducto;

use VFC\Core\Database\Schema;
use VFC\Core\Services\AuditService;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Estado de productos por centro.
 *
 * Convención:
 *  - Por defecto, todos los productos están activos para todos los centros.
 *  - En la tabla solo se guardan filas para *cambios* (activo=0 ó activo=1 explícito).
 *  - Una fila con `activo=0` es una exclusión: el producto NO se muestra para ese centro.
 */
final class CentroProductoRepository
{
    private const ENTIDAD = 'centro_producto';

    /**
     * @return array<int, bool> Mapa product_id => activo (true|false). Productos no presentes => activos por defecto.
     */
    public function statusMapForCentro(int $centroId): array
    {
        global $wpdb;
        $table = Schema::table(Schema::TABLE_CENTRO_PRODUCTOS);
        $rows = $wpdb->get_results(
            $wpdb->prepare("SELECT product_id, activo FROM {$table} WHERE centro_id = %d", $centroId),
            ARRAY_A
        );
        $map = [];
        foreach ((array) $rows as $row) {
            $map[(int) $row['product_id']] = (int) $row['activo'] === 1;
        }
        return $map;
    }

    public function isActivoForCentro(int $centroId, int $productId): bool
    {
        $map = $this->statusMapForCentro($centroId);
        return $map[$productId] ?? true;
    }

    /**
     * Establece el estado de un producto en un centro.
     */
    public function setEstado(int $centroId, int $productId, bool $activo): void
    {
        global $wpdb;
        $table = Schema::table(Schema::TABLE_CENTRO_PRODUCTOS);

        $existing = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, activo FROM {$table} WHERE centro_id = %d AND product_id = %d",
                $centroId,
                $productId
            ),
            ARRAY_A
        );

        $now = current_time('mysql', true);

        if ($existing === null) {
            $wpdb->insert(
                $table,
                [
                    'centro_id' => $centroId,
                    'product_id' => $productId,
                    'activo' => $activo ? 1 : 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                ['%d', '%d', '%d', '%s', '%s']
            );
            AuditService::log('upsert', self::ENTIDAD, null, [
                'centro_id' => $centroId,
                'product_id' => $productId,
                'activo' => $activo,
                'before' => null,
            ], $centroId);
            return;
        }

        $beforeActivo = (int) $existing['activo'] === 1;
        if ($beforeActivo === $activo) {
            return;
        }
        $wpdb->update(
            $table,
            ['activo' => $activo ? 1 : 0, 'updated_at' => $now],
            ['id' => (int) $existing['id']],
            ['%d', '%s'],
            ['%d']
        );
        AuditService::log('update', self::ENTIDAD, (int) $existing['id'], [
            'centro_id' => $centroId,
            'product_id' => $productId,
            'before' => $beforeActivo,
            'after' => $activo,
        ], $centroId);
    }
}

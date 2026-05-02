<?php
declare(strict_types=1);

namespace VFC\Core\Domain\Centro;

use VFC\Core\Database\Schema;
use VFC\Core\Services\AuditService;

if (!defined('ABSPATH')) {
    exit;
}

final class CentroRepository
{
    private const ENTIDAD = 'centro';

    public function find(int $id): ?Centro
    {
        global $wpdb;
        $table = Schema::table(Schema::TABLE_CENTROS);
        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id),
            ARRAY_A
        );
        return is_array($row) ? Centro::fromRow($row) : null;
    }

    public function findBySlug(string $slug): ?Centro
    {
        global $wpdb;
        $table = Schema::table(Schema::TABLE_CENTROS);
        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE slug = %s", $slug),
            ARRAY_A
        );
        return is_array($row) ? Centro::fromRow($row) : null;
    }

    /**
     * Lista paginada / filtrada.
     *
     * @param array{search?: string, estado?: string, per_page?: int, page?: int, orderby?: string, order?: string} $args
     * @return array{items: array<int, Centro>, total: int}
     */
    public function list(array $args = []): array
    {
        global $wpdb;
        $table = Schema::table(Schema::TABLE_CENTROS);

        $perPage = max(1, (int) ($args['per_page'] ?? 20));
        $page = max(1, (int) ($args['page'] ?? 1));
        $offset = ($page - 1) * $perPage;

        $where = ['1=1'];
        $params = [];

        if (!empty($args['search'])) {
            $like = '%' . $wpdb->esc_like((string) $args['search']) . '%';
            $where[] = '(nombre LIKE %s OR slug LIKE %s OR email LIKE %s)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        if (!empty($args['estado'])) {
            $where[] = 'estado = %s';
            $params[] = (string) $args['estado'];
        }

        $orderbyRequested = (string) ($args['orderby'] ?? 'nombre');
        $orderby = in_array($orderbyRequested, ['id', 'nombre', 'slug', 'estado', 'created_at'], true)
            ? $orderbyRequested
            : 'nombre';
        $order = strtoupper((string) ($args['order'] ?? 'ASC')) === 'DESC' ? 'DESC' : 'ASC';

        $whereSql = implode(' AND ', $where);

        $totalSql = "SELECT COUNT(*) FROM {$table} WHERE {$whereSql}";
        $total = (int) ($params === []
            ? $wpdb->get_var($totalSql)
            : $wpdb->get_var($wpdb->prepare($totalSql, $params)));

        $listSql = "SELECT * FROM {$table} WHERE {$whereSql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
        $listParams = array_merge($params, [$perPage, $offset]);
        $rows = $wpdb->get_results($wpdb->prepare($listSql, $listParams), ARRAY_A);

        $items = [];
        foreach ((array) $rows as $row) {
            $items[] = Centro::fromRow($row);
        }

        return ['items' => $items, 'total' => $total];
    }

    /**
     * Inserta o actualiza un centro y devuelve la versión persistida.
     *
     * @throws \RuntimeException si el slug ya existe en otro centro o falla la BD.
     */
    public function save(Centro $centro): Centro
    {
        global $wpdb;
        $table = Schema::table(Schema::TABLE_CENTROS);

        $slug = $centro->slug !== '' ? $centro->slug : sanitize_title($centro->nombre);

        $existing = $this->findBySlug($slug);
        if ($existing !== null && $existing->id !== $centro->id) {
            throw new \RuntimeException(__('Ya existe un centro con ese identificador (slug).', 'vfc-core'));
        }

        $now = current_time('mysql', true);
        $data = [
            'nombre' => $centro->nombre,
            'slug' => $slug,
            'cif' => $centro->cif,
            'email' => $centro->email,
            'telefono' => $centro->telefono,
            'direccion' => $centro->direccion,
            'estado' => $centro->estado !== '' ? $centro->estado : 'activo',
            'updated_at' => $now,
        ];
        $formats = ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'];

        if ($centro->id === null) {
            $data['created_at'] = $now;
            $formats[] = '%s';
            $result = $wpdb->insert($table, $data, $formats);
            if ($result === false) {
                throw new \RuntimeException(__('No se pudo crear el centro.', 'vfc-core'));
            }
            $id = (int) $wpdb->insert_id;
            $saved = $this->find($id);
            if ($saved === null) {
                throw new \RuntimeException(__('Centro no encontrado tras insertar.', 'vfc-core'));
            }
            AuditService::log('create', self::ENTIDAD, $id, ['after' => $saved->toArray()], $id);
            return $saved;
        }

        $before = $this->find($centro->id);

        $result = $wpdb->update($table, $data, ['id' => $centro->id], $formats, ['%d']);
        if ($result === false) {
            throw new \RuntimeException(__('No se pudo actualizar el centro.', 'vfc-core'));
        }

        $saved = $this->find($centro->id);
        if ($saved === null) {
            throw new \RuntimeException(__('Centro no encontrado tras actualizar.', 'vfc-core'));
        }

        AuditService::log(
            'update',
            self::ENTIDAD,
            $centro->id,
            [
                'before' => $before?->toArray(),
                'after' => $saved->toArray(),
            ],
            $centro->id
        );

        return $saved;
    }

    public function delete(int $id): bool
    {
        global $wpdb;
        $table = Schema::table(Schema::TABLE_CENTROS);

        $before = $this->find($id);
        if ($before === null) {
            return false;
        }

        $result = $wpdb->delete($table, ['id' => $id], ['%d']);
        if ($result === false) {
            return false;
        }

        AuditService::log('delete', self::ENTIDAD, $id, ['before' => $before->toArray()], $id);
        return true;
    }
}

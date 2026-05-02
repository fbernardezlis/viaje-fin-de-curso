<?php
declare(strict_types=1);

namespace VFC\Core\Domain\Edicion;

use VFC\Core\Database\Schema;
use VFC\Core\Services\AuditService;

if (!defined('ABSPATH')) {
    exit;
}

final class EdicionRepository
{
    private const ENTIDAD = 'edicion';

    public function find(int $id): ?Edicion
    {
        global $wpdb;
        $table = Schema::table(Schema::TABLE_EDICIONES);
        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id),
            ARRAY_A
        );
        return is_array($row) ? Edicion::fromRow($row) : null;
    }

    /**
     * @param array{search?: string, estado?: string, centro_id?: int, per_page?: int, page?: int, orderby?: string, order?: string} $args
     * @return array{items: array<int, Edicion>, total: int}
     */
    public function list(array $args = []): array
    {
        global $wpdb;
        $table = Schema::table(Schema::TABLE_EDICIONES);

        $perPage = max(1, (int) ($args['per_page'] ?? 20));
        $page = max(1, (int) ($args['page'] ?? 1));
        $offset = ($page - 1) * $perPage;

        $where = ['1=1'];
        $params = [];

        if (!empty($args['search'])) {
            $like = '%' . $wpdb->esc_like((string) $args['search']) . '%';
            $where[] = 'nombre LIKE %s';
            $params[] = $like;
        }
        if (!empty($args['estado']) && EstadoEdicion::isValid((string) $args['estado'])) {
            $where[] = 'estado = %s';
            $params[] = (string) $args['estado'];
        }
        if (!empty($args['centro_id'])) {
            $where[] = 'centro_id = %d';
            $params[] = (int) $args['centro_id'];
        }

        $orderbyRequested = (string) ($args['orderby'] ?? 'created_at');
        $orderby = in_array(
            $orderbyRequested,
            ['id', 'nombre', 'estado', 'centro_id', 'created_at', 'fecha_inicio', 'fecha_fin'],
            true
        ) ? $orderbyRequested : 'created_at';
        $order = strtoupper((string) ($args['order'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

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
            $items[] = Edicion::fromRow($row);
        }
        return ['items' => $items, 'total' => $total];
    }

    /**
     * Inserta o actualiza la edición. La transición de estado se hace con `transitionTo()`.
     *
     * @throws \RuntimeException
     */
    public function save(Edicion $edicion): Edicion
    {
        global $wpdb;
        $table = Schema::table(Schema::TABLE_EDICIONES);

        if ($edicion->nombre === '') {
            throw new \RuntimeException(__('El nombre de la edición es obligatorio.', 'vfc-core'));
        }
        if ($edicion->centroId <= 0) {
            throw new \RuntimeException(__('Debes asignar un centro a la edición.', 'vfc-core'));
        }
        if (!EstadoEdicion::isValid($edicion->estado)) {
            throw new \RuntimeException(__('Estado de edición inválido.', 'vfc-core'));
        }

        $now = current_time('mysql', true);
        $data = [
            'centro_id' => $edicion->centroId,
            'nombre' => $edicion->nombre,
            'fecha_inicio' => $edicion->fechaInicio,
            'fecha_fin' => $edicion->fechaFin,
            'updated_at' => $now,
        ];
        $formats = ['%d', '%s', '%s', '%s', '%s'];

        if ($edicion->id === null) {
            $data['estado'] = $edicion->estado !== '' ? $edicion->estado : EstadoEdicion::BORRADOR;
            $data['created_at'] = $now;
            $formats[] = '%s';
            $formats[] = '%s';

            $result = $wpdb->insert($table, $data, $formats);
            if ($result === false) {
                throw new \RuntimeException(__('No se pudo crear la edición.', 'vfc-core'));
            }
            $id = (int) $wpdb->insert_id;
            $saved = $this->find($id);
            if ($saved === null) {
                throw new \RuntimeException(__('Edición no encontrada tras crear.', 'vfc-core'));
            }
            AuditService::log('create', self::ENTIDAD, $id, ['after' => $saved->toArray()], $edicion->centroId);
            return $saved;
        }

        $before = $this->find($edicion->id);

        $result = $wpdb->update($table, $data, ['id' => $edicion->id], $formats, ['%d']);
        if ($result === false) {
            throw new \RuntimeException(__('No se pudo actualizar la edición.', 'vfc-core'));
        }
        $saved = $this->find($edicion->id);
        if ($saved === null) {
            throw new \RuntimeException(__('Edición no encontrada tras actualizar.', 'vfc-core'));
        }

        AuditService::log(
            'update',
            self::ENTIDAD,
            $edicion->id,
            ['before' => $before?->toArray(), 'after' => $saved->toArray()],
            $edicion->centroId
        );
        return $saved;
    }

    /**
     * Cambia el estado de la edición aplicando la máquina de estados y auditando.
     *
     * @throws \RuntimeException si la transición no está permitida.
     */
    public function transitionTo(int $id, string $nuevoEstado): Edicion
    {
        global $wpdb;
        $table = Schema::table(Schema::TABLE_EDICIONES);

        $edicion = $this->find($id);
        if ($edicion === null) {
            throw new \RuntimeException(__('Edición no encontrada.', 'vfc-core'));
        }
        if (!EstadoEdicion::canTransition($edicion->estado, $nuevoEstado)) {
            throw new \RuntimeException(sprintf(
                /* translators: 1: current state, 2: target state */
                __('No se puede pasar de "%1$s" a "%2$s".', 'vfc-core'),
                $edicion->estado,
                $nuevoEstado
            ));
        }

        $data = [
            'estado' => $nuevoEstado,
            'updated_at' => current_time('mysql', true),
        ];
        $formats = ['%s', '%s'];

        if ($nuevoEstado === EstadoEdicion::APROBADA) {
            $data['aprobada_por'] = get_current_user_id() ?: null;
            $data['fecha_aprobacion'] = current_time('mysql', true);
            $formats[] = '%d';
            $formats[] = '%s';
        }

        $result = $wpdb->update($table, $data, ['id' => $id], $formats, ['%d']);
        if ($result === false) {
            throw new \RuntimeException(__('No se pudo actualizar el estado.', 'vfc-core'));
        }

        $saved = $this->find($id);
        if ($saved === null) {
            throw new \RuntimeException(__('Edición no encontrada tras transición.', 'vfc-core'));
        }

        AuditService::log(
            'transition',
            self::ENTIDAD,
            $id,
            [
                'from' => $edicion->estado,
                'to' => $nuevoEstado,
            ],
            $edicion->centroId
        );

        return $saved;
    }

    public function delete(int $id): bool
    {
        global $wpdb;
        $table = Schema::table(Schema::TABLE_EDICIONES);

        $before = $this->find($id);
        if ($before === null) {
            return false;
        }
        $result = $wpdb->delete($table, ['id' => $id], ['%d']);
        if ($result === false) {
            return false;
        }
        AuditService::log('delete', self::ENTIDAD, $id, ['before' => $before->toArray()], $before->centroId);
        return true;
    }
}

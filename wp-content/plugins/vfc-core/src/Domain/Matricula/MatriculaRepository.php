<?php
declare(strict_types=1);

namespace VFC\Core\Domain\Matricula;

use VFC\Core\Database\Schema;
use VFC\Core\Domain\Edicion\EdicionRepository;
use VFC\Core\Services\AuditService;
use VFC\Core\Services\QrTokenService;

if (!defined('ABSPATH')) {
    exit;
}

final class MatriculaRepository
{
    private const ENTIDAD = 'matricula';

    private QrTokenService $qr;
    private EdicionRepository $ediciones;

    public function __construct(?QrTokenService $qr = null, ?EdicionRepository $ediciones = null)
    {
        $this->qr = $qr ?? new QrTokenService();
        $this->ediciones = $ediciones ?? new EdicionRepository();
    }

    public function find(int $id): ?Matricula
    {
        global $wpdb;
        $table = Schema::table(Schema::TABLE_MATRICULAS);
        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id),
            ARRAY_A
        );
        return is_array($row) ? Matricula::fromRow($row) : null;
    }

    public function findByEdicionAndAlumno(int $edicionId, int $alumnoUserId): ?Matricula
    {
        global $wpdb;
        $table = Schema::table(Schema::TABLE_MATRICULAS);
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE edicion_id = %d AND alumno_user_id = %d",
                $edicionId,
                $alumnoUserId
            ),
            ARRAY_A
        );
        return is_array($row) ? Matricula::fromRow($row) : null;
    }

    public function findByTokenHash(string $hash): ?Matricula
    {
        global $wpdb;
        $table = Schema::table(Schema::TABLE_MATRICULAS);
        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE qr_token_hash = %s", $hash),
            ARRAY_A
        );
        return is_array($row) ? Matricula::fromRow($row) : null;
    }

    /**
     * @return array<int, Matricula>
     */
    public function listByEdicion(int $edicionId): array
    {
        global $wpdb;
        $table = Schema::table(Schema::TABLE_MATRICULAS);
        $rows = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$table} WHERE edicion_id = %d ORDER BY alias ASC", $edicionId),
            ARRAY_A
        );
        $out = [];
        foreach ((array) $rows as $row) {
            $out[] = Matricula::fromRow($row);
        }
        return $out;
    }

    /**
     * @return array<int, Matricula>
     */
    public function listByAlumno(int $alumnoUserId): array
    {
        global $wpdb;
        $table = Schema::table(Schema::TABLE_MATRICULAS);
        $rows = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$table} WHERE alumno_user_id = %d ORDER BY id DESC", $alumnoUserId),
            ARRAY_A
        );
        $out = [];
        foreach ((array) $rows as $row) {
            $out[] = Matricula::fromRow($row);
        }
        return $out;
    }

    /**
     * Crea matrícula nueva. Genera token QR y devuelve la matrícula + token en claro.
     *
     * @return array{matricula: Matricula, token: string}
     * @throws \RuntimeException
     */
    public function create(int $edicionId, int $alumnoUserId, string $alias): array
    {
        global $wpdb;
        $table = Schema::table(Schema::TABLE_MATRICULAS);

        $alias = sanitize_text_field($alias);
        if ($alias === '') {
            throw new \RuntimeException(__('El alias es obligatorio.', 'vfc-core'));
        }
        if ($edicionId <= 0 || $alumnoUserId <= 0) {
            throw new \RuntimeException(__('Edición y alumno son obligatorios.', 'vfc-core'));
        }
        if ($this->findByEdicionAndAlumno($edicionId, $alumnoUserId) !== null) {
            throw new \RuntimeException(__('Este alumno ya está matriculado en esta edición.', 'vfc-core'));
        }

        $pair = $this->qr->generate();

        $now = current_time('mysql', true);
        $result = $wpdb->insert(
            $table,
            [
                'edicion_id' => $edicionId,
                'alumno_user_id' => $alumnoUserId,
                'alias' => $alias,
                'qr_token_hash' => $pair['hash'],
                'creado_por' => get_current_user_id() ?: null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ['%d', '%d', '%s', '%s', '%d', '%s', '%s']
        );
        if ($result === false) {
            throw new \RuntimeException(__('No se pudo crear la matrícula.', 'vfc-core'));
        }
        $id = (int) $wpdb->insert_id;
        $matricula = $this->find($id);
        if ($matricula === null) {
            throw new \RuntimeException(__('Matrícula no encontrada tras crear.', 'vfc-core'));
        }

        $edicion = $this->ediciones->find($edicionId);
        AuditService::log('create', self::ENTIDAD, $id, [
            'edicion_id' => $edicionId,
            'alumno_user_id' => $alumnoUserId,
            'alias' => $alias,
        ], $edicion?->centroId);

        return ['matricula' => $matricula, 'token' => $pair['token']];
    }

    public function updateAlias(int $id, string $alias): Matricula
    {
        global $wpdb;
        $table = Schema::table(Schema::TABLE_MATRICULAS);

        $matricula = $this->find($id);
        if ($matricula === null) {
            throw new \RuntimeException(__('Matrícula no encontrada.', 'vfc-core'));
        }
        $alias = sanitize_text_field($alias);
        if ($alias === '') {
            throw new \RuntimeException(__('Alias inválido.', 'vfc-core'));
        }

        $wpdb->update(
            $table,
            ['alias' => $alias, 'updated_at' => current_time('mysql', true)],
            ['id' => $id],
            ['%s', '%s'],
            ['%d']
        );

        $saved = $this->find($id);
        $edicion = $this->ediciones->find($matricula->edicionId);
        AuditService::log('update_alias', self::ENTIDAD, $id, [
            'before' => $matricula->alias,
            'after' => $alias,
        ], $edicion?->centroId);

        return $saved ?? $matricula;
    }

    /**
     * Rota el token (cuando hay reenvío).
     *
     * @return string token en claro recién generado
     */
    public function rotateToken(int $id): string
    {
        global $wpdb;
        $table = Schema::table(Schema::TABLE_MATRICULAS);

        $matricula = $this->find($id);
        if ($matricula === null) {
            throw new \RuntimeException(__('Matrícula no encontrada.', 'vfc-core'));
        }

        $pair = $this->qr->generate();

        $wpdb->update(
            $table,
            ['qr_token_hash' => $pair['hash'], 'updated_at' => current_time('mysql', true)],
            ['id' => $id],
            ['%s', '%s'],
            ['%d']
        );

        $edicion = $this->ediciones->find($matricula->edicionId);
        AuditService::log('rotate_qr_token', self::ENTIDAD, $id, [], $edicion?->centroId);

        return $pair['token'];
    }

    public function delete(int $id): bool
    {
        global $wpdb;
        $table = Schema::table(Schema::TABLE_MATRICULAS);

        $matricula = $this->find($id);
        if ($matricula === null) {
            return false;
        }
        $result = $wpdb->delete($table, ['id' => $id], ['%d']);
        if ($result === false) {
            return false;
        }
        $edicion = $this->ediciones->find($matricula->edicionId);
        AuditService::log('delete', self::ENTIDAD, $id, [
            'edicion_id' => $matricula->edicionId,
            'alumno_user_id' => $matricula->alumnoUserId,
        ], $edicion?->centroId);
        return true;
    }
}

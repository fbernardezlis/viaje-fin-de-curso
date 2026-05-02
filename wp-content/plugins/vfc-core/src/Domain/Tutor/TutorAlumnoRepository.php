<?php
declare(strict_types=1);

namespace VFC\Core\Domain\Tutor;

use VFC\Core\Database\Schema;
use VFC\Core\Services\AuditService;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Vincula tutores con alumnos (relación N:N) en `wp_vfc_tutor_alumno`.
 */
final class TutorAlumnoRepository
{
    private const ENTIDAD = 'tutor_alumno';

    public function link(int $tutorUserId, int $alumnoUserId): void
    {
        if ($tutorUserId <= 0 || $alumnoUserId <= 0) {
            throw new \RuntimeException(__('Tutor y alumno son obligatorios.', 'vfc-core'));
        }
        if ($this->isLinked($tutorUserId, $alumnoUserId)) {
            return;
        }
        global $wpdb;
        $table = Schema::table(Schema::TABLE_TUTOR_ALUMNO);
        $now = current_time('mysql', true);
        $wpdb->insert(
            $table,
            [
                'tutor_user_id' => $tutorUserId,
                'alumno_user_id' => $alumnoUserId,
                'created_at' => $now,
            ],
            ['%d', '%d', '%s']
        );
        AuditService::log('link', self::ENTIDAD, null, [
            'tutor_user_id' => $tutorUserId,
            'alumno_user_id' => $alumnoUserId,
        ]);
    }

    public function unlink(int $tutorUserId, int $alumnoUserId): void
    {
        global $wpdb;
        $table = Schema::table(Schema::TABLE_TUTOR_ALUMNO);
        $wpdb->delete(
            $table,
            ['tutor_user_id' => $tutorUserId, 'alumno_user_id' => $alumnoUserId],
            ['%d', '%d']
        );
        AuditService::log('unlink', self::ENTIDAD, null, [
            'tutor_user_id' => $tutorUserId,
            'alumno_user_id' => $alumnoUserId,
        ]);
    }

    public function isLinked(int $tutorUserId, int $alumnoUserId): bool
    {
        global $wpdb;
        $table = Schema::table(Schema::TABLE_TUTOR_ALUMNO);
        $count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE tutor_user_id = %d AND alumno_user_id = %d",
            $tutorUserId,
            $alumnoUserId
        ));
        return $count > 0;
    }

    /**
     * @return array<int, int> Lista de alumno_user_id vinculados al tutor.
     */
    public function alumnosForTutor(int $tutorUserId): array
    {
        global $wpdb;
        $table = Schema::table(Schema::TABLE_TUTOR_ALUMNO);
        $rows = $wpdb->get_col($wpdb->prepare(
            "SELECT alumno_user_id FROM {$table} WHERE tutor_user_id = %d ORDER BY alumno_user_id ASC",
            $tutorUserId
        ));
        return array_map('intval', (array) $rows);
    }

    /**
     * @return array<int, int> Lista de tutor_user_id vinculados al alumno.
     */
    public function tutoresForAlumno(int $alumnoUserId): array
    {
        global $wpdb;
        $table = Schema::table(Schema::TABLE_TUTOR_ALUMNO);
        $rows = $wpdb->get_col($wpdb->prepare(
            "SELECT tutor_user_id FROM {$table} WHERE alumno_user_id = %d ORDER BY tutor_user_id ASC",
            $alumnoUserId
        ));
        return array_map('intval', (array) $rows);
    }
}

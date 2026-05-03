<?php
declare(strict_types=1);

namespace VFC\Portal\Rest;

use VFC\Core\Domain\Centro\CentroRepository;
use VFC\Core\Domain\Edicion\EdicionRepository;
use VFC\Core\Domain\Matricula\MatriculaRepository;
use VFC\Core\Domain\Tutor\TutorAlumnoRepository;
use VFC\Portal\Routing\Permissions;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * GET /vfc/v1/portal/mis-alumnos -> alumnos vinculados al tutor logueado.
 */
final class MisAlumnosController
{
    private const NS = 'vfc/v1/portal';

    public function register(): void
    {
        register_rest_route(self::NS, '/mis-alumnos', [
            'methods' => 'GET',
            'callback' => [$this, 'get'],
            'permission_callback' => [$this, 'authorize'],
        ]);
    }

    public function authorize(): bool|\WP_Error
    {
        if (!is_user_logged_in()) {
            return new \WP_Error('rest_forbidden', __('Sesión requerida.', 'vfc-portal'), ['status' => 401]);
        }
        $user = wp_get_current_user();
        if (!Permissions::isTutor($user) && !Permissions::isSuperAdmin($user)) {
            return new \WP_Error('rest_forbidden', __('Solo tutores.', 'vfc-portal'), ['status' => 403]);
        }
        return true;
    }

    public function get(\WP_REST_Request $request): \WP_REST_Response
    {
        $user = wp_get_current_user();
        $tutorRepo = new TutorAlumnoRepository();
        $matrRepo = new MatriculaRepository();
        $edRepo = new EdicionRepository();
        $centroRepo = new CentroRepository();

        $alumnoIds = $tutorRepo->alumnosForTutor((int) $user->ID);
        $items = [];
        foreach ($alumnoIds as $alumnoId) {
            $alumno = get_user_by('id', $alumnoId);
            if (!$alumno instanceof \WP_User) {
                continue;
            }
            $matriculas = $matrRepo->listByAlumno($alumnoId);
            $matEntries = [];
            foreach ($matriculas as $m) {
                $ed = $edRepo->find((int) $m->edicionId);
                $centro = $ed ? $centroRepo->find((int) $ed->centroId) : null;
                $matEntries[] = [
                    'matricula_id' => (int) $m->id,
                    'edicion_id' => (int) $m->edicionId,
                    'edicion' => $ed?->nombre,
                    'edicion_estado' => $ed?->estado,
                    'centro_id' => $ed?->centroId,
                    'centro' => $centro?->nombre,
                    'alias' => $m->alias,
                ];
            }
            $items[] = [
                'alumno_id' => (int) $alumno->ID,
                'display_name' => $alumno->display_name ?: $alumno->user_login,
                'email' => $alumno->user_email,
                'matriculas' => $matEntries,
            ];
        }

        return new \WP_REST_Response(['items' => $items]);
    }
}

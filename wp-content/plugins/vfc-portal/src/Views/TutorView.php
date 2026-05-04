<?php
declare(strict_types=1);

namespace VFC\Portal\Views;

use VFC\Core\Domain\Centro\CentroRepository;
use VFC\Core\Domain\Edicion\EdicionRepository;
use VFC\Core\Domain\Matricula\MatriculaRepository;
use VFC\Core\Domain\Saldo\SaldoRepository;
use VFC\Core\Services\QrTokenService;
use VFC\Core\Domain\Tutor\TutorAlumnoRepository;
use VFC\Portal\Routing\Permissions;

if (!defined('ABSPATH')) {
    exit;
}

final class TutorView
{
    public function render(string $param = ''): void
    {
        $user = Permissions::requireLogin();

        if (!Permissions::isTutor($user) && !Permissions::isSuperAdmin($user)) {
            wp_safe_redirect(home_url('/portal/login'));
            exit;
        }

        $tutorRepo = new TutorAlumnoRepository();
        $matrRepo = new MatriculaRepository();
        $edRepo = new EdicionRepository();
        $centroRepo = new CentroRepository();
        $saldoRepo = new SaldoRepository();

        $alumnoIds = $tutorRepo->alumnosForTutor((int) $user->ID);

        $alumnos = [];
        foreach ($alumnoIds as $alumnoId) {
            $u = get_user_by('id', $alumnoId);
            if (!$u instanceof \WP_User) {
                continue;
            }
            $alumnos[] = [
                'id' => (int) $u->ID,
                'display_name' => $u->display_name ?: $u->user_login,
                'email' => $u->user_email,
            ];
        }

        // Si no hay parametro, escogemos el primer alumno por defecto.
        $selectedId = (int) ($param !== '' ? $param : ($alumnos[0]['id'] ?? 0));
        if ($selectedId > 0 && !Permissions::canSeeAlumno($user, $selectedId, $tutorRepo)) {
            wp_safe_redirect(home_url('/portal/tutor'));
            exit;
        }

        $detail = null;
        if ($selectedId > 0) {
            $matriculas = $matrRepo->listByAlumno($selectedId);
            $qrSvc = new QrTokenService();
            $matriculasView = [];
            foreach ($matriculas as $m) {
                $mid = (int) $m->id;
                $ed = $edRepo->find((int) $m->edicionId);
                $centro = $ed ? $centroRepo->find((int) $ed->centroId) : null;
                $plain = $matrRepo->getPlainQrToken($mid);
                $matriculasView[] = [
                    'matricula_id' => $mid,
                    'edicion_id' => (int) $m->edicionId,
                    'edicion' => $ed?->nombre ?? '—',
                    'edicion_estado' => $ed?->estado ?? '',
                    'centro' => $centro?->nombre ?? '—',
                    'alias' => $m->alias,
                    'qr_image_url' => home_url('/portal/qr-image/' . $mid . '.png'),
                    'qr_link_url' => $qrSvc->urlForToken($plain),
                ];
            }
            $alumnoUser = get_user_by('id', $selectedId);
            $edicionesFiltro = [];
            foreach ($matriculasView as $mv) {
                $edicionesFiltro[(int) $mv['edicion_id']] = (string) $mv['edicion'];
            }
            $hf = HistorialRequestParams::fromRequest();
            $detail = [
                'alumno' => [
                    'id' => $selectedId,
                    'display_name' => $alumnoUser instanceof \WP_User ? ($alumnoUser->display_name ?: $alumnoUser->user_login) : '—',
                    'email' => $alumnoUser instanceof \WP_User ? $alumnoUser->user_email : null,
                ],
                'matriculas' => $matriculasView,
                'ediciones_filtro' => $edicionesFiltro,
                'saldo' => $saldoRepo->saldoNeto($selectedId),
                'historial' => $saldoRepo->historial(array_merge([
                    'alumno_user_id' => $selectedId,
                    'per_page' => 25,
                    'page' => max(1, (int) ($_GET['page'] ?? 1)),
                ], $hf)),
                'historial_hf' => $hf,
                'historial_hf_query' => HistorialRequestParams::preservationQuery($hf),
            ];
        }

        Layout::render(__('Mis tutorados', 'vfc-portal'), 'tutor', [
            'tutor' => [
                'id' => (int) $user->ID,
                'display_name' => $user->display_name ?: $user->user_login,
            ],
            'alumnos' => $alumnos,
            'selected_id' => $selectedId,
            'detail' => $detail,
        ]);
    }
}

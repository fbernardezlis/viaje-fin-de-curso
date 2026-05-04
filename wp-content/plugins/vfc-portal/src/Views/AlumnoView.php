<?php
declare(strict_types=1);

namespace VFC\Portal\Views;

use VFC\Core\Domain\Centro\CentroRepository;
use VFC\Core\Domain\Edicion\EdicionRepository;
use VFC\Core\Domain\Matricula\MatriculaRepository;
use VFC\Core\Domain\Saldo\SaldoRepository;
use VFC\Core\Services\QrTokenService;
use VFC\Portal\Routing\Permissions;

if (!defined('ABSPATH')) {
    exit;
}

final class AlumnoView
{
    public function render(string $param = ''): void
    {
        $user = Permissions::requireLogin();

        // El parametro permite que el tutor vea un alumno concreto via /portal/tutor/{alumnoId}
        // pero la vista del alumno propia ignora el parametro.
        $alumnoId = (int) $user->ID;

        if (!Permissions::isAlumno($user) && !Permissions::isSuperAdmin($user)) {
            wp_safe_redirect(home_url('/portal/login'));
            exit;
        }

        $matrRepo = new MatriculaRepository();
        $edRepo = new EdicionRepository();
        $centroRepo = new CentroRepository();
        $saldoRepo = new SaldoRepository();

        $matriculas = $matrRepo->listByAlumno($alumnoId);
        $qrSvc = new QrTokenService();
        $matriculasView = [];
        $edicionesFiltro = [];
        foreach ($matriculas as $m) {
            $mid = (int) $m->id;
            $ed = $edRepo->find((int) $m->edicionId);
            $centro = $ed ? $centroRepo->find((int) $ed->centroId) : null;
            $plain = $matrRepo->getPlainQrToken($mid);
            $eid = (int) $m->edicionId;
            $edicionesFiltro[$eid] = $ed?->nombre ?? '—';
            $matriculasView[] = [
                'matricula_id' => $mid,
                'edicion_id' => $eid,
                'edicion' => $ed?->nombre ?? '—',
                'edicion_estado' => $ed?->estado ?? '',
                'centro' => $centro?->nombre ?? '—',
                'alias' => $m->alias,
                'qr_image_url' => home_url('/portal/qr-image/' . $mid . '.png'),
                'qr_link_url' => $qrSvc->urlForToken($plain),
            ];
        }

        $hf = HistorialRequestParams::fromRequest();
        $saldo = $saldoRepo->saldoNeto($alumnoId);
        $historial = $saldoRepo->historial(array_merge([
            'alumno_user_id' => $alumnoId,
            'per_page' => 25,
            'page' => max(1, (int) ($_GET['page'] ?? 1)),
        ], $hf));

        Layout::render(__('Mi cuenta', 'vfc-portal'), 'alumno', [
            'alumno' => [
                'id' => $alumnoId,
                'display_name' => $user->display_name ?: $user->user_login,
                'email' => $user->user_email,
            ],
            'matriculas' => $matriculasView,
            'ediciones_filtro' => $edicionesFiltro,
            'saldo' => $saldo,
            'historial' => $historial,
            'historial_hf' => $hf,
            'historial_hf_query' => HistorialRequestParams::preservationQuery($hf),
        ]);
    }
}

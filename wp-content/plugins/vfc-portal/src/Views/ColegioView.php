<?php
declare(strict_types=1);

namespace VFC\Portal\Views;

use VFC\Core\Domain\Centro\CentroAdminRepository;
use VFC\Core\Domain\Centro\CentroRepository;
use VFC\Core\Domain\CentroProducto\CentroProductoRepository;
use VFC\Core\Domain\Edicion\EdicionRepository;
use VFC\Core\Domain\Liquidacion\LiquidacionRepository;
use VFC\Core\Domain\Matricula\MatriculaRepository;
use VFC\Portal\Routing\Permissions;
use VFC\Portal\Services\DashboardService;

if (!defined('ABSPATH')) {
    exit;
}

final class ColegioView
{
    public function render(string $param = ''): void
    {
        $user = Permissions::requireLogin();

        if (!Permissions::isAdminColegio($user) && !Permissions::isSuperAdmin($user)) {
            wp_safe_redirect(home_url('/portal/login'));
            exit;
        }

        $centroAdmins = new CentroAdminRepository();
        $centroRepo = new CentroRepository();

        if (Permissions::isSuperAdmin($user)) {
            $centrosList = $centroRepo->list(['per_page' => 100]);
            $centroIds = array_map(static fn($c) => (int) $c->id, $centrosList['items']);
        } else {
            $centroIds = $centroAdmins->listCentrosForUser((int) $user->ID);
        }

        $centros = [];
        foreach ($centroIds as $id) {
            $c = $centroRepo->find((int) $id);
            if ($c !== null) {
                $centros[] = ['id' => (int) $c->id, 'nombre' => (string) $c->nombre];
            }
        }
        if ($centros === []) {
            Layout::render(__('Panel del centro', 'vfc-portal'), 'colegio', [
                'centros' => [],
                'selected_id' => 0,
                'detail' => null,
            ]);
            return;
        }

        $selectedId = $param !== '' ? (int) $param : (int) $centros[0]['id'];
        $allowed = array_column($centros, 'id');
        if (!in_array($selectedId, array_map('intval', $allowed), true)) {
            wp_safe_redirect(home_url('/portal/colegio'));
            exit;
        }

        $service = new DashboardService(
            $centroRepo,
            new EdicionRepository(),
            new MatriculaRepository(),
            new CentroProductoRepository(),
            new LiquidacionRepository()
        );
        $detail = $service->snapshot($selectedId);

        Layout::render(__('Panel del centro', 'vfc-portal'), 'colegio', [
            'centros' => $centros,
            'selected_id' => $selectedId,
            'detail' => $detail,
        ]);
    }
}

<?php
declare(strict_types=1);

namespace VFC\Portal\Services;

use VFC\Core\Domain\Centro\CentroRepository;
use VFC\Core\Domain\CentroProducto\CentroProductoRepository;
use VFC\Core\Domain\Edicion\EdicionRepository;
use VFC\Core\Domain\Liquidacion\LiquidacionRepository;
use VFC\Core\Domain\Matricula\MatriculaRepository;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Compone los datos del dashboard del admin de colegio (lectura).
 */
final class DashboardService
{
    public function __construct(
        private CentroRepository $centros,
        private EdicionRepository $ediciones,
        private MatriculaRepository $matriculas,
        private CentroProductoRepository $centroProductos,
        private LiquidacionRepository $liquidaciones
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function snapshot(int $centroId): ?array
    {
        $centro = $this->centros->find($centroId);
        if ($centro === null) {
            return null;
        }

        $edicionesList = $this->ediciones->list(['centro_id' => $centroId, 'per_page' => 50]);
        $alumnos = [];
        $matriculasCount = 0;
        foreach ($edicionesList['items'] as $ed) {
            $mats = $this->matriculas->listByEdicion((int) $ed->id);
            $matriculasCount += count($mats);
            foreach ($mats as $m) {
                if (!isset($alumnos[$m->alumnoUserId])) {
                    $u = get_user_by('id', $m->alumnoUserId);
                    $alumnos[$m->alumnoUserId] = [
                        'alumno_id' => (int) $m->alumnoUserId,
                        'display_name' => $u instanceof \WP_User ? ($u->display_name ?: $u->user_login) : '—',
                        'email' => $u instanceof \WP_User ? $u->user_email : null,
                        'matriculas' => [],
                    ];
                }
                $alumnos[$m->alumnoUserId]['matriculas'][] = [
                    'matricula_id' => (int) $m->id,
                    'edicion_id' => (int) $m->edicionId,
                    'edicion' => $ed->nombre,
                    'edicion_estado' => $ed->estado,
                    'alias' => $m->alias,
                ];
            }
        }

        $statusMap = $this->centroProductos->statusMapForCentro($centroId);
        $productosOverrides = [];
        foreach ($statusMap as $productId => $activo) {
            $product = function_exists('wc_get_product') ? wc_get_product($productId) : null;
            $productosOverrides[] = [
                'product_id' => (int) $productId,
                'activo' => (bool) $activo,
                'name' => $product ? $product->get_name() : ('#' . $productId),
            ];
        }

        $liquidaciones = array_map(static function ($l) {
            return [
                'id' => (int) $l->id,
                'fecha' => $l->fecha,
                'importe' => (float) $l->importe,
                'referencia' => $l->referencia,
                'estado' => $l->estado,
            ];
        }, $this->liquidaciones->listByCentro($centroId));

        $totalLiquidado = array_sum(array_map(static fn($l) => (float) $l['importe'], $liquidaciones));

        return [
            'centro' => [
                'id' => (int) $centro->id,
                'nombre' => $centro->nombre,
                'slug' => $centro->slug,
                'estado' => $centro->estado,
                'email' => $centro->email,
                'telefono' => $centro->telefono,
                'direccion' => $centro->direccion,
            ],
            'resumen' => [
                'ediciones' => count($edicionesList['items']),
                'alumnos' => count($alumnos),
                'matriculas' => $matriculasCount,
                'liquidaciones' => count($liquidaciones),
                'total_liquidado' => round($totalLiquidado, 4),
            ],
            'ediciones' => array_map(static fn($e) => [
                'id' => (int) $e->id,
                'nombre' => $e->nombre,
                'estado' => $e->estado,
                'fecha_inicio' => $e->fechaInicio,
                'fecha_fin' => $e->fechaFin,
            ], $edicionesList['items']),
            'alumnos' => array_values($alumnos),
            'productos_overrides' => $productosOverrides,
            'liquidaciones' => $liquidaciones,
        ];
    }
}

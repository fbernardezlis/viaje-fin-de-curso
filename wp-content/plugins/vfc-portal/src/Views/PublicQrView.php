<?php
declare(strict_types=1);

namespace VFC\Portal\Views;

use VFC\Core\Domain\Centro\CentroRepository;
use VFC\Core\Domain\Edicion\EdicionRepository;
use VFC\Core\Domain\Edicion\EstadoEdicion;
use VFC\Core\Domain\Matricula\MatriculaRepository;
use VFC\Core\Services\QrTokenService;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Pagina publica que aparece cuando alguien escanea el QR antes de aceptar la cookie.
 * Muestra alias + edicion + centro y un boton "Aceptar y comprar" que redirige al
 * endpoint /qr/{token} de Fase 2 (que es quien emite la cookie HMAC).
 */
final class PublicQrView
{
    public function render(string $token = ''): void
    {
        $matricula = null;
        $edicion = null;
        $centro = null;
        $invalid = false;
        $inactive = false;

        if ($token === '') {
            $invalid = true;
        } else {
            $hash = (new QrTokenService())->hash($token);
            $matricula = (new MatriculaRepository())->findByTokenHash($hash);
            if ($matricula === null) {
                $invalid = true;
            } else {
                $edicion = (new EdicionRepository())->find((int) $matricula->edicionId);
                if ($edicion === null) {
                    $invalid = true;
                } elseif ($edicion->estado !== EstadoEdicion::ACTIVA) {
                    $inactive = true;
                } else {
                    $centro = (new CentroRepository())->find((int) $edicion->centroId);
                }
            }
        }

        Layout::render(__('Comprar para un alumno', 'vfc-portal'), 'public-qr', [
            'token' => $token,
            'matricula' => $matricula,
            'edicion' => $edicion,
            'centro' => $centro,
            'invalid' => $invalid,
            'inactive' => $inactive,
        ]);
    }
}

<?php
declare(strict_types=1);

namespace VFC\Portal\Services;

use VFC\Core\Domain\Matricula\MatriculaRepository;
use VFC\Core\Services\QrTokenService;
use VFC\Portal\Routing\Permissions;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Genera la imagen PNG del QR de una matricula.
 *
 * Como en la BD solo guardamos el hash del token, NO podemos reconstruir el token original.
 * Por eso el codigo flujo es:
 *  - Si el solicitante tiene permiso (alumno o tutor de ese alumno), rotamos el token y devolvemos
 *    la imagen del nuevo. La rotacion invalida automaticamente cualquier QR anterior, lo que
 *    evita que se filtre por error y mantiene el principio de "no almacenar el token en claro".
 */
final class QrImageService
{
    private MatriculaRepository $matriculas;
    private QrTokenService $tokens;

    public function __construct(
        ?MatriculaRepository $matriculas = null,
        ?QrTokenService $tokens = null
    ) {
        $this->matriculas = $matriculas ?? new MatriculaRepository();
        $this->tokens = $tokens ?? new QrTokenService();
    }

    public function stream(int $matriculaId): void
    {
        if (!is_user_logged_in()) {
            status_header(401);
            exit;
        }
        $matricula = $this->matriculas->find($matriculaId);
        if ($matricula === null) {
            status_header(404);
            exit;
        }
        $user = wp_get_current_user();
        if (!Permissions::canSeeAlumno($user, (int) $matricula->alumnoUserId)) {
            status_header(403);
            exit;
        }

        $token = $this->matriculas->rotateToken((int) $matricula->id);
        $url = $this->tokens->urlForToken($token);

        $png = $this->renderPng($url);
        if ($png === '') {
            status_header(500);
            echo 'QR generation failed';
            exit;
        }

        nocache_headers();
        header('Content-Type: image/png');
        header('Content-Length: ' . strlen($png));
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        echo $png;
    }

    private function renderPng(string $data): string
    {
        if (class_exists(\Endroid\QrCode\Builder\Builder::class)) {
            try {
                $result = \Endroid\QrCode\Builder\Builder::create()
                    ->writer(new \Endroid\QrCode\Writer\PngWriter())
                    ->data($data)
                    ->size(360)
                    ->margin(12)
                    ->build();
                return $result->getString();
            } catch (\Throwable $e) {
                // fallback abajo
            }
        }
        // Fallback: servicio externo (solo desarrollo). Genera la imagen en cliente cuando falla.
        $remote = 'https://api.qrserver.com/v1/create-qr-code/?size=360x360&data=' . rawurlencode($data);
        $resp = wp_remote_get($remote, ['timeout' => 5]);
        if (is_wp_error($resp) || (int) wp_remote_retrieve_response_code($resp) !== 200) {
            return '';
        }
        return (string) wp_remote_retrieve_body($resp);
    }
}

<?php
declare(strict_types=1);

namespace VFC\Portal\Services;

use VFC\Core\Domain\Matricula\MatriculaRepository;
use VFC\Core\Services\AuditService;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Reenvio del QR al alumno: rota el token y dispara el hook que monta el email
 * (gestionado por QrEmailService de vfc-core).
 */
final class ResendQrService
{
    private MatriculaRepository $matriculas;

    public function __construct(?MatriculaRepository $matriculas = null)
    {
        $this->matriculas = $matriculas ?? new MatriculaRepository();
    }

    public function resend(int $matriculaId): bool
    {
        $matricula = $this->matriculas->find($matriculaId);
        if ($matricula === null) {
            return false;
        }
        $alumnoId = (int) $matricula->alumnoUserId;
        do_action('vfc_send_qr_to_alumno', $alumnoId);
        AuditService::log('qr_resend_request', 'matricula', (int) $matricula->id, [
            'alumno_user_id' => $alumnoId,
        ]);
        return true;
    }
}

<?php
declare(strict_types=1);

namespace VFC\Portal\Services;

use VFC\Core\Domain\Matricula\MatriculaRepository;
use VFC\Core\Services\AuditService;
use VFC\Core\Services\QrEmailService;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Reenvío del correo con el enlace QR (mismo token inmutable).
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
        $ok = (new QrEmailService())->sendForMatricula($matriculaId);
        AuditService::log('qr_resend_request', 'matricula', (int) $matricula->id, [
            'alumno_user_id' => $alumnoId,
            'sent' => $ok,
        ]);
        return $ok;
    }
}

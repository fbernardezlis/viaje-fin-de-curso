<?php
declare(strict_types=1);

namespace VFC\Core\Services;

use VFC\Core\Domain\Centro\CentroRepository;
use VFC\Core\Domain\Edicion\EdicionRepository;
use VFC\Core\Domain\Matricula\MatriculaRepository;
use VFC\Core\Roles\RolesInstaller;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Envía el email con el enlace QR del alumno para una edición.
 * El enlace es el mismo token inmutable de la matrícula; el reenvío solo repite el correo.
 */
final class QrEmailService
{
    private MatriculaRepository $matriculas;
    private EdicionRepository $ediciones;
    private CentroRepository $centros;
    private QrTokenService $qr;

    public function __construct(
        ?MatriculaRepository $matriculas = null,
        ?EdicionRepository $ediciones = null,
        ?CentroRepository $centros = null,
        ?QrTokenService $qr = null
    ) {
        $this->matriculas = $matriculas ?? new MatriculaRepository();
        $this->ediciones = $ediciones ?? new EdicionRepository();
        $this->centros = $centros ?? new CentroRepository();
        $this->qr = $qr ?? new QrTokenService();
    }

    /**
     * Registra el listener del hook que envía el QR cuando se establece la contraseña.
     */
    public static function registerHooks(): void
    {
        add_action('vfc_send_qr_to_alumno', [self::class, 'sendAllForAlumno']);
    }

    public static function sendAllForAlumno(int $alumnoUserId): void
    {
        $svc = new self();
        $matriculas = $svc->matriculas->listByAlumno($alumnoUserId);
        foreach ($matriculas as $m) {
            $svc->sendForMatricula((int) $m->id);
        }
        update_user_meta($alumnoUserId, UsersService::META_QR_DELIVERED, current_time('mysql', true));
    }

    /**
     * Envía (o reenvía) el email con el enlace QR de una matrícula (mismo token inmutable).
     */
    public function sendForMatricula(int $matriculaId): bool
    {
        $matricula = $this->matriculas->find($matriculaId);
        if ($matricula === null) {
            return false;
        }
        $alumno = get_userdata($matricula->alumnoUserId);
        if (!$alumno instanceof \WP_User) {
            return false;
        }
        if (!in_array(RolesInstaller::ROLE_ALUMNO, (array) $alumno->roles, true)) {
            return false;
        }
        $edicion = $this->ediciones->find($matricula->edicionId);
        if ($edicion === null) {
            return false;
        }
        $centro = $this->centros->find($edicion->centroId);

        $token = $this->matriculas->getPlainQrToken($matriculaId);
        $url = $this->qr->urlForToken($token);

        $blogName = wp_specialchars_decode((string) get_option('blogname'), ENT_QUOTES);
        $subject = sprintf(
            /* translators: 1: edition name, 2: blog name */
            __('[%2$s] Tu código QR para la edición “%1$s”', 'vfc-core'),
            $edicion->nombre,
            $blogName
        );

        $body = sprintf(
            /* translators: 1: alumno name, 2: edicion name, 3: centro name, 4: QR URL, 5: blog name */
            __("Hola %1\$s,\n\nTe han matriculado en la edición “%2\$s”%3\$s. Este enlace es permanente para esta matrícula: compártelo o el código QR generado a partir de él con quien quieras que pueda comprar a tu favor:\n\n%4\$s\n\nCualquier compra realizada después de abrir ese enlace quedará vinculada a tu cuenta hasta que la persona pulse “Salir / dejar de comprar”.\n\nPuedes pedir un reenvío del correo desde el portal; el enlace seguirá siendo el mismo.\n\n— %5\$s", 'vfc-core'),
            $alumno->display_name ?: $alumno->user_login,
            $edicion->nombre,
            $centro !== null ? sprintf(' (%s)', $centro->nombre) : '',
            $url,
            $blogName
        );

        return wp_mail($alumno->user_email, $subject, $body);
    }
}

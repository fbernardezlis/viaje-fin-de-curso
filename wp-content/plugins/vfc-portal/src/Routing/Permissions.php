<?php
declare(strict_types=1);

namespace VFC\Portal\Routing;

use VFC\Core\Domain\Centro\CentroAdminRepository;
use VFC\Core\Domain\Tutor\TutorAlumnoRepository;
use VFC\Core\Roles\RolesInstaller;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Helpers de permisos del portal.
 */
final class Permissions
{
    public static function isAlumno(\WP_User $user): bool
    {
        return in_array(RolesInstaller::ROLE_ALUMNO, (array) $user->roles, true);
    }

    public static function isTutor(\WP_User $user): bool
    {
        return in_array(RolesInstaller::ROLE_TUTOR, (array) $user->roles, true);
    }

    public static function isAdminColegio(\WP_User $user): bool
    {
        return in_array(RolesInstaller::ROLE_ADMIN_COLEGIO, (array) $user->roles, true);
    }

    public static function isSuperAdmin(\WP_User $user): bool
    {
        return user_can($user, 'manage_options')
            || in_array(RolesInstaller::ROLE_SUPER_ADMIN, (array) $user->roles, true);
    }

    /**
     * Devuelve true si el usuario puede ver datos del alumno indicado.
     */
    public static function canSeeAlumno(\WP_User $user, int $alumnoUserId, ?TutorAlumnoRepository $tutorRepo = null): bool
    {
        if ($alumnoUserId <= 0) {
            return false;
        }
        if (self::isSuperAdmin($user)) {
            return true;
        }
        if ((int) $user->ID === $alumnoUserId && self::isAlumno($user)) {
            return true;
        }
        if (self::isTutor($user)) {
            $repo = $tutorRepo ?? new TutorAlumnoRepository();
            return $repo->isLinked((int) $user->ID, $alumnoUserId);
        }
        return false;
    }

    /**
     * Devuelve true si el usuario puede ver el centro indicado.
     */
    public static function canSeeCentro(\WP_User $user, int $centroId, ?CentroAdminRepository $repo = null): bool
    {
        if ($centroId <= 0) {
            return false;
        }
        if (self::isSuperAdmin($user)) {
            return true;
        }
        if (self::isAdminColegio($user)) {
            $r = $repo ?? new CentroAdminRepository();
            return $r->isAdminOfCentro((int) $user->ID, $centroId);
        }
        return false;
    }

    /**
     * Devuelve la lista de centros que el usuario administra.
     *
     * @return array<int, int>
     */
    public static function centrosForUser(\WP_User $user, ?CentroAdminRepository $repo = null): array
    {
        if (self::isSuperAdmin($user)) {
            // Super admin ve todos los centros (no listamos aqui; el caller usa CentroRepository::list).
            return [];
        }
        if (!self::isAdminColegio($user)) {
            return [];
        }
        $r = $repo ?? new CentroAdminRepository();
        return $r->listCentrosForUser((int) $user->ID);
    }

    /**
     * Redirige a /portal/login si no hay sesion.
     */
    public static function requireLogin(): \WP_User
    {
        if (!is_user_logged_in()) {
            wp_safe_redirect(home_url('/portal/login'));
            exit;
        }
        return wp_get_current_user();
    }
}

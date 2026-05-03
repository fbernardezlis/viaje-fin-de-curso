<?php
declare(strict_types=1);

namespace VFC\Portal\Auth;

use VFC\Portal\Routing\Permissions;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Decide a que panel del portal redirigir tras un login exitoso.
 */
final class RoleRedirector
{
    public static function targetUrl(\WP_User $user): string
    {
        if (Permissions::isSuperAdmin($user) || Permissions::isAdminColegio($user)) {
            return home_url('/portal/colegio');
        }
        if (Permissions::isTutor($user)) {
            return home_url('/portal/tutor');
        }
        if (Permissions::isAlumno($user)) {
            return home_url('/portal/alumno');
        }
        return home_url('/portal/login');
    }
}

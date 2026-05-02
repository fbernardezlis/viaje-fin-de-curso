<?php
declare(strict_types=1);

namespace VFC\Core\Roles;

if (!defined('ABSPATH')) {
    exit;
}

final class RolesInstaller
{
    public const ROLE_SUPER_ADMIN = 'vfc_super_admin';
    public const ROLE_ADMIN_COLEGIO = 'vfc_admin_colegio';
    public const ROLE_ALUMNO = 'vfc_alumno';
    public const ROLE_TUTOR = 'vfc_tutor';

    /**
     * Crea los roles VFC y añade las caps al rol `administrator` para que el
     * super-admin de WordPress también pueda gestionar la plataforma.
     */
    public static function install(): void
    {
        self::ensureRole(
            self::ROLE_SUPER_ADMIN,
            __('VFC Super Admin', 'vfc-core'),
            ['read' => true] + self::capsAsTrueMap(Capabilities::superAdmin())
        );

        self::ensureRole(
            self::ROLE_ADMIN_COLEGIO,
            __('VFC Admin colegio', 'vfc-core'),
            ['read' => true] + self::capsAsTrueMap(Capabilities::adminColegio())
        );

        self::ensureRole(
            self::ROLE_ALUMNO,
            __('VFC Alumno', 'vfc-core'),
            ['read' => true] + self::capsAsTrueMap(Capabilities::alumno())
        );

        self::ensureRole(
            self::ROLE_TUTOR,
            __('VFC Tutor', 'vfc-core'),
            ['read' => true] + self::capsAsTrueMap(Capabilities::tutor())
        );

        $admin = get_role('administrator');
        if ($admin instanceof \WP_Role) {
            foreach (Capabilities::all() as $cap) {
                $admin->add_cap($cap);
            }
        }
    }

    /**
     * Quita roles y caps. No se llama en MVP (la desactivación es no destructiva).
     */
    public static function uninstall(): void
    {
        foreach ([self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN_COLEGIO, self::ROLE_ALUMNO, self::ROLE_TUTOR] as $role) {
            remove_role($role);
        }

        $admin = get_role('administrator');
        if ($admin instanceof \WP_Role) {
            foreach (Capabilities::all() as $cap) {
                $admin->remove_cap($cap);
            }
        }
    }

    /**
     * @param array<string, bool> $caps
     */
    private static function ensureRole(string $name, string $label, array $caps): void
    {
        $role = get_role($name);
        if (!$role instanceof \WP_Role) {
            add_role($name, $label, $caps);
            return;
        }
        foreach ($caps as $cap => $granted) {
            if ($granted) {
                $role->add_cap($cap);
            }
        }
    }

    /**
     * @param array<int, string> $caps
     * @return array<string, bool>
     */
    private static function capsAsTrueMap(array $caps): array
    {
        $map = [];
        foreach ($caps as $cap) {
            $map[$cap] = true;
        }
        return $map;
    }
}

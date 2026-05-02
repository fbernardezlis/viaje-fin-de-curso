<?php
declare(strict_types=1);

namespace VFC\Core\Roles;

if (!defined('ABSPATH')) {
    exit;
}

final class Capabilities
{
    public const MANAGE_CENTROS = 'vfc_manage_centros';
    public const APPROVE_EDICIONES = 'vfc_approve_ediciones';
    public const MANAGE_EDICIONES = 'vfc_manage_ediciones';
    public const MANAGE_MATRICULAS = 'vfc_manage_matriculas';
    public const MANAGE_CENTRO_PRODUCTOS = 'vfc_manage_centro_productos';
    public const MANAGE_LIQUIDACIONES = 'vfc_manage_liquidaciones';
    public const VIEW_AUDIT = 'vfc_view_audit';
    public const EXPORT_AUDIT = 'vfc_export_audit';
    public const VIEW_OWN_BALANCE = 'vfc_view_own_balance';
    public const MANAGE_ALIAS = 'vfc_manage_alias';

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return [
            self::MANAGE_CENTROS,
            self::APPROVE_EDICIONES,
            self::MANAGE_EDICIONES,
            self::MANAGE_MATRICULAS,
            self::MANAGE_CENTRO_PRODUCTOS,
            self::MANAGE_LIQUIDACIONES,
            self::VIEW_AUDIT,
            self::EXPORT_AUDIT,
            self::VIEW_OWN_BALANCE,
            self::MANAGE_ALIAS,
        ];
    }

    /**
     * Capacidades del rol superadmin de la plataforma (todas).
     *
     * @return array<int, string>
     */
    public static function superAdmin(): array
    {
        return self::all();
    }

    /**
     * Capacidades del admin de un centro (acotadas a su centro vía repositorios).
     *
     * @return array<int, string>
     */
    public static function adminColegio(): array
    {
        return [
            self::MANAGE_EDICIONES,
            self::MANAGE_MATRICULAS,
            self::MANAGE_CENTRO_PRODUCTOS,
            self::VIEW_AUDIT,
            self::EXPORT_AUDIT,
            self::MANAGE_ALIAS,
        ];
    }

    /**
     * Capacidades del alumno.
     *
     * @return array<int, string>
     */
    public static function alumno(): array
    {
        return [self::VIEW_OWN_BALANCE];
    }

    /**
     * Capacidades del tutor.
     *
     * @return array<int, string>
     */
    public static function tutor(): array
    {
        return [self::VIEW_OWN_BALANCE];
    }
}

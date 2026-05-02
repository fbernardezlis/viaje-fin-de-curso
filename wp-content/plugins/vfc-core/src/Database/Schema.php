<?php
declare(strict_types=1);

namespace VFC\Core\Database;

if (!defined('ABSPATH')) {
    exit;
}

final class Schema
{
    public const TABLE_CENTROS = 'vfc_centros';
    public const TABLE_CENTRO_ADMINS = 'vfc_centro_admins';
    public const TABLE_CENTRO_PRODUCTOS = 'vfc_centro_productos';
    public const TABLE_EDICIONES = 'vfc_ediciones';
    public const TABLE_MATRICULAS = 'vfc_matriculas';
    public const TABLE_TUTOR_ALUMNO = 'vfc_tutor_alumno';
    public const TABLE_MOVIMIENTOS_SALDO = 'vfc_movimientos_saldo';
    public const TABLE_LIQUIDACIONES = 'vfc_liquidaciones';
    public const TABLE_LIQUIDACION_ITEMS = 'vfc_liquidacion_items';
    public const TABLE_AUDIT_LOG = 'vfc_audit_log';

    /**
     * Devuelve nombre de tabla con prefijo de WP.
     */
    public static function table(string $name): string
    {
        global $wpdb;
        return $wpdb->prefix . $name;
    }

    /**
     * Lista de definiciones SQL (CREATE TABLE) listas para dbDelta.
     *
     * @return array<int, string>
     */
    public static function definitions(): array
    {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        $p = $wpdb->prefix;

        $sql = [];

        $sql[] = "CREATE TABLE {$p}vfc_centros (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            nombre VARCHAR(191) NOT NULL,
            slug VARCHAR(191) NOT NULL,
            cif VARCHAR(32) NULL,
            email VARCHAR(191) NULL,
            telefono VARCHAR(64) NULL,
            direccion TEXT NULL,
            estado VARCHAR(32) NOT NULL DEFAULT 'activo',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY uniq_slug (slug),
            KEY idx_estado (estado)
        ) {$charset};";

        $sql[] = "CREATE TABLE {$p}vfc_centro_admins (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            centro_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY uniq_centro_user (centro_id, user_id),
            KEY idx_centro (centro_id),
            KEY idx_user (user_id)
        ) {$charset};";

        $sql[] = "CREATE TABLE {$p}vfc_centro_productos (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            centro_id BIGINT UNSIGNED NOT NULL,
            product_id BIGINT UNSIGNED NOT NULL,
            activo TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY uniq_centro_product (centro_id, product_id),
            KEY idx_centro (centro_id),
            KEY idx_product (product_id)
        ) {$charset};";

        $sql[] = "CREATE TABLE {$p}vfc_ediciones (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            centro_id BIGINT UNSIGNED NOT NULL,
            nombre VARCHAR(191) NOT NULL,
            fecha_inicio DATE NULL,
            fecha_fin DATE NULL,
            estado VARCHAR(32) NOT NULL DEFAULT 'borrador',
            aprobada_por BIGINT UNSIGNED NULL,
            fecha_aprobacion DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_centro (centro_id),
            KEY idx_estado (estado)
        ) {$charset};";

        $sql[] = "CREATE TABLE {$p}vfc_matriculas (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            edicion_id BIGINT UNSIGNED NOT NULL,
            alumno_user_id BIGINT UNSIGNED NOT NULL,
            alias VARCHAR(120) NOT NULL,
            qr_token_hash CHAR(64) NOT NULL,
            creado_por BIGINT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY uniq_edicion_alumno (edicion_id, alumno_user_id),
            UNIQUE KEY uniq_qr_token_hash (qr_token_hash),
            KEY idx_alumno (alumno_user_id)
        ) {$charset};";

        $sql[] = "CREATE TABLE {$p}vfc_tutor_alumno (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            tutor_user_id BIGINT UNSIGNED NOT NULL,
            alumno_user_id BIGINT UNSIGNED NOT NULL,
            parentesco VARCHAR(64) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY uniq_tutor_alumno (tutor_user_id, alumno_user_id),
            KEY idx_tutor (tutor_user_id),
            KEY idx_alumno (alumno_user_id)
        ) {$charset};";

        $sql[] = "CREATE TABLE {$p}vfc_movimientos_saldo (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            alumno_user_id BIGINT UNSIGNED NOT NULL,
            edicion_id BIGINT UNSIGNED NOT NULL,
            order_id BIGINT UNSIGNED NOT NULL,
            line_item_id BIGINT UNSIGNED NULL,
            tipo VARCHAR(16) NOT NULL,
            importe_sin_iva DECIMAL(12,4) NOT NULL DEFAULT 0,
            estado VARCHAR(16) NOT NULL DEFAULT 'BLOQUEADO',
            fecha_pedido DATETIME NOT NULL,
            fecha_liberacion DATETIME NULL,
            fecha_confirmacion DATETIME NULL,
            motivo VARCHAR(255) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_alumno (alumno_user_id),
            KEY idx_edicion (edicion_id),
            KEY idx_order (order_id),
            KEY idx_estado (estado),
            KEY idx_liberacion (fecha_liberacion)
        ) {$charset};";

        $sql[] = "CREATE TABLE {$p}vfc_liquidaciones (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            centro_id BIGINT UNSIGNED NOT NULL,
            importe DECIMAL(12,4) NOT NULL DEFAULT 0,
            fecha DATE NOT NULL,
            referencia VARCHAR(191) NULL,
            notas TEXT NULL,
            registrada_por BIGINT UNSIGNED NULL,
            estado VARCHAR(32) NOT NULL DEFAULT 'registrada',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_centro (centro_id),
            KEY idx_fecha (fecha)
        ) {$charset};";

        $sql[] = "CREATE TABLE {$p}vfc_liquidacion_items (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            liquidacion_id BIGINT UNSIGNED NOT NULL,
            alumno_user_id BIGINT UNSIGNED NOT NULL,
            edicion_id BIGINT UNSIGNED NOT NULL,
            importe DECIMAL(12,4) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_liquidacion (liquidacion_id),
            KEY idx_alumno (alumno_user_id),
            KEY idx_edicion (edicion_id)
        ) {$charset};";

        $sql[] = "CREATE TABLE {$p}vfc_audit_log (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            actor_user_id BIGINT UNSIGNED NULL,
            accion VARCHAR(64) NOT NULL,
            entidad_tipo VARCHAR(64) NOT NULL,
            entidad_id BIGINT UNSIGNED NULL,
            centro_id BIGINT UNSIGNED NULL,
            datos LONGTEXT NULL,
            ip VARCHAR(45) NULL,
            user_agent VARCHAR(255) NULL,
            PRIMARY KEY  (id),
            KEY idx_fecha (fecha),
            KEY idx_actor (actor_user_id),
            KEY idx_centro (centro_id),
            KEY idx_entidad (entidad_tipo, entidad_id)
        ) {$charset};";

        return $sql;
    }
}

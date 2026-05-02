<?php
declare(strict_types=1);

namespace VFC\Core\Services;

use VFC\Core\Roles\RolesInstaller;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Alta y gestión de usuarios alumno/tutor sobre el sistema de usuarios de WordPress.
 * Usa user_meta para campos extra (centro, ya recibió QR, etc.).
 */
final class UsersService
{
    public const META_CENTRO_ID = 'vfc_centro_id';
    public const META_QR_DELIVERED = 'vfc_qr_delivered';

    /**
     * Crea (o reusa) un usuario con rol alumno y devuelve su ID.
     *
     * @throws \RuntimeException si email inválido o no se puede crear.
     */
    public function createAlumno(string $email, string $firstName, string $lastName, ?int $centroId = null): int
    {
        return $this->createOrEnsureRole($email, $firstName, $lastName, RolesInstaller::ROLE_ALUMNO, $centroId);
    }

    /**
     * Crea (o reusa) un usuario con rol tutor y devuelve su ID.
     *
     * @throws \RuntimeException
     */
    public function createTutor(string $email, string $firstName, string $lastName): int
    {
        return $this->createOrEnsureRole($email, $firstName, $lastName, RolesInstaller::ROLE_TUTOR, null);
    }

    private function createOrEnsureRole(string $email, string $firstName, string $lastName, string $role, ?int $centroId): int
    {
        $email = sanitize_email($email);
        if ($email === '' || !is_email($email)) {
            throw new \RuntimeException(__('Email inválido.', 'vfc-core'));
        }
        $existing = get_user_by('email', $email);
        if ($existing instanceof \WP_User) {
            $userId = (int) $existing->ID;
            $existing->add_role($role);
        } else {
            $login = $this->generateLogin($email);
            $password = wp_generate_password(20, true, true);
            $userId = wp_insert_user([
                'user_login' => $login,
                'user_email' => $email,
                'user_pass' => $password,
                'first_name' => sanitize_text_field($firstName),
                'last_name' => sanitize_text_field($lastName),
                'display_name' => trim(sanitize_text_field($firstName) . ' ' . sanitize_text_field($lastName)),
                'role' => $role,
            ]);
            if (is_wp_error($userId)) {
                throw new \RuntimeException($userId->get_error_message());
            }
            $userId = (int) $userId;
        }

        if ($centroId !== null && $centroId > 0) {
            update_user_meta($userId, self::META_CENTRO_ID, $centroId);
        }

        return $userId;
    }

    /**
     * Envía el email de “establecer contraseña” usando el flujo nativo de WordPress.
     */
    public function sendSetPasswordEmail(int $userId): bool
    {
        $user = get_userdata($userId);
        if (!$user instanceof \WP_User) {
            return false;
        }

        $key = get_password_reset_key($user);
        if (is_wp_error($key)) {
            return false;
        }

        $resetUrl = network_site_url(
            'wp-login.php?action=rp&key=' . rawurlencode($key) . '&login=' . rawurlencode($user->user_login),
            'login'
        );

        $blogName = wp_specialchars_decode((string) get_option('blogname'), ENT_QUOTES);
        $subject = sprintf(
            /* translators: %s: blog name */
            __('[%s] Establece tu contraseña', 'vfc-core'),
            $blogName
        );

        $message = sprintf(
            /* translators: 1: user display name, 2: link to set password */
            __("Hola %1\$s,\n\nSe ha creado una cuenta para ti en %2\$s. Para acceder, primero establece tu contraseña aquí:\n%3\$s\n\nUna vez la hayas configurado, recibirás un correo aparte con tu código QR para que puedan comprar a tu favor.\n\n— %2\$s", 'vfc-core'),
            $user->display_name ?: $user->user_login,
            $blogName,
            $resetUrl
        );

        return wp_mail($user->user_email, $subject, $message);
    }

    /**
     * Registra los hooks que envían el QR cuando el alumno establece su contraseña.
     */
    public static function registerHooks(): void
    {
        add_action('after_password_reset', [self::class, 'onAfterPasswordReset'], 10, 2);
    }

    /**
     * @param \WP_User $user
     */
    public static function onAfterPasswordReset($user, string $newPass): void
    {
        if (!$user instanceof \WP_User) {
            return;
        }
        if (!in_array(RolesInstaller::ROLE_ALUMNO, (array) $user->roles, true)) {
            return;
        }
        if (get_user_meta((int) $user->ID, self::META_QR_DELIVERED, true)) {
            return;
        }

        do_action('vfc_send_qr_to_alumno', (int) $user->ID);
    }

    private function generateLogin(string $email): string
    {
        $base = sanitize_user(strtolower(strtok($email, '@') ?: 'user'), true);
        if ($base === '') {
            $base = 'vfc-user';
        }
        $candidate = $base;
        $i = 1;
        while (username_exists($candidate)) {
            $candidate = $base . '-' . $i;
            $i++;
            if ($i > 999) {
                $candidate = $base . '-' . substr(md5(uniqid('', true)), 0, 6);
                break;
            }
        }
        return $candidate;
    }
}

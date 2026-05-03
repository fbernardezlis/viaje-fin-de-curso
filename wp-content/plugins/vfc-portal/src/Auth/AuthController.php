<?php
declare(strict_types=1);

namespace VFC\Portal\Auth;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Procesa los POST de login y reset password.
 */
final class AuthController
{
    public const NONCE_LOGIN = 'vfc_portal_login';
    public const NONCE_RESET = 'vfc_portal_reset';

    public function register(): void
    {
        add_action('admin_post_nopriv_vfc_portal_login', [$this, 'handleLogin']);
        add_action('admin_post_vfc_portal_login', [$this, 'handleLogin']);

        add_action('admin_post_nopriv_vfc_portal_reset', [$this, 'handleResetRequest']);
        add_action('admin_post_vfc_portal_reset', [$this, 'handleResetRequest']);
    }

    public function handleLogin(): void
    {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce((string) $_POST['_wpnonce'], self::NONCE_LOGIN)) {
            $this->redirectLogin('nonce');
        }

        $user = (string) ($_POST['log'] ?? '');
        $pass = (string) ($_POST['pwd'] ?? '');
        $remember = !empty($_POST['rememberme']);

        if ($user === '' || $pass === '') {
            $this->redirectLogin('empty');
        }

        $signon = wp_signon([
            'user_login' => sanitize_user($user, false),
            'user_password' => $pass,
            'remember' => $remember,
        ], is_ssl());

        if (is_wp_error($signon)) {
            $this->redirectLogin('invalid');
        }

        wp_safe_redirect(RoleRedirector::targetUrl($signon));
        exit;
    }

    public function handleResetRequest(): void
    {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce((string) $_POST['_wpnonce'], self::NONCE_RESET)) {
            $this->redirectReset('nonce');
        }

        $login = trim((string) ($_POST['user_login'] ?? ''));
        if ($login === '') {
            $this->redirectReset('empty');
        }

        require_once ABSPATH . 'wp-includes/user.php';
        $errors = retrieve_password($login);
        if (is_wp_error($errors)) {
            $this->redirectReset('invalid');
        }

        $this->redirectReset('sent');
    }

    private function redirectLogin(string $code): void
    {
        wp_safe_redirect(add_query_arg('vfc_err', $code, home_url('/portal/login')));
        exit;
    }

    private function redirectReset(string $code): void
    {
        wp_safe_redirect(add_query_arg('vfc_msg', $code, home_url('/portal/reset')));
        exit;
    }
}

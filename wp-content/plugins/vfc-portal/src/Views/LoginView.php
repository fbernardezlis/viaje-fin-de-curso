<?php
declare(strict_types=1);

namespace VFC\Portal\Views;

use VFC\Portal\Auth\RoleRedirector;

if (!defined('ABSPATH')) {
    exit;
}

final class LoginView
{
    public function render(string $param = ''): void
    {
        if (is_user_logged_in()) {
            wp_safe_redirect(RoleRedirector::targetUrl(wp_get_current_user()));
            exit;
        }
        Layout::render(__('Acceso al portal', 'vfc-portal'), 'login', [
            'error' => isset($_GET['vfc_err']) ? (string) $_GET['vfc_err'] : '',
        ]);
    }
}

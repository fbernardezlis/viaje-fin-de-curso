<?php
declare(strict_types=1);

namespace VFC\Portal\Views;

if (!defined('ABSPATH')) {
    exit;
}

final class ResetView
{
    public function render(string $param = ''): void
    {
        Layout::render(__('Recuperar contraseña', 'vfc-portal'), 'reset', [
            'msg' => isset($_GET['vfc_msg']) ? (string) $_GET['vfc_msg'] : '',
        ]);
    }
}

<?php
declare(strict_types=1);

namespace VFC\Core\Admin\Pages;

use VFC\Core\Domain\Tutor\TutorAlumnoRepository;
use VFC\Core\Roles\Capabilities;
use VFC\Core\Roles\RolesInstaller;
use VFC\Core\Services\UsersService;

if (!defined('ABSPATH')) {
    exit;
}

final class TutoresListPage
{
    public const SLUG = 'vfc-tutores';
    private const NONCE_FIELD = 'vfc_tutor_nonce';
    private const NONCE_ACTION_SAVE = 'vfc_tutor_save';
    private const NONCE_ACTION_LINK = 'vfc_tutor_link';

    private UsersService $users;
    private TutorAlumnoRepository $tutorAlumno;

    public function __construct(?UsersService $users = null, ?TutorAlumnoRepository $tutorAlumno = null)
    {
        $this->users = $users ?? new UsersService();
        $this->tutorAlumno = $tutorAlumno ?? new TutorAlumnoRepository();
    }

    public function register(): void
    {
        add_action('admin_menu', [$this, 'registerMenu']);
        add_action('admin_init', [$this, 'handleActions']);
    }

    public function registerMenu(): void
    {
        add_submenu_page(
            CentrosListPage::PARENT_SLUG,
            __('Tutores', 'vfc-core'),
            __('Tutores', 'vfc-core'),
            Capabilities::MANAGE_MATRICULAS,
            self::SLUG,
            [$this, 'render']
        );
    }

    public function render(): void
    {
        if (!current_user_can(Capabilities::MANAGE_MATRICULAS)) {
            wp_die(esc_html__('No tienes permisos.', 'vfc-core'));
        }
        $action = isset($_GET['action']) ? sanitize_key((string) $_GET['action']) : '';
        if ($action === 'new') {
            $this->renderForm();
            return;
        }
        if ($action === 'manage') {
            $this->renderManage();
            return;
        }
        $this->renderList();
    }

    public function handleActions(): void
    {
        if (!is_admin()) {
            return;
        }
        $page = isset($_REQUEST['page']) ? sanitize_key((string) $_REQUEST['page']) : '';
        if ($page !== self::SLUG) {
            return;
        }
        $action = isset($_REQUEST['action']) ? sanitize_key((string) $_REQUEST['action']) : '';
        if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleSave();
        } elseif ($action === 'link' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleLink();
        } elseif ($action === 'unlink') {
            $this->handleUnlink();
        }
    }

    private function handleSave(): void
    {
        if (!current_user_can(Capabilities::MANAGE_MATRICULAS)) {
            wp_die(esc_html__('Sin permisos.', 'vfc-core'));
        }
        check_admin_referer(self::NONCE_ACTION_SAVE, self::NONCE_FIELD);

        try {
            $userId = $this->users->createTutor(
                (string) ($_POST['email'] ?? ''),
                (string) ($_POST['first_name'] ?? ''),
                (string) ($_POST['last_name'] ?? '')
            );
            $this->users->sendSetPasswordEmail($userId);
        } catch (\RuntimeException $e) {
            $this->redirect([
                'action' => 'new',
                'vfc_error' => 'save',
                'vfc_msg' => rawurlencode($e->getMessage()),
            ]);
            return;
        }

        $this->redirect(['action' => 'manage', 'tutor_id' => $userId, 'vfc_notice' => 'created']);
    }

    private function handleLink(): void
    {
        if (!current_user_can(Capabilities::MANAGE_MATRICULAS)) {
            wp_die(esc_html__('Sin permisos.', 'vfc-core'));
        }
        check_admin_referer(self::NONCE_ACTION_LINK, self::NONCE_FIELD);

        $tutorId = isset($_POST['tutor_id']) ? (int) $_POST['tutor_id'] : 0;
        $alumnoId = isset($_POST['alumno_id']) ? (int) $_POST['alumno_id'] : 0;

        try {
            $this->tutorAlumno->link($tutorId, $alumnoId);
        } catch (\RuntimeException $e) {
            $this->redirect([
                'action' => 'manage',
                'tutor_id' => $tutorId,
                'vfc_error' => 'link',
                'vfc_msg' => rawurlencode($e->getMessage()),
            ]);
            return;
        }

        $this->redirect(['action' => 'manage', 'tutor_id' => $tutorId, 'vfc_notice' => 'linked']);
    }

    private function handleUnlink(): void
    {
        if (!current_user_can(Capabilities::MANAGE_MATRICULAS)) {
            wp_die(esc_html__('Sin permisos.', 'vfc-core'));
        }
        $tutorId = isset($_GET['tutor_id']) ? (int) $_GET['tutor_id'] : 0;
        $alumnoId = isset($_GET['alumno_id']) ? (int) $_GET['alumno_id'] : 0;
        check_admin_referer('vfc_tutor_unlink_' . $tutorId . '_' . $alumnoId);
        $this->tutorAlumno->unlink($tutorId, $alumnoId);
        $this->redirect(['action' => 'manage', 'tutor_id' => $tutorId, 'vfc_notice' => 'unlinked']);
    }

    private function renderList(): void
    {
        $tutores = get_users([
            'role' => RolesInstaller::ROLE_TUTOR,
            'orderby' => 'display_name',
            'order' => 'ASC',
            'number' => 200,
        ]);
        $newUrl = add_query_arg(['page' => self::SLUG, 'action' => 'new'], admin_url('admin.php'));

        echo '<div class="wrap"><h1 class="wp-heading-inline">' . esc_html__('Tutores', 'vfc-core') . '</h1>';
        echo ' <a href="' . esc_url($newUrl) . '" class="page-title-action">' . esc_html__('Añadir tutor', 'vfc-core') . '</a>';
        echo '<hr class="wp-header-end">';
        $this->renderNotices();

        echo '<table class="wp-list-table widefat fixed striped"><thead><tr>';
        echo '<th>' . esc_html__('Nombre', 'vfc-core') . '</th>';
        echo '<th>' . esc_html__('Email', 'vfc-core') . '</th>';
        echo '<th>' . esc_html__('Alumnos vinculados', 'vfc-core') . '</th>';
        echo '<th>' . esc_html__('Acciones', 'vfc-core') . '</th>';
        echo '</tr></thead><tbody>';

        if ($tutores === []) {
            echo '<tr><td colspan="4">' . esc_html__('No hay tutores.', 'vfc-core') . '</td></tr>';
        } else {
            foreach ($tutores as $tutor) {
                $alumnos = $this->tutorAlumno->alumnosForTutor((int) $tutor->ID);
                $manageUrl = add_query_arg(
                    ['page' => self::SLUG, 'action' => 'manage', 'tutor_id' => (int) $tutor->ID],
                    admin_url('admin.php')
                );
                printf(
                    '<tr><td><strong>%s</strong></td><td>%s</td><td>%d</td><td><a class="button" href="%s">%s</a></td></tr>',
                    esc_html($tutor->display_name ?: $tutor->user_login),
                    esc_html($tutor->user_email),
                    count($alumnos),
                    esc_url($manageUrl),
                    esc_html__('Gestionar vínculos', 'vfc-core')
                );
            }
        }

        echo '</tbody></table></div>';
    }

    private function renderManage(): void
    {
        $tutorId = isset($_GET['tutor_id']) ? (int) $_GET['tutor_id'] : 0;
        $tutor = get_userdata($tutorId);
        if (!$tutor instanceof \WP_User || !in_array(RolesInstaller::ROLE_TUTOR, (array) $tutor->roles, true)) {
            echo '<div class="wrap"><div class="notice notice-error"><p>' . esc_html__('Tutor no encontrado.', 'vfc-core') . '</p></div></div>';
            return;
        }

        $alumnosVinculados = $this->tutorAlumno->alumnosForTutor($tutorId);
        $alumnos = get_users([
            'role' => RolesInstaller::ROLE_ALUMNO,
            'orderby' => 'display_name',
            'order' => 'ASC',
            'number' => 200,
        ]);
        $linkUrl = add_query_arg(['page' => self::SLUG, 'action' => 'link'], admin_url('admin.php'));
        $listUrl = add_query_arg(['page' => self::SLUG], admin_url('admin.php'));

        echo '<div class="wrap"><h1>' . esc_html(sprintf(
            /* translators: %s: tutor name */
            __('Tutor: %s', 'vfc-core'),
            $tutor->display_name ?: $tutor->user_login
        )) . '</h1>';
        echo '<p><a href="' . esc_url($listUrl) . '">&laquo; ' . esc_html__('Volver a la lista', 'vfc-core') . '</a></p>';
        $this->renderNotices();

        echo '<h2>' . esc_html__('Alumnos vinculados', 'vfc-core') . '</h2>';
        if ($alumnosVinculados === []) {
            echo '<p>' . esc_html__('Aún no hay alumnos vinculados.', 'vfc-core') . '</p>';
        } else {
            echo '<ul>';
            foreach ($alumnosVinculados as $alumnoId) {
                $alumno = get_userdata($alumnoId);
                if (!$alumno instanceof \WP_User) {
                    continue;
                }
                $unlinkUrl = wp_nonce_url(
                    add_query_arg(
                        ['page' => self::SLUG, 'action' => 'unlink', 'tutor_id' => $tutorId, 'alumno_id' => $alumnoId],
                        admin_url('admin.php')
                    ),
                    'vfc_tutor_unlink_' . $tutorId . '_' . $alumnoId
                );
                printf(
                    '<li>%s &lt;%s&gt; — <a href="%s" onclick="return confirm(\'%s\');">%s</a></li>',
                    esc_html($alumno->display_name ?: $alumno->user_login),
                    esc_html($alumno->user_email),
                    esc_url($unlinkUrl),
                    esc_js(__('¿Desvincular este alumno?', 'vfc-core')),
                    esc_html__('desvincular', 'vfc-core')
                );
            }
            echo '</ul>';
        }

        echo '<h2>' . esc_html__('Vincular nuevo alumno', 'vfc-core') . '</h2>';
        echo '<form method="post" action="' . esc_url($linkUrl) . '">';
        wp_nonce_field(self::NONCE_ACTION_LINK, self::NONCE_FIELD);
        echo '<input type="hidden" name="tutor_id" value="' . (int) $tutorId . '" />';
        echo '<select name="alumno_id" required>';
        echo '<option value="">' . esc_html__('— Selecciona alumno —', 'vfc-core') . '</option>';
        foreach ($alumnos as $alumno) {
            if (in_array((int) $alumno->ID, $alumnosVinculados, true)) {
                continue;
            }
            printf(
                '<option value="%d">%s &lt;%s&gt;</option>',
                (int) $alumno->ID,
                esc_html($alumno->display_name ?: $alumno->user_login),
                esc_html($alumno->user_email)
            );
        }
        echo '</select> ';
        submit_button(__('Vincular', 'vfc-core'), 'primary', 'submit', false);
        echo '</form></div>';
    }

    private function renderForm(): void
    {
        $formUrl = add_query_arg(['page' => self::SLUG, 'action' => 'save'], admin_url('admin.php'));
        $listUrl = add_query_arg(['page' => self::SLUG], admin_url('admin.php'));

        echo '<div class="wrap"><h1>' . esc_html__('Nuevo tutor', 'vfc-core') . '</h1>';
        $this->renderNotices();

        echo '<form method="post" action="' . esc_url($formUrl) . '">';
        wp_nonce_field(self::NONCE_ACTION_SAVE, self::NONCE_FIELD);
        echo '<table class="form-table" role="presentation"><tbody>';

        echo '<tr><th><label for="vfc-email">' . esc_html__('Email', 'vfc-core') . '</label></th><td>';
        echo '<input type="email" id="vfc-email" name="email" class="regular-text" required />';
        echo '</td></tr>';

        echo '<tr><th><label for="vfc-first">' . esc_html__('Nombre', 'vfc-core') . '</label></th><td>';
        echo '<input type="text" id="vfc-first" name="first_name" class="regular-text" required />';
        echo '</td></tr>';

        echo '<tr><th><label for="vfc-last">' . esc_html__('Apellidos', 'vfc-core') . '</label></th><td>';
        echo '<input type="text" id="vfc-last" name="last_name" class="regular-text" />';
        echo '</td></tr>';

        echo '</tbody></table>';
        submit_button(__('Crear tutor y enviar email', 'vfc-core'));
        echo ' <a href="' . esc_url($listUrl) . '" class="button">' . esc_html__('Cancelar', 'vfc-core') . '</a>';
        echo '</form></div>';
    }

    /**
     * @param array<string, string|int> $params
     */
    private function redirect(array $params): void
    {
        $url = add_query_arg(array_merge(['page' => self::SLUG], $params), admin_url('admin.php'));
        wp_safe_redirect($url);
        exit;
    }

    private function renderNotices(): void
    {
        $messages = [
            'created' => __('Tutor creado y email de contraseña enviado.', 'vfc-core'),
            'linked' => __('Alumno vinculado.', 'vfc-core'),
            'unlinked' => __('Alumno desvinculado.', 'vfc-core'),
        ];
        if (!empty($_GET['vfc_notice']) && isset($messages[(string) $_GET['vfc_notice']])) {
            printf(
                '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
                esc_html($messages[(string) $_GET['vfc_notice']])
            );
        }
        if (!empty($_GET['vfc_error'])) {
            $msg = isset($_GET['vfc_msg'])
                ? rawurldecode((string) $_GET['vfc_msg'])
                : __('Operación no realizada.', 'vfc-core');
            printf(
                '<div class="notice notice-error is-dismissible"><p>%s</p></div>',
                esc_html($msg)
            );
        }
    }
}

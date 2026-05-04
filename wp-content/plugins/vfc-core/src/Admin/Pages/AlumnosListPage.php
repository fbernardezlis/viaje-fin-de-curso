<?php
declare(strict_types=1);

namespace VFC\Core\Admin\Pages;

use VFC\Core\Domain\Centro\CentroRepository;
use VFC\Core\Domain\Matricula\MatriculaRepository;
use VFC\Core\Domain\Tutor\TutorAlumnoRepository;
use VFC\Core\Roles\Capabilities;
use VFC\Core\Roles\RolesInstaller;
use VFC\Core\Services\QrEmailService;
use VFC\Core\Services\UsersService;

if (!defined('ABSPATH')) {
    exit;
}

final class AlumnosListPage
{
    public const SLUG = 'vfc-alumnos';
    private const NONCE_FIELD = 'vfc_alumno_nonce';
    private const NONCE_ACTION_SAVE = 'vfc_alumno_save';

    private UsersService $users;
    private CentroRepository $centros;
    private MatriculaRepository $matriculas;
    private TutorAlumnoRepository $tutorAlumno;
    private QrEmailService $qrEmail;

    public function __construct(
        ?UsersService $users = null,
        ?CentroRepository $centros = null,
        ?MatriculaRepository $matriculas = null,
        ?TutorAlumnoRepository $tutorAlumno = null,
        ?QrEmailService $qrEmail = null
    ) {
        $this->users = $users ?? new UsersService();
        $this->centros = $centros ?? new CentroRepository();
        $this->matriculas = $matriculas ?? new MatriculaRepository();
        $this->tutorAlumno = $tutorAlumno ?? new TutorAlumnoRepository();
        $this->qrEmail = $qrEmail ?? new QrEmailService();
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
            __('Alumnos', 'vfc-core'),
            __('Alumnos', 'vfc-core'),
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
        } elseif ($action === 'resend_password') {
            $this->handleResendPassword();
        } elseif ($action === 'resend_qr') {
            $this->handleResendQr();
        }
    }

    private function handleSave(): void
    {
        if (!current_user_can(Capabilities::MANAGE_MATRICULAS)) {
            wp_die(esc_html__('Sin permisos.', 'vfc-core'));
        }
        check_admin_referer(self::NONCE_ACTION_SAVE, self::NONCE_FIELD);

        try {
            $userId = $this->users->createAlumno(
                (string) ($_POST['email'] ?? ''),
                (string) ($_POST['first_name'] ?? ''),
                (string) ($_POST['last_name'] ?? ''),
                isset($_POST['centro_id']) ? (int) $_POST['centro_id'] : null
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

        $this->redirect(['vfc_notice' => 'created', 'vfc_user' => $userId]);
    }

    private function handleResendPassword(): void
    {
        if (!current_user_can(Capabilities::MANAGE_MATRICULAS)) {
            wp_die(esc_html__('Sin permisos.', 'vfc-core'));
        }
        $userId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
        check_admin_referer('vfc_resend_password_' . $userId);
        $this->users->sendSetPasswordEmail($userId);
        $this->redirect(['vfc_notice' => 'password_resent']);
    }

    private function handleResendQr(): void
    {
        if (!current_user_can(Capabilities::MANAGE_MATRICULAS)) {
            wp_die(esc_html__('Sin permisos.', 'vfc-core'));
        }
        $matriculaId = isset($_GET['matricula_id']) ? (int) $_GET['matricula_id'] : 0;
        check_admin_referer('vfc_resend_qr_' . $matriculaId);
        $this->qrEmail->sendForMatricula($matriculaId);
        $this->redirect(['vfc_notice' => 'qr_resent']);
    }

    private function renderList(): void
    {
        $alumnos = get_users([
            'role' => RolesInstaller::ROLE_ALUMNO,
            'orderby' => 'display_name',
            'order' => 'ASC',
            'number' => 200,
        ]);
        $newUrl = add_query_arg(['page' => self::SLUG, 'action' => 'new'], admin_url('admin.php'));

        echo '<div class="wrap"><h1 class="wp-heading-inline">' . esc_html__('Alumnos', 'vfc-core') . '</h1>';
        echo ' <a href="' . esc_url($newUrl) . '" class="page-title-action">' . esc_html__('Añadir alumno', 'vfc-core') . '</a>';
        echo '<hr class="wp-header-end">';
        $this->renderNotices();

        echo '<table class="wp-list-table widefat fixed striped"><thead><tr>';
        echo '<th>' . esc_html__('Nombre', 'vfc-core') . '</th>';
        echo '<th>' . esc_html__('Email', 'vfc-core') . '</th>';
        echo '<th>' . esc_html__('Centro', 'vfc-core') . '</th>';
        echo '<th>' . esc_html__('Matrículas', 'vfc-core') . '</th>';
        echo '<th>' . esc_html__('Acciones', 'vfc-core') . '</th>';
        echo '</tr></thead><tbody>';

        if ($alumnos === []) {
            echo '<tr><td colspan="5">' . esc_html__('No hay alumnos.', 'vfc-core') . '</td></tr>';
        } else {
            foreach ($alumnos as $alumno) {
                $this->renderAlumnoRow($alumno);
            }
        }

        echo '</tbody></table></div>';
    }

    private function renderAlumnoRow(\WP_User $alumno): void
    {
        $centroId = (int) get_user_meta($alumno->ID, UsersService::META_CENTRO_ID, true);
        $centro = $centroId > 0 ? $this->centros->find($centroId) : null;
        $matriculas = $this->matriculas->listByAlumno((int) $alumno->ID);

        $resendPwdUrl = wp_nonce_url(
            add_query_arg(
                ['page' => self::SLUG, 'action' => 'resend_password', 'user_id' => (int) $alumno->ID],
                admin_url('admin.php')
            ),
            'vfc_resend_password_' . (int) $alumno->ID
        );

        echo '<tr>';
        echo '<td><strong>' . esc_html($alumno->display_name ?: $alumno->user_login) . '</strong></td>';
        echo '<td>' . esc_html($alumno->user_email) . '</td>';
        echo '<td>' . esc_html($centro?->nombre ?? '—') . '</td>';
        echo '<td>';
        if ($matriculas === []) {
            echo '—';
        } else {
            $items = [];
            foreach ($matriculas as $m) {
                $resendQrUrl = wp_nonce_url(
                    add_query_arg(
                        ['page' => self::SLUG, 'action' => 'resend_qr', 'matricula_id' => (int) $m->id],
                        admin_url('admin.php')
                    ),
                    'vfc_resend_qr_' . (int) $m->id
                );
                $items[] = sprintf(
                    '%s <small>(<a href="%s">%s</a>)</small>',
                    esc_html($m->alias),
                    esc_url($resendQrUrl),
                    esc_html__('reenviar QR', 'vfc-core')
                );
            }
            echo implode('<br />', $items);
        }
        echo '</td>';
        echo '<td><a class="button" href="' . esc_url($resendPwdUrl) . '">' . esc_html__('Reenviar contraseña', 'vfc-core') . '</a></td>';
        echo '</tr>';
    }

    private function renderForm(): void
    {
        $formUrl = add_query_arg(['page' => self::SLUG, 'action' => 'save'], admin_url('admin.php'));
        $listUrl = add_query_arg(['page' => self::SLUG], admin_url('admin.php'));
        $centros = $this->centros->list(['per_page' => 200])['items'];

        echo '<div class="wrap"><h1>' . esc_html__('Nuevo alumno', 'vfc-core') . '</h1>';
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

        echo '<tr><th><label for="vfc-centro">' . esc_html__('Centro', 'vfc-core') . '</label></th><td>';
        echo '<select id="vfc-centro" name="centro_id">';
        echo '<option value="0">' . esc_html__('— Sin centro —', 'vfc-core') . '</option>';
        foreach ($centros as $c) {
            printf('<option value="%d">%s</option>', (int) $c->id, esc_html($c->nombre));
        }
        echo '</select></td></tr>';

        echo '</tbody></table>';
        submit_button(__('Crear alumno y enviar email', 'vfc-core'));
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
            'created' => __('Alumno creado y email de contraseña enviado.', 'vfc-core'),
            'password_resent' => __('Email de contraseña reenviado.', 'vfc-core'),
            'qr_resent' => __('Correo con el enlace QR reenviado al alumno.', 'vfc-core'),
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

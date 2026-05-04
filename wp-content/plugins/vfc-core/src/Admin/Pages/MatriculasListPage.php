<?php
declare(strict_types=1);

namespace VFC\Core\Admin\Pages;

use VFC\Core\Domain\Centro\CentroRepository;
use VFC\Core\Domain\Edicion\EdicionRepository;
use VFC\Core\Domain\Matricula\MatriculaRepository;
use VFC\Core\Roles\Capabilities;
use VFC\Core\Roles\RolesInstaller;
use VFC\Core\Services\QrEmailService;
use VFC\Core\Services\UsersService;

if (!defined('ABSPATH')) {
    exit;
}

final class MatriculasListPage
{
    public const SLUG = 'vfc-matriculas';
    private const NONCE_FIELD = 'vfc_matricula_nonce';
    private const NONCE_ACTION_CREATE = 'vfc_matricula_create';

    private MatriculaRepository $matriculas;
    private EdicionRepository $ediciones;
    private CentroRepository $centros;
    private QrEmailService $qrEmail;

    public function __construct(
        ?MatriculaRepository $matriculas = null,
        ?EdicionRepository $ediciones = null,
        ?CentroRepository $centros = null,
        ?QrEmailService $qrEmail = null
    ) {
        $this->matriculas = $matriculas ?? new MatriculaRepository();
        $this->ediciones = $ediciones ?? new EdicionRepository();
        $this->centros = $centros ?? new CentroRepository();
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
            __('Matrículas', 'vfc-core'),
            __('Matrículas', 'vfc-core'),
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
        $this->renderListAndForm();
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
        if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleCreate();
        } elseif ($action === 'resend_qr') {
            $this->handleResendQr();
        } elseif ($action === 'delete') {
            $this->handleDelete();
        } elseif ($action === 'rename') {
            $this->handleRename();
        }
    }

    private function handleCreate(): void
    {
        if (!current_user_can(Capabilities::MANAGE_MATRICULAS)) {
            wp_die(esc_html__('Sin permisos.', 'vfc-core'));
        }
        check_admin_referer(self::NONCE_ACTION_CREATE, self::NONCE_FIELD);

        $edicionId = isset($_POST['edicion_id']) ? (int) $_POST['edicion_id'] : 0;
        $alumnoId = isset($_POST['alumno_user_id']) ? (int) $_POST['alumno_user_id'] : 0;
        $alias = (string) ($_POST['alias'] ?? '');

        try {
            $result = $this->matriculas->create($edicionId, $alumnoId, $alias);
            $alumno = get_userdata($alumnoId);
            if ($alumno instanceof \WP_User
                && get_user_meta($alumnoId, UsersService::META_QR_DELIVERED, true)) {
                $this->qrEmail->sendForMatricula((int) $result['matricula']->id);
            }
        } catch (\RuntimeException $e) {
            $this->redirect([
                'edicion_id' => $edicionId,
                'vfc_error' => 'create',
                'vfc_msg' => rawurlencode($e->getMessage()),
            ]);
            return;
        }

        $this->redirect(['edicion_id' => $edicionId, 'vfc_notice' => 'created']);
    }

    private function handleResendQr(): void
    {
        if (!current_user_can(Capabilities::MANAGE_MATRICULAS)) {
            wp_die(esc_html__('Sin permisos.', 'vfc-core'));
        }
        $matriculaId = isset($_GET['matricula_id']) ? (int) $_GET['matricula_id'] : 0;
        check_admin_referer('vfc_resend_qr_' . $matriculaId);
        $this->qrEmail->sendForMatricula($matriculaId);
        $matricula = $this->matriculas->find($matriculaId);
        $this->redirect([
            'edicion_id' => $matricula?->edicionId ?? 0,
            'vfc_notice' => 'qr_resent',
        ]);
    }

    private function handleDelete(): void
    {
        if (!current_user_can(Capabilities::MANAGE_MATRICULAS)) {
            wp_die(esc_html__('Sin permisos.', 'vfc-core'));
        }
        $matriculaId = isset($_GET['matricula_id']) ? (int) $_GET['matricula_id'] : 0;
        check_admin_referer('vfc_delete_matricula_' . $matriculaId);
        $matricula = $this->matriculas->find($matriculaId);
        $edicionId = $matricula?->edicionId ?? 0;
        $this->matriculas->delete($matriculaId);
        $this->redirect(['edicion_id' => $edicionId, 'vfc_notice' => 'deleted']);
    }

    private function handleRename(): void
    {
        if (!current_user_can(Capabilities::MANAGE_MATRICULAS)) {
            wp_die(esc_html__('Sin permisos.', 'vfc-core'));
        }
        check_admin_referer('vfc_rename_matricula');
        $matriculaId = isset($_POST['matricula_id']) ? (int) $_POST['matricula_id'] : 0;
        $alias = (string) ($_POST['alias'] ?? '');
        try {
            $this->matriculas->updateAlias($matriculaId, $alias);
        } catch (\RuntimeException $e) {
            $this->redirect([
                'edicion_id' => isset($_POST['edicion_id']) ? (int) $_POST['edicion_id'] : 0,
                'vfc_error' => 'rename',
                'vfc_msg' => rawurlencode($e->getMessage()),
            ]);
            return;
        }
        $this->redirect([
            'edicion_id' => isset($_POST['edicion_id']) ? (int) $_POST['edicion_id'] : 0,
            'vfc_notice' => 'renamed',
        ]);
    }

    private function renderListAndForm(): void
    {
        $edicionId = isset($_GET['edicion_id']) ? (int) $_GET['edicion_id'] : 0;
        $ediciones = $this->ediciones->list(['per_page' => 200])['items'];
        $edicion = $edicionId > 0 ? $this->ediciones->find($edicionId) : null;
        $centro = $edicion !== null ? $this->centros->find($edicion->centroId) : null;

        echo '<div class="wrap"><h1>' . esc_html__('Matrículas', 'vfc-core') . '</h1>';
        $this->renderNotices();

        echo '<form method="get"><input type="hidden" name="page" value="' . esc_attr(self::SLUG) . '" />';
        echo '<label>' . esc_html__('Edición:', 'vfc-core') . ' ';
        echo '<select name="edicion_id" onchange="this.form.submit()">';
        echo '<option value="0">' . esc_html__('— Selecciona —', 'vfc-core') . '</option>';
        foreach ($ediciones as $e) {
            $centroEd = $this->centros->find($e->centroId);
            printf(
                '<option value="%d" %s>%s — %s [%s]</option>',
                (int) $e->id,
                selected($edicionId, (int) $e->id, false),
                esc_html($centroEd?->nombre ?? '—'),
                esc_html($e->nombre),
                esc_html($e->estado)
            );
        }
        echo '</select></label> ';
        submit_button(__('Filtrar', 'vfc-core'), '', '', false);
        echo '</form>';

        if ($edicion === null) {
            echo '<p>' . esc_html__('Selecciona una edición para ver y gestionar sus matrículas.', 'vfc-core') . '</p></div>';
            return;
        }

        echo '<h2>' . esc_html(sprintf(
            /* translators: 1: edicion name, 2: centro name */
            __('%1$s — %2$s', 'vfc-core'),
            $edicion->nombre,
            $centro?->nombre ?? '—'
        )) . '</h2>';

        $this->renderCreateForm($edicion->id ?? 0, (int) ($centro?->id ?? 0));
        $this->renderMatriculasTable($edicion->id ?? 0);
        echo '</div>';
    }

    private function renderCreateForm(int $edicionId, int $centroId): void
    {
        $alumnos = $this->alumnosDelCentro($centroId);
        $url = add_query_arg(['page' => self::SLUG, 'action' => 'create'], admin_url('admin.php'));
        echo '<h3>' . esc_html__('Matricular alumno', 'vfc-core') . '</h3>';

        if ($alumnos === []) {
            echo '<p>' . esc_html__('No hay alumnos asignados a este centro. Crea alumnos en VFC > Alumnos y asígnales el centro.', 'vfc-core') . '</p>';
            return;
        }

        echo '<form method="post" action="' . esc_url($url) . '" class="vfc-inline-form">';
        wp_nonce_field(self::NONCE_ACTION_CREATE, self::NONCE_FIELD);
        echo '<input type="hidden" name="edicion_id" value="' . (int) $edicionId . '" />';
        echo '<select name="alumno_user_id" required>';
        echo '<option value="">' . esc_html__('— Alumno —', 'vfc-core') . '</option>';
        foreach ($alumnos as $alumno) {
            printf(
                '<option value="%d">%s &lt;%s&gt;</option>',
                (int) $alumno->ID,
                esc_html($alumno->display_name ?: $alumno->user_login),
                esc_html($alumno->user_email)
            );
        }
        echo '</select> ';
        echo '<input type="text" name="alias" placeholder="' . esc_attr__('Alias (visible en compras)', 'vfc-core') . '" required /> ';
        submit_button(__('Matricular', 'vfc-core'), 'primary', 'submit', false);
        echo '</form>';
    }

    /**
     * @return array<int, \WP_User>
     */
    private function alumnosDelCentro(int $centroId): array
    {
        $args = [
            'role' => RolesInstaller::ROLE_ALUMNO,
            'orderby' => 'display_name',
            'order' => 'ASC',
            'number' => 500,
        ];
        if ($centroId > 0) {
            $args['meta_key'] = UsersService::META_CENTRO_ID;
            $args['meta_value'] = (string) $centroId;
        }
        return get_users($args);
    }

    private function renderMatriculasTable(int $edicionId): void
    {
        $matriculas = $this->matriculas->listByEdicion($edicionId);
        echo '<h3>' . esc_html__('Matriculados', 'vfc-core') . '</h3>';
        if ($matriculas === []) {
            echo '<p>' . esc_html__('Aún no hay alumnos matriculados.', 'vfc-core') . '</p>';
            return;
        }

        echo '<table class="wp-list-table widefat fixed striped"><thead><tr>';
        echo '<th>' . esc_html__('Alumno', 'vfc-core') . '</th>';
        echo '<th>' . esc_html__('Email', 'vfc-core') . '</th>';
        echo '<th>' . esc_html__('Alias', 'vfc-core') . '</th>';
        echo '<th>' . esc_html__('Acciones', 'vfc-core') . '</th>';
        echo '</tr></thead><tbody>';

        $renameUrl = add_query_arg(['page' => self::SLUG, 'action' => 'rename'], admin_url('admin.php'));

        foreach ($matriculas as $m) {
            $alumno = get_userdata($m->alumnoUserId);
            $resendUrl = wp_nonce_url(
                add_query_arg(
                    ['page' => self::SLUG, 'action' => 'resend_qr', 'matricula_id' => (int) $m->id],
                    admin_url('admin.php')
                ),
                'vfc_resend_qr_' . (int) $m->id
            );
            $deleteUrl = wp_nonce_url(
                add_query_arg(
                    ['page' => self::SLUG, 'action' => 'delete', 'matricula_id' => (int) $m->id],
                    admin_url('admin.php')
                ),
                'vfc_delete_matricula_' . (int) $m->id
            );

            echo '<tr>';
            echo '<td>' . esc_html($alumno?->display_name ?? $alumno?->user_login ?? ('user#' . $m->alumnoUserId)) . '</td>';
            echo '<td>' . esc_html($alumno?->user_email ?? '—') . '</td>';
            echo '<td>';
            echo '<form method="post" action="' . esc_url($renameUrl) . '" style="display:inline-flex;gap:.4rem;">';
            wp_nonce_field('vfc_rename_matricula', '_wpnonce', true, true);
            echo '<input type="hidden" name="matricula_id" value="' . (int) $m->id . '" />';
            echo '<input type="hidden" name="edicion_id" value="' . (int) $edicionId . '" />';
            echo '<input type="text" name="alias" value="' . esc_attr($m->alias) . '" />';
            echo '<button class="button" type="submit">' . esc_html__('Guardar', 'vfc-core') . '</button>';
            echo '</form>';
            echo '</td>';
            echo '<td>';
            printf(
                '<a class="button" href="%s">%s</a> <a class="button" href="%s" onclick="return confirm(\'%s\');">%s</a>',
                esc_url($resendUrl),
                esc_html__('Reenviar QR', 'vfc-core'),
                esc_url($deleteUrl),
                esc_js(__('¿Borrar matrícula?', 'vfc-core')),
                esc_html__('Borrar', 'vfc-core')
            );
            echo '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
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
            'created' => __('Matrícula creada y QR enviado (si el alumno ya tiene contraseña).', 'vfc-core'),
            'qr_resent' => __('Correo con el enlace QR reenviado al alumno.', 'vfc-core'),
            'deleted' => __('Matrícula eliminada.', 'vfc-core'),
            'renamed' => __('Alias actualizado.', 'vfc-core'),
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

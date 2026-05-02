<?php
declare(strict_types=1);

namespace VFC\Core\Admin\Pages;

use VFC\Core\Domain\Centro\CentroRepository;
use VFC\Core\Domain\Edicion\Edicion;
use VFC\Core\Domain\Edicion\EdicionRepository;
use VFC\Core\Domain\Edicion\EstadoEdicion;
use VFC\Core\Roles\Capabilities;

if (!defined('ABSPATH')) {
    exit;
}

final class EdicionesListPage
{
    public const SLUG = 'vfc-ediciones';
    private const NONCE_FIELD = 'vfc_ediciones_nonce';
    private const NONCE_ACTION_SAVE = 'vfc_ediciones_save';

    private EdicionRepository $repo;
    private CentroRepository $centros;

    public function __construct(?EdicionRepository $repo = null, ?CentroRepository $centros = null)
    {
        $this->repo = $repo ?? new EdicionRepository();
        $this->centros = $centros ?? new CentroRepository();
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
            __('Ediciones', 'vfc-core'),
            __('Ediciones', 'vfc-core'),
            Capabilities::MANAGE_EDICIONES,
            self::SLUG,
            [$this, 'render']
        );
    }

    public function render(): void
    {
        if (!current_user_can(Capabilities::MANAGE_EDICIONES)) {
            wp_die(esc_html__('No tienes permisos.', 'vfc-core'));
        }

        $action = isset($_GET['action']) ? sanitize_key((string) $_GET['action']) : '';
        if ($action === 'edit' || $action === 'new') {
            $this->renderForm($action);
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
            return;
        }
        if ($action === 'transition') {
            $this->handleTransition();
            return;
        }
        if ($action === 'delete') {
            $this->handleDelete();
        }
    }

    private function handleSave(): void
    {
        if (!current_user_can(Capabilities::MANAGE_EDICIONES)) {
            wp_die(esc_html__('Sin permisos.', 'vfc-core'));
        }
        check_admin_referer(self::NONCE_ACTION_SAVE, self::NONCE_FIELD);

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        try {
            $edicion = new Edicion(
                id: $id > 0 ? $id : null,
                centroId: isset($_POST['centro_id']) ? (int) $_POST['centro_id'] : 0,
                nombre: sanitize_text_field((string) ($_POST['nombre'] ?? '')),
                fechaInicio: $this->nullableDate($_POST['fecha_inicio'] ?? null),
                fechaFin: $this->nullableDate($_POST['fecha_fin'] ?? null),
                estado: $id > 0 ? sanitize_key((string) ($_POST['estado_actual'] ?? EstadoEdicion::BORRADOR)) : EstadoEdicion::BORRADOR
            );
            $saved = $this->repo->save($edicion);
        } catch (\RuntimeException $e) {
            $this->redirect([
                'action' => $id > 0 ? 'edit' : 'new',
                'id' => $id,
                'vfc_error' => 'save',
                'vfc_msg' => rawurlencode($e->getMessage()),
            ]);
            return;
        }

        $this->redirect([
            'action' => 'edit',
            'id' => (int) $saved->id,
            'vfc_notice' => $id > 0 ? 'updated' : 'created',
        ]);
    }

    private function handleTransition(): void
    {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $to = isset($_GET['to']) ? sanitize_key((string) $_GET['to']) : '';
        check_admin_referer('vfc_edicion_transition_' . $id);

        $cap = $to === EstadoEdicion::APROBADA || $to === EstadoEdicion::RECHAZADA
            ? Capabilities::APPROVE_EDICIONES
            : Capabilities::MANAGE_EDICIONES;
        if (!current_user_can($cap)) {
            wp_die(esc_html__('Sin permisos para esta transición.', 'vfc-core'));
        }

        try {
            $this->repo->transitionTo($id, $to);
        } catch (\RuntimeException $e) {
            $this->redirect([
                'action' => 'edit',
                'id' => $id,
                'vfc_error' => 'transition',
                'vfc_msg' => rawurlencode($e->getMessage()),
            ]);
            return;
        }

        $this->redirect(['action' => 'edit', 'id' => $id, 'vfc_notice' => 'transitioned']);
    }

    private function handleDelete(): void
    {
        if (!current_user_can(Capabilities::MANAGE_EDICIONES)) {
            wp_die(esc_html__('Sin permisos.', 'vfc-core'));
        }
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        check_admin_referer('vfc_delete_edicion_' . $id);
        $this->repo->delete($id);
        $this->redirect(['vfc_notice' => 'deleted']);
    }

    private function renderList(): void
    {
        $table = new EdicionesListTable($this->repo, $this->centros);
        $table->prepare_items();

        $newUrl = add_query_arg(['page' => self::SLUG, 'action' => 'new'], admin_url('admin.php'));

        echo '<div class="wrap">';
        echo '<h1 class="wp-heading-inline">' . esc_html__('Ediciones', 'vfc-core') . '</h1>';
        echo ' <a href="' . esc_url($newUrl) . '" class="page-title-action">' . esc_html__('Añadir nueva', 'vfc-core') . '</a>';
        echo '<hr class="wp-header-end">';

        $this->renderNotices();
        $this->renderEstadoFilter();

        echo '<form method="get">';
        echo '<input type="hidden" name="page" value="' . esc_attr(self::SLUG) . '" />';
        if (!empty($_GET['estado'])) {
            echo '<input type="hidden" name="estado" value="' . esc_attr(sanitize_key((string) $_GET['estado'])) . '" />';
        }
        $table->search_box(__('Buscar ediciones', 'vfc-core'), 'vfc-search-ediciones');
        $table->display();
        echo '</form>';
        echo '</div>';
    }

    private function renderEstadoFilter(): void
    {
        $current = isset($_GET['estado']) ? sanitize_key((string) $_GET['estado']) : '';
        $base = ['page' => self::SLUG];

        $links = [];
        $links[] = sprintf(
            '<a href="%s"%s>%s</a>',
            esc_url(add_query_arg($base, admin_url('admin.php'))),
            $current === '' ? ' class="current"' : '',
            esc_html__('Todas', 'vfc-core')
        );
        foreach (EstadoEdicion::labels() as $estado => $label) {
            $url = add_query_arg($base + ['estado' => $estado], admin_url('admin.php'));
            $links[] = sprintf(
                '<a href="%s"%s>%s</a>',
                esc_url($url),
                $current === $estado ? ' class="current"' : '',
                esc_html($label)
            );
        }
        echo '<ul class="subsubsub"><li>' . implode(' | </li><li>', $links) . '</li></ul><br class="clear">';
    }

    private function renderForm(string $action): void
    {
        $edicion = null;
        if ($action === 'edit') {
            $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
            $edicion = $this->repo->find($id);
            if ($edicion === null) {
                echo '<div class="wrap"><div class="notice notice-error"><p>' . esc_html__('Edición no encontrada.', 'vfc-core') . '</p></div></div>';
                return;
            }
        }

        $title = $edicion === null ? __('Nueva edición', 'vfc-core') : __('Editar edición', 'vfc-core');
        $formUrl = add_query_arg(['page' => self::SLUG, 'action' => 'save'], admin_url('admin.php'));
        $listUrl = add_query_arg(['page' => self::SLUG], admin_url('admin.php'));

        echo '<div class="wrap"><h1>' . esc_html($title) . '</h1>';
        $this->renderNotices();

        $centros = $this->centros->list(['per_page' => 200])['items'];

        echo '<form method="post" action="' . esc_url($formUrl) . '">';
        wp_nonce_field(self::NONCE_ACTION_SAVE, self::NONCE_FIELD);
        if ($edicion !== null) {
            echo '<input type="hidden" name="id" value="' . (int) $edicion->id . '" />';
            echo '<input type="hidden" name="estado_actual" value="' . esc_attr($edicion->estado) . '" />';
        }

        echo '<table class="form-table" role="presentation"><tbody>';

        echo '<tr><th><label for="vfc-centro_id">' . esc_html__('Centro', 'vfc-core') . '</label></th><td>';
        echo '<select id="vfc-centro_id" name="centro_id" required>';
        echo '<option value="">' . esc_html__('— Selecciona un centro —', 'vfc-core') . '</option>';
        foreach ($centros as $c) {
            printf(
                '<option value="%d" %s>%s</option>',
                (int) $c->id,
                selected($edicion?->centroId ?? 0, (int) $c->id, false),
                esc_html($c->nombre)
            );
        }
        echo '</select></td></tr>';

        echo '<tr><th><label for="vfc-nombre">' . esc_html__('Nombre', 'vfc-core') . '</label></th><td>';
        printf(
            '<input type="text" id="vfc-nombre" name="nombre" value="%s" class="regular-text" required />',
            esc_attr($edicion?->nombre ?? '')
        );
        echo '</td></tr>';

        echo '<tr><th><label for="vfc-fecha_inicio">' . esc_html__('Fecha inicio', 'vfc-core') . '</label></th><td>';
        printf(
            '<input type="date" id="vfc-fecha_inicio" name="fecha_inicio" value="%s" />',
            esc_attr($edicion?->fechaInicio ?? '')
        );
        echo '</td></tr>';

        echo '<tr><th><label for="vfc-fecha_fin">' . esc_html__('Fecha fin', 'vfc-core') . '</label></th><td>';
        printf(
            '<input type="date" id="vfc-fecha_fin" name="fecha_fin" value="%s" />',
            esc_attr($edicion?->fechaFin ?? '')
        );
        echo '</td></tr>';

        if ($edicion !== null) {
            echo '<tr><th>' . esc_html__('Estado', 'vfc-core') . '</th><td>';
            $labels = EstadoEdicion::labels();
            echo '<strong>' . esc_html($labels[$edicion->estado] ?? $edicion->estado) . '</strong>';
            echo '</td></tr>';
        }

        echo '</tbody></table>';

        submit_button($edicion === null ? __('Crear edición', 'vfc-core') : __('Guardar cambios', 'vfc-core'));
        echo ' <a href="' . esc_url($listUrl) . '" class="button">' . esc_html__('Cancelar', 'vfc-core') . '</a>';
        echo '</form>';

        if ($edicion !== null) {
            $this->renderTransitionButtons($edicion);
        }

        echo '</div>';
    }

    private function renderTransitionButtons(Edicion $edicion): void
    {
        $next = EstadoEdicion::nextStates($edicion->estado);
        if ($next === []) {
            return;
        }
        $labels = EstadoEdicion::labels();
        echo '<h2>' . esc_html__('Cambiar estado', 'vfc-core') . '</h2><p>';
        foreach ($next as $to) {
            $url = wp_nonce_url(
                add_query_arg(
                    ['page' => self::SLUG, 'action' => 'transition', 'id' => (int) $edicion->id, 'to' => $to],
                    admin_url('admin.php')
                ),
                'vfc_edicion_transition_' . (int) $edicion->id
            );
            $cap = $to === EstadoEdicion::APROBADA || $to === EstadoEdicion::RECHAZADA
                ? Capabilities::APPROVE_EDICIONES
                : Capabilities::MANAGE_EDICIONES;
            if (!current_user_can($cap)) {
                continue;
            }
            printf(
                '<a class="button" href="%s">%s</a> ',
                esc_url($url),
                /* translators: %s: target state label */
                esc_html(sprintf(__('Pasar a “%s”', 'vfc-core'), $labels[$to] ?? $to))
            );
        }
        echo '</p>';
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
            'created' => __('Edición creada.', 'vfc-core'),
            'updated' => __('Cambios guardados.', 'vfc-core'),
            'deleted' => __('Edición eliminada.', 'vfc-core'),
            'transitioned' => __('Estado actualizado.', 'vfc-core'),
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

    private function nullableDate(mixed $value): ?string
    {
        if (!is_string($value) || $value === '') {
            return null;
        }
        $value = sanitize_text_field($value);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }
        return $value;
    }
}

<?php
declare(strict_types=1);

namespace VFC\Core\Admin\Pages;

use VFC\Core\Domain\Centro\CentroRepository;
use VFC\Core\Domain\Edicion\EdicionRepository;
use VFC\Core\Domain\Liquidacion\LiquidacionRepository;
use VFC\Core\Roles\Capabilities;

if (!defined('ABSPATH')) {
    exit;
}

final class LiquidacionesListPage
{
    public const SLUG = 'vfc-liquidaciones';
    private const NONCE_FIELD = 'vfc_liquidacion_nonce';
    private const NONCE_ACTION_CREATE = 'vfc_liquidacion_create';

    private LiquidacionRepository $repo;
    private CentroRepository $centros;
    private EdicionRepository $ediciones;

    public function __construct(
        ?LiquidacionRepository $repo = null,
        ?CentroRepository $centros = null,
        ?EdicionRepository $ediciones = null
    ) {
        $this->repo = $repo ?? new LiquidacionRepository();
        $this->centros = $centros ?? new CentroRepository();
        $this->ediciones = $ediciones ?? new EdicionRepository();
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
            __('Liquidaciones', 'vfc-core'),
            __('Liquidaciones', 'vfc-core'),
            'read',
            self::SLUG,
            [$this, 'render']
        );
    }

    public function render(): void
    {
        if (!current_user_can(Capabilities::MANAGE_LIQUIDACIONES) && !current_user_can(Capabilities::VIEW_AUDIT)) {
            wp_die(esc_html__('No tienes permisos.', 'vfc-core'));
        }
        $action = isset($_GET['action']) ? sanitize_key((string) $_GET['action']) : '';
        if ($action === 'new' && current_user_can(Capabilities::MANAGE_LIQUIDACIONES)) {
            $this->renderCreateForm();
            return;
        }
        if ($action === 'view') {
            $this->renderDetail();
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
        if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleCreate();
        }
    }

    private function handleCreate(): void
    {
        if (!current_user_can(Capabilities::MANAGE_LIQUIDACIONES)) {
            wp_die(esc_html__('Sin permisos.', 'vfc-core'));
        }
        check_admin_referer(self::NONCE_ACTION_CREATE, self::NONCE_FIELD);

        $centroId = isset($_POST['centro_id']) ? (int) $_POST['centro_id'] : 0;
        $fecha = sanitize_text_field((string) ($_POST['fecha'] ?? ''));
        $referencia = isset($_POST['referencia']) ? sanitize_text_field((string) $_POST['referencia']) : null;
        $notas = isset($_POST['notas']) ? sanitize_textarea_field((string) $_POST['notas']) : null;

        $seleccionRaw = isset($_POST['seleccion']) && is_array($_POST['seleccion']) ? $_POST['seleccion'] : [];
        $seleccion = [];
        foreach ($seleccionRaw as $key) {
            $parts = explode(':', (string) $key);
            if (count($parts) !== 2) {
                continue;
            }
            $seleccion[] = ['alumno_user_id' => (int) $parts[0], 'edicion_id' => (int) $parts[1]];
        }

        try {
            $liq = $this->repo->create($centroId, $fecha, $referencia ?: null, $notas ?: null, $seleccion);
        } catch (\RuntimeException $e) {
            $this->redirect([
                'action' => 'new',
                'centro_id' => $centroId,
                'vfc_error' => 'create',
                'vfc_msg' => rawurlencode($e->getMessage()),
            ]);
            return;
        }

        $this->redirect(['action' => 'view', 'id' => (int) $liq->id, 'vfc_notice' => 'created']);
    }

    private function renderList(): void
    {
        $isSuper = current_user_can(Capabilities::MANAGE_LIQUIDACIONES);

        $liquidaciones = $isSuper
            ? $this->repo->listAll()
            : $this->liquidacionesDelUsuario();

        $newUrl = add_query_arg(['page' => self::SLUG, 'action' => 'new'], admin_url('admin.php'));

        echo '<div class="wrap"><h1 class="wp-heading-inline">' . esc_html__('Liquidaciones', 'vfc-core') . '</h1>';
        if ($isSuper) {
            echo ' <a class="page-title-action" href="' . esc_url($newUrl) . '">' . esc_html__('Nueva liquidación', 'vfc-core') . '</a>';
        }
        echo '<hr class="wp-header-end">';
        $this->renderNotices();

        echo '<table class="wp-list-table widefat fixed striped"><thead><tr>';
        echo '<th>' . esc_html__('Fecha', 'vfc-core') . '</th>';
        echo '<th>' . esc_html__('Centro', 'vfc-core') . '</th>';
        echo '<th>' . esc_html__('Importe', 'vfc-core') . '</th>';
        echo '<th>' . esc_html__('Referencia', 'vfc-core') . '</th>';
        echo '<th>' . esc_html__('Estado', 'vfc-core') . '</th>';
        echo '<th></th>';
        echo '</tr></thead><tbody>';

        if ($liquidaciones === []) {
            echo '<tr><td colspan="6">' . esc_html__('No hay liquidaciones.', 'vfc-core') . '</td></tr>';
        } else {
            foreach ($liquidaciones as $liq) {
                $centro = $this->centros->find($liq->centroId);
                $viewUrl = add_query_arg(
                    ['page' => self::SLUG, 'action' => 'view', 'id' => (int) $liq->id],
                    admin_url('admin.php')
                );
                echo '<tr>';
                echo '<td>' . esc_html($liq->fecha) . '</td>';
                echo '<td>' . esc_html($centro?->nombre ?? '—') . '</td>';
                echo '<td>' . esc_html(number_format_i18n($liq->importe, 2)) . ' €</td>';
                echo '<td>' . esc_html((string) ($liq->referencia ?? '')) . '</td>';
                echo '<td>' . esc_html($liq->estado) . '</td>';
                echo '<td><a class="button" href="' . esc_url($viewUrl) . '">' . esc_html__('Ver detalle', 'vfc-core') . '</a></td>';
                echo '</tr>';
            }
        }
        echo '</tbody></table></div>';
    }

    /**
     * @return array<int, \VFC\Core\Domain\Liquidacion\Liquidacion>
     */
    private function liquidacionesDelUsuario(): array
    {
        $userId = get_current_user_id();
        $centroId = (int) get_user_meta($userId, 'vfc_centro_admin_id', true);
        if ($centroId <= 0) {
            return [];
        }
        return $this->repo->listByCentro($centroId);
    }

    private function renderCreateForm(): void
    {
        $centros = $this->centros->list(['per_page' => 200])['items'];
        $centroId = isset($_GET['centro_id']) ? (int) $_GET['centro_id'] : 0;
        $url = add_query_arg(['page' => self::SLUG, 'action' => 'create'], admin_url('admin.php'));

        echo '<div class="wrap"><h1>' . esc_html__('Nueva liquidación', 'vfc-core') . '</h1>';
        $this->renderNotices();

        echo '<form method="get"><input type="hidden" name="page" value="' . esc_attr(self::SLUG) . '" />';
        echo '<input type="hidden" name="action" value="new" />';
        echo '<label>' . esc_html__('Centro:', 'vfc-core') . ' ';
        echo '<select name="centro_id" onchange="this.form.submit()">';
        echo '<option value="0">' . esc_html__('— Selecciona centro —', 'vfc-core') . '</option>';
        foreach ($centros as $c) {
            printf(
                '<option value="%d" %s>%s</option>',
                (int) $c->id,
                selected($centroId, (int) $c->id, false),
                esc_html($c->nombre)
            );
        }
        echo '</select></label></form>';

        if ($centroId <= 0) {
            echo '<p>' . esc_html__('Selecciona un centro para ver los saldos pendientes.', 'vfc-core') . '</p></div>';
            return;
        }

        $pendientes = $this->repo->pendientesPorCentro($centroId);
        if ($pendientes === []) {
            echo '<p>' . esc_html__('No hay saldos confirmados pendientes para este centro.', 'vfc-core') . '</p></div>';
            return;
        }

        echo '<form method="post" action="' . esc_url($url) . '">';
        wp_nonce_field(self::NONCE_ACTION_CREATE, self::NONCE_FIELD);
        echo '<input type="hidden" name="centro_id" value="' . (int) $centroId . '" />';

        echo '<table class="form-table" role="presentation"><tbody>';
        echo '<tr><th><label for="vfc-fecha">' . esc_html__('Fecha de la liquidación', 'vfc-core') . '</label></th><td>';
        echo '<input type="date" id="vfc-fecha" name="fecha" required value="' . esc_attr(gmdate('Y-m-d')) . '" />';
        echo '</td></tr>';
        echo '<tr><th><label for="vfc-ref">' . esc_html__('Referencia (transferencia, etc.)', 'vfc-core') . '</label></th><td>';
        echo '<input type="text" id="vfc-ref" name="referencia" class="regular-text" />';
        echo '</td></tr>';
        echo '<tr><th><label for="vfc-notas">' . esc_html__('Notas', 'vfc-core') . '</label></th><td>';
        echo '<textarea id="vfc-notas" name="notas" rows="3" class="large-text"></textarea>';
        echo '</td></tr>';
        echo '</tbody></table>';

        echo '<h2>' . esc_html__('Saldos confirmados pendientes', 'vfc-core') . '</h2>';
        echo '<table class="wp-list-table widefat fixed striped"><thead><tr>';
        echo '<th><input type="checkbox" onclick="document.querySelectorAll(\'.vfc-liq-cb\').forEach(c=>c.checked=this.checked);" /></th>';
        echo '<th>' . esc_html__('Alumno', 'vfc-core') . '</th>';
        echo '<th>' . esc_html__('Edición', 'vfc-core') . '</th>';
        echo '<th>' . esc_html__('Movimientos', 'vfc-core') . '</th>';
        echo '<th>' . esc_html__('Importe (sin IVA)', 'vfc-core') . '</th>';
        echo '</tr></thead><tbody>';

        $total = 0.0;
        foreach ($pendientes as $row) {
            $alumno = get_userdata($row['alumno_user_id']);
            $edicion = $this->ediciones->find($row['edicion_id']);
            $key = $row['alumno_user_id'] . ':' . $row['edicion_id'];
            $total += $row['importe'];
            printf(
                '<tr><td><input class="vfc-liq-cb" type="checkbox" name="seleccion[]" value="%s" checked /></td>'
                . '<td>%s</td><td>%s</td><td>%d</td><td>%s €</td></tr>',
                esc_attr($key),
                esc_html($alumno?->display_name ?? ('user#' . $row['alumno_user_id'])),
                esc_html($edicion?->nombre ?? ('edicion#' . $row['edicion_id'])),
                (int) $row['n_movimientos'],
                esc_html(number_format_i18n($row['importe'], 2))
            );
        }
        echo '</tbody>';
        echo '<tfoot><tr><th></th><th></th><th></th><th>' . esc_html__('Total seleccionado:', 'vfc-core') . '</th>';
        echo '<th>' . esc_html(number_format_i18n($total, 2)) . ' €</th></tr></tfoot>';
        echo '</table>';

        submit_button(__('Crear liquidación', 'vfc-core'));
        echo '</form></div>';
    }

    private function renderDetail(): void
    {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $liq = $this->repo->find($id);
        if ($liq === null) {
            echo '<div class="wrap"><div class="notice notice-error"><p>' . esc_html__('Liquidación no encontrada.', 'vfc-core') . '</p></div></div>';
            return;
        }
        if (!current_user_can(Capabilities::MANAGE_LIQUIDACIONES)) {
            $userCentroId = (int) get_user_meta(get_current_user_id(), 'vfc_centro_admin_id', true);
            if ($userCentroId !== $liq->centroId) {
                wp_die(esc_html__('Sin permisos para ver esta liquidación.', 'vfc-core'));
            }
        }

        $centro = $this->centros->find($liq->centroId);
        $items = $this->repo->itemsForLiquidacion($id);

        $listUrl = add_query_arg(['page' => self::SLUG], admin_url('admin.php'));

        echo '<div class="wrap"><h1>' . esc_html__('Detalle de liquidación', 'vfc-core') . '</h1>';
        echo '<p><a href="' . esc_url($listUrl) . '">&laquo; ' . esc_html__('Volver', 'vfc-core') . '</a></p>';
        $this->renderNotices();

        echo '<table class="form-table" role="presentation"><tbody>';
        printf('<tr><th>%s</th><td>%s</td></tr>', esc_html__('Centro', 'vfc-core'), esc_html($centro?->nombre ?? '—'));
        printf('<tr><th>%s</th><td>%s</td></tr>', esc_html__('Fecha', 'vfc-core'), esc_html($liq->fecha));
        printf('<tr><th>%s</th><td>%s €</td></tr>', esc_html__('Importe total', 'vfc-core'), esc_html(number_format_i18n($liq->importe, 2)));
        printf('<tr><th>%s</th><td>%s</td></tr>', esc_html__('Referencia', 'vfc-core'), esc_html((string) ($liq->referencia ?? '—')));
        printf('<tr><th>%s</th><td>%s</td></tr>', esc_html__('Notas', 'vfc-core'), esc_html((string) ($liq->notas ?? '—')));
        printf('<tr><th>%s</th><td>%s</td></tr>', esc_html__('Estado', 'vfc-core'), esc_html($liq->estado));
        echo '</tbody></table>';

        echo '<h2>' . esc_html__('Detalle por alumno', 'vfc-core') . '</h2>';
        echo '<table class="wp-list-table widefat fixed striped"><thead><tr>';
        echo '<th>' . esc_html__('Alumno', 'vfc-core') . '</th>';
        echo '<th>' . esc_html__('Edición', 'vfc-core') . '</th>';
        echo '<th>' . esc_html__('Importe (sin IVA)', 'vfc-core') . '</th>';
        echo '</tr></thead><tbody>';
        if ($items === []) {
            echo '<tr><td colspan="3">' . esc_html__('Sin items.', 'vfc-core') . '</td></tr>';
        } else {
            foreach ($items as $it) {
                $alumno = get_userdata((int) $it['alumno_user_id']);
                $edicion = $this->ediciones->find((int) $it['edicion_id']);
                printf(
                    '<tr><td>%s</td><td>%s</td><td>%s €</td></tr>',
                    esc_html($alumno?->display_name ?? ('user#' . $it['alumno_user_id'])),
                    esc_html($edicion?->nombre ?? ('edicion#' . $it['edicion_id'])),
                    esc_html(number_format_i18n((float) $it['importe'], 2))
                );
            }
        }
        echo '</tbody></table>';
        echo '</div>';
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
        if (!empty($_GET['vfc_notice']) && (string) $_GET['vfc_notice'] === 'created') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Liquidación creada.', 'vfc-core') . '</p></div>';
        }
        if (!empty($_GET['vfc_error'])) {
            $msg = isset($_GET['vfc_msg'])
                ? rawurldecode((string) $_GET['vfc_msg'])
                : __('Operación no realizada.', 'vfc-core');
            printf('<div class="notice notice-error is-dismissible"><p>%s</p></div>', esc_html($msg));
        }
    }
}

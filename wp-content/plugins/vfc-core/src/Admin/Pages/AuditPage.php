<?php
declare(strict_types=1);

namespace VFC\Core\Admin\Pages;

use VFC\Core\Domain\Centro\CentroRepository;
use VFC\Core\Roles\Capabilities;
use VFC\Core\Services\AuditService;

if (!defined('ABSPATH')) {
    exit;
}

final class AuditPage
{
    public const SLUG = 'vfc-auditoria';

    private CentroRepository $centros;

    public function __construct(?CentroRepository $centros = null)
    {
        $this->centros = $centros ?? new CentroRepository();
    }

    public function register(): void
    {
        add_action('admin_menu', [$this, 'registerMenu']);
        add_action('admin_init', [$this, 'handleExport']);
    }

    public function registerMenu(): void
    {
        add_submenu_page(
            CentrosListPage::PARENT_SLUG,
            __('Auditoría', 'vfc-core'),
            __('Auditoría', 'vfc-core'),
            Capabilities::VIEW_AUDIT,
            self::SLUG,
            [$this, 'render']
        );
    }

    public function render(): void
    {
        if (!current_user_can(Capabilities::VIEW_AUDIT)) {
            wp_die(esc_html__('No tienes permisos.', 'vfc-core'));
        }

        $filters = $this->collectFilters();
        $page = max(1, (int) ($_GET['paged'] ?? 1));
        $perPage = 50;
        $list = AuditService::list($filters + ['page' => $page, 'per_page' => $perPage]);
        $totalPages = (int) ceil(max(1, $list['total']) / $perPage);

        $exportUrl = wp_nonce_url(
            add_query_arg(
                array_merge($filters, ['page' => self::SLUG, 'action' => 'export']),
                admin_url('admin.php')
            ),
            'vfc_audit_export'
        );

        echo '<div class="wrap"><h1 class="wp-heading-inline">' . esc_html__('Auditoría', 'vfc-core') . '</h1>';
        if (current_user_can(Capabilities::EXPORT_AUDIT)) {
            echo ' <a class="page-title-action" href="' . esc_url($exportUrl) . '">' . esc_html__('Exportar CSV', 'vfc-core') . '</a>';
        }
        echo '<hr class="wp-header-end">';

        $this->renderFilters($filters);

        echo '<p>' . esc_html(sprintf(
            /* translators: %d: total count */
            __('%d entradas encontradas.', 'vfc-core'),
            $list['total']
        )) . '</p>';

        echo '<table class="wp-list-table widefat fixed striped"><thead><tr>';
        echo '<th style="width:140px;">' . esc_html__('Fecha (UTC)', 'vfc-core') . '</th>';
        echo '<th>' . esc_html__('Actor', 'vfc-core') . '</th>';
        echo '<th>' . esc_html__('Acción', 'vfc-core') . '</th>';
        echo '<th>' . esc_html__('Entidad', 'vfc-core') . '</th>';
        echo '<th>' . esc_html__('Centro', 'vfc-core') . '</th>';
        echo '<th>' . esc_html__('Datos', 'vfc-core') . '</th>';
        echo '</tr></thead><tbody>';

        if ($list['items'] === []) {
            echo '<tr><td colspan="6">' . esc_html__('No hay entradas que coincidan.', 'vfc-core') . '</td></tr>';
        } else {
            foreach ($list['items'] as $row) {
                $this->renderRow($row);
            }
        }

        echo '</tbody></table>';

        $this->renderPagination($page, $totalPages, $filters);
        echo '</div>';
    }

    public function handleExport(): void
    {
        if (!is_admin()) {
            return;
        }
        if (!isset($_GET['page']) || (string) $_GET['page'] !== self::SLUG) {
            return;
        }
        if (!isset($_GET['action']) || (string) $_GET['action'] !== 'export') {
            return;
        }
        if (!current_user_can(Capabilities::EXPORT_AUDIT)) {
            wp_die(esc_html__('Sin permisos para exportar.', 'vfc-core'));
        }
        check_admin_referer('vfc_audit_export');

        $filters = $this->collectFilters();
        $centro = !empty($filters['centro_id']) ? $this->centros->find((int) $filters['centro_id']) : null;
        $filename = sprintf(
            'vfc-audit-%s-%s.csv',
            $centro?->slug ?? 'todos',
            gmdate('Ymd-His')
        );

        nocache_headers();
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        echo "\xEF\xBB\xBF"; // BOM UTF-8 para que Excel interprete bien.
        $out = fopen('php://output', 'w');
        if ($out === false) {
            exit;
        }
        fputcsv($out, ['fecha_utc', 'actor_user_id', 'actor_login', 'accion', 'entidad_tipo', 'entidad_id', 'centro_id', 'ip', 'datos'], ';');
        foreach (AuditService::iterate($filters) as $row) {
            $actorLogin = '';
            if (!empty($row['actor_user_id'])) {
                $u = get_userdata((int) $row['actor_user_id']);
                $actorLogin = $u instanceof \WP_User ? $u->user_login : '';
            }
            fputcsv($out, [
                $row['fecha'] ?? '',
                $row['actor_user_id'] ?? '',
                $actorLogin,
                $row['accion'] ?? '',
                $row['entidad_tipo'] ?? '',
                $row['entidad_id'] ?? '',
                $row['centro_id'] ?? '',
                $row['ip'] ?? '',
                (string) ($row['datos'] ?? ''),
            ], ';');
        }
        fclose($out);
        exit;
    }

    /**
     * @return array<string, mixed>
     */
    private function collectFilters(): array
    {
        $f = [];
        if (!empty($_GET['centro_id'])) {
            $f['centro_id'] = (int) $_GET['centro_id'];
        }
        if (!empty($_GET['accion'])) {
            $f['accion'] = sanitize_text_field((string) $_GET['accion']);
        }
        if (!empty($_GET['entidad'])) {
            $f['entidad'] = sanitize_text_field((string) $_GET['entidad']);
        }
        if (!empty($_GET['from']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $_GET['from']) === 1) {
            $f['from'] = ((string) $_GET['from']) . ' 00:00:00';
        }
        if (!empty($_GET['to']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $_GET['to']) === 1) {
            $f['to'] = ((string) $_GET['to']) . ' 23:59:59';
        }
        return $f;
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function renderFilters(array $filters): void
    {
        $centros = $this->centros->list(['per_page' => 200])['items'];
        $from = isset($_GET['from']) ? sanitize_text_field((string) $_GET['from']) : '';
        $to = isset($_GET['to']) ? sanitize_text_field((string) $_GET['to']) : '';

        echo '<form method="get" class="vfc-audit-filters">';
        echo '<input type="hidden" name="page" value="' . esc_attr(self::SLUG) . '" />';
        echo '<label>' . esc_html__('Centro:', 'vfc-core') . ' <select name="centro_id">';
        echo '<option value="">' . esc_html__('Todos', 'vfc-core') . '</option>';
        foreach ($centros as $c) {
            printf(
                '<option value="%d" %s>%s</option>',
                (int) $c->id,
                selected((int) ($filters['centro_id'] ?? 0), (int) $c->id, false),
                esc_html($c->nombre)
            );
        }
        echo '</select></label> ';

        printf(
            '<label>%s <input type="text" name="accion" value="%s" placeholder="create, update, delete..." /></label> ',
            esc_html__('Acción:', 'vfc-core'),
            esc_attr((string) ($filters['accion'] ?? ''))
        );
        printf(
            '<label>%s <input type="text" name="entidad" value="%s" placeholder="centro, edicion..." /></label> ',
            esc_html__('Entidad:', 'vfc-core'),
            esc_attr((string) ($filters['entidad'] ?? ''))
        );
        printf(
            '<label>%s <input type="date" name="from" value="%s" /></label> ',
            esc_html__('Desde:', 'vfc-core'),
            esc_attr($from)
        );
        printf(
            '<label>%s <input type="date" name="to" value="%s" /></label> ',
            esc_html__('Hasta:', 'vfc-core'),
            esc_attr($to)
        );
        submit_button(__('Filtrar', 'vfc-core'), '', '', false);
        echo '</form>';
    }

    /**
     * @param array<string, mixed> $row
     */
    private function renderRow(array $row): void
    {
        $actor = '—';
        if (!empty($row['actor_user_id'])) {
            $u = get_userdata((int) $row['actor_user_id']);
            $actor = $u instanceof \WP_User
                ? sprintf('%s <small>(%d)</small>', esc_html($u->user_login), (int) $u->ID)
                : '#' . (int) $row['actor_user_id'];
        }
        $datos = (string) ($row['datos'] ?? '');
        if ($datos !== '') {
            $datos = '<code style="white-space:pre-wrap;display:block;max-width:480px;">'
                . esc_html($datos)
                . '</code>';
        }
        echo '<tr>';
        echo '<td>' . esc_html((string) ($row['fecha'] ?? '')) . '</td>';
        echo '<td>' . wp_kses_post($actor) . '</td>';
        echo '<td><code>' . esc_html((string) ($row['accion'] ?? '')) . '</code></td>';
        echo '<td>' . esc_html((string) ($row['entidad_tipo'] ?? '')) . '#' . (int) ($row['entidad_id'] ?? 0) . '</td>';
        echo '<td>' . esc_html((string) ($row['centro_id'] ?? '—')) . '</td>';
        echo '<td>' . wp_kses_post($datos) . '</td>';
        echo '</tr>';
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function renderPagination(int $current, int $totalPages, array $filters): void
    {
        if ($totalPages <= 1) {
            return;
        }
        echo '<div class="tablenav"><div class="tablenav-pages"><span class="pagination-links">';
        for ($i = 1; $i <= min($totalPages, 20); $i++) {
            $url = add_query_arg(
                array_merge($filters, ['page' => self::SLUG, 'paged' => $i]),
                admin_url('admin.php')
            );
            printf(
                '<a class="button %s" href="%s">%d</a> ',
                $i === $current ? 'button-primary' : '',
                esc_url($url),
                $i
            );
        }
        echo '</span></div></div>';
    }
}

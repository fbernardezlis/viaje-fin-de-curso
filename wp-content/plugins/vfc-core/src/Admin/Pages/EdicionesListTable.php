<?php
declare(strict_types=1);

namespace VFC\Core\Admin\Pages;

use VFC\Core\Domain\Centro\CentroRepository;
use VFC\Core\Domain\Edicion\EdicionRepository;
use VFC\Core\Domain\Edicion\EstadoEdicion;

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

final class EdicionesListTable extends \WP_List_Table
{
    private EdicionRepository $repo;
    private CentroRepository $centros;

    public function __construct(EdicionRepository $repo, CentroRepository $centros)
    {
        parent::__construct([
            'singular' => 'edicion',
            'plural' => 'ediciones',
            'ajax' => false,
        ]);
        $this->repo = $repo;
        $this->centros = $centros;
    }

    public function get_columns(): array
    {
        return [
            'nombre' => __('Nombre', 'vfc-core'),
            'centro' => __('Centro', 'vfc-core'),
            'estado' => __('Estado', 'vfc-core'),
            'fechas' => __('Fechas', 'vfc-core'),
            'created_at' => __('Creada', 'vfc-core'),
        ];
    }

    public function get_sortable_columns(): array
    {
        return [
            'nombre' => ['nombre', false],
            'estado' => ['estado', false],
            'created_at' => ['created_at', true],
        ];
    }

    public function prepare_items(): void
    {
        $perPage = 20;
        $page = max(1, (int) ($_GET['paged'] ?? 1));
        $search = isset($_REQUEST['s']) ? sanitize_text_field((string) $_REQUEST['s']) : '';
        $estado = isset($_GET['estado']) ? sanitize_key((string) $_GET['estado']) : '';
        $centroId = isset($_GET['centro_id']) ? (int) $_GET['centro_id'] : 0;
        $orderby = isset($_GET['orderby']) ? sanitize_key((string) $_GET['orderby']) : 'created_at';
        $order = isset($_GET['order']) ? sanitize_key((string) $_GET['order']) : 'desc';

        $args = [
            'per_page' => $perPage,
            'page' => $page,
            'orderby' => $orderby,
            'order' => $order,
        ];
        if ($search !== '') {
            $args['search'] = $search;
        }
        if ($estado !== '') {
            $args['estado'] = $estado;
        }
        if ($centroId > 0) {
            $args['centro_id'] = $centroId;
        }

        $result = $this->repo->list($args);

        $this->_column_headers = [$this->get_columns(), [], $this->get_sortable_columns()];
        $this->items = array_map(static fn($e) => $e->toArray(), $result['items']);

        $this->set_pagination_args([
            'total_items' => $result['total'],
            'per_page' => $perPage,
            'total_pages' => (int) ceil($result['total'] / $perPage),
        ]);
    }

    /** @param array<string, mixed> $item */
    public function column_default($item, $column_name): string
    {
        $value = $item[$column_name] ?? '';
        return $value === null ? '' : esc_html((string) $value);
    }

    /** @param array<string, mixed> $item */
    public function column_nombre($item): string
    {
        $editUrl = add_query_arg(
            ['page' => EdicionesListPage::SLUG, 'action' => 'edit', 'id' => (int) $item['id']],
            admin_url('admin.php')
        );
        $deleteUrl = wp_nonce_url(
            add_query_arg(
                ['page' => EdicionesListPage::SLUG, 'action' => 'delete', 'id' => (int) $item['id']],
                admin_url('admin.php')
            ),
            'vfc_delete_edicion_' . (int) $item['id']
        );

        $actions = [
            'edit' => sprintf('<a href="%s">%s</a>', esc_url($editUrl), esc_html__('Editar', 'vfc-core')),
            'delete' => sprintf(
                '<a href="%s" onclick="return confirm(\'%s\');">%s</a>',
                esc_url($deleteUrl),
                esc_js(__('¿Borrar esta edición?', 'vfc-core')),
                esc_html__('Borrar', 'vfc-core')
            ),
        ];

        return sprintf(
            '<strong><a href="%s">%s</a></strong>%s',
            esc_url($editUrl),
            esc_html((string) ($item['nombre'] ?? '')),
            $this->row_actions($actions)
        );
    }

    /** @param array<string, mixed> $item */
    public function column_centro($item): string
    {
        $centro = $this->centros->find((int) ($item['centro_id'] ?? 0));
        return $centro !== null ? esc_html($centro->nombre) : '—';
    }

    /** @param array<string, mixed> $item */
    public function column_estado($item): string
    {
        $estado = (string) ($item['estado'] ?? '');
        $labels = EstadoEdicion::labels();
        return '<span class="vfc-estado vfc-estado-' . esc_attr($estado) . '">'
            . esc_html($labels[$estado] ?? $estado)
            . '</span>';
    }

    /** @param array<string, mixed> $item */
    public function column_fechas($item): string
    {
        $inicio = $item['fecha_inicio'] ?? null;
        $fin = $item['fecha_fin'] ?? null;
        if ($inicio === null && $fin === null) {
            return '—';
        }
        return sprintf(
            '%s &mdash; %s',
            esc_html((string) ($inicio ?: '?')),
            esc_html((string) ($fin ?: '?'))
        );
    }

    public function no_items(): void
    {
        esc_html_e('Aún no hay ediciones.', 'vfc-core');
    }
}

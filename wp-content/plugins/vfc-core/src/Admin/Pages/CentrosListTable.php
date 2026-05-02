<?php
declare(strict_types=1);

namespace VFC\Core\Admin\Pages;

use VFC\Core\Domain\Centro\CentroRepository;

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

final class CentrosListTable extends \WP_List_Table
{
    private CentroRepository $repo;

    public function __construct(CentroRepository $repo)
    {
        parent::__construct([
            'singular' => 'centro',
            'plural' => 'centros',
            'ajax' => false,
        ]);
        $this->repo = $repo;
    }

    public function get_columns(): array
    {
        return [
            'nombre' => __('Nombre', 'vfc-core'),
            'slug' => __('Slug', 'vfc-core'),
            'cif' => __('CIF', 'vfc-core'),
            'email' => __('Email', 'vfc-core'),
            'estado' => __('Estado', 'vfc-core'),
            'created_at' => __('Creado', 'vfc-core'),
        ];
    }

    public function get_sortable_columns(): array
    {
        return [
            'nombre' => ['nombre', true],
            'slug' => ['slug', false],
            'estado' => ['estado', false],
            'created_at' => ['created_at', false],
        ];
    }

    public function prepare_items(): void
    {
        $perPage = 20;
        $page = max(1, (int) ($_GET['paged'] ?? 1));
        $search = isset($_REQUEST['s']) ? sanitize_text_field((string) $_REQUEST['s']) : '';
        $orderby = isset($_GET['orderby']) ? sanitize_key((string) $_GET['orderby']) : 'nombre';
        $order = isset($_GET['order']) ? sanitize_key((string) $_GET['order']) : 'asc';

        $result = $this->repo->list([
            'search' => $search,
            'per_page' => $perPage,
            'page' => $page,
            'orderby' => $orderby,
            'order' => $order,
        ]);

        $this->_column_headers = [$this->get_columns(), [], $this->get_sortable_columns()];
        $this->items = array_map(static fn($c) => $c->toArray(), $result['items']);

        $this->set_pagination_args([
            'total_items' => $result['total'],
            'per_page' => $perPage,
            'total_pages' => (int) ceil($result['total'] / $perPage),
        ]);
    }

    /**
     * @param array<string, mixed> $item
     */
    public function column_default($item, $column_name): string
    {
        $value = $item[$column_name] ?? '';
        return $value === null ? '' : esc_html((string) $value);
    }

    /**
     * @param array<string, mixed> $item
     */
    public function column_nombre($item): string
    {
        $editUrl = add_query_arg(
            [
                'page' => CentrosListPage::SLUG,
                'action' => 'edit',
                'id' => (int) $item['id'],
            ],
            admin_url('admin.php')
        );

        $deleteUrl = wp_nonce_url(
            add_query_arg(
                [
                    'page' => CentrosListPage::SLUG,
                    'action' => 'delete',
                    'id' => (int) $item['id'],
                ],
                admin_url('admin.php')
            ),
            'vfc_delete_centro_' . (int) $item['id']
        );

        $actions = [
            'edit' => sprintf('<a href="%s">%s</a>', esc_url($editUrl), esc_html__('Editar', 'vfc-core')),
            'delete' => sprintf(
                '<a href="%s" onclick="return confirm(\'%s\');">%s</a>',
                esc_url($deleteUrl),
                esc_js(__('¿Seguro que quieres borrar este centro?', 'vfc-core')),
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

    public function no_items(): void
    {
        esc_html_e('Aún no hay centros. Crea el primero con el botón “Añadir nuevo”.', 'vfc-core');
    }
}

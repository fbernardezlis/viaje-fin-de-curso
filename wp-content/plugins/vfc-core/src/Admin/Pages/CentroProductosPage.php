<?php
declare(strict_types=1);

namespace VFC\Core\Admin\Pages;

use VFC\Core\Domain\Centro\CentroRepository;
use VFC\Core\Domain\CentroProducto\CentroProductoRepository;
use VFC\Core\Roles\Capabilities;

if (!defined('ABSPATH')) {
    exit;
}

final class CentroProductosPage
{
    public const SLUG = 'vfc-centro-productos';
    private const NONCE_FIELD = 'vfc_centro_productos_nonce';
    private const NONCE_ACTION = 'vfc_centro_productos_save';

    private CentroRepository $centros;
    private CentroProductoRepository $repo;

    public function __construct(?CentroRepository $centros = null, ?CentroProductoRepository $repo = null)
    {
        $this->centros = $centros ?? new CentroRepository();
        $this->repo = $repo ?? new CentroProductoRepository();
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
            __('Productos por centro', 'vfc-core'),
            __('Productos por centro', 'vfc-core'),
            Capabilities::MANAGE_CENTRO_PRODUCTOS,
            self::SLUG,
            [$this, 'render']
        );
    }

    public function render(): void
    {
        if (!current_user_can(Capabilities::MANAGE_CENTRO_PRODUCTOS)) {
            wp_die(esc_html__('No tienes permisos.', 'vfc-core'));
        }

        $centroId = isset($_GET['centro_id']) ? (int) $_GET['centro_id'] : 0;
        $centros = $this->centros->list(['per_page' => 200])['items'];

        echo '<div class="wrap"><h1>' . esc_html__('Productos por centro', 'vfc-core') . '</h1>';
        $this->renderNotices();

        echo '<form method="get"><input type="hidden" name="page" value="' . esc_attr(self::SLUG) . '" />';
        echo '<label>' . esc_html__('Centro:', 'vfc-core') . ' ';
        echo '<select name="centro_id" onchange="this.form.submit()">';
        echo '<option value="0">' . esc_html__('— Selecciona un centro —', 'vfc-core') . '</option>';
        foreach ($centros as $centro) {
            printf(
                '<option value="%d" %s>%s</option>',
                (int) $centro->id,
                selected($centroId, (int) $centro->id, false),
                esc_html($centro->nombre)
            );
        }
        echo '</select></label> ';
        submit_button(__('Filtrar', 'vfc-core'), '', '', false);
        echo '</form>';

        if ($centroId === 0) {
            echo '<p>' . esc_html__('Selecciona un centro para gestionar qué productos del catálogo global están disponibles.', 'vfc-core') . '</p></div>';
            return;
        }

        if (!class_exists('WooCommerce')) {
            echo '<div class="notice notice-warning"><p>' . esc_html__('WooCommerce no está activo. Activa WooCommerce para listar productos.', 'vfc-core') . '</p></div></div>';
            return;
        }

        $this->renderProductsForm($centroId);
        echo '</div>';
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
        }
    }

    private function handleSave(): void
    {
        if (!current_user_can(Capabilities::MANAGE_CENTRO_PRODUCTOS)) {
            wp_die(esc_html__('Sin permisos.', 'vfc-core'));
        }
        check_admin_referer(self::NONCE_ACTION, self::NONCE_FIELD);

        $centroId = isset($_POST['centro_id']) ? (int) $_POST['centro_id'] : 0;
        if ($centroId <= 0) {
            $this->redirect([]);
            return;
        }

        $allIds = isset($_POST['all_product_ids']) && is_array($_POST['all_product_ids'])
            ? array_map('intval', $_POST['all_product_ids'])
            : [];
        $activos = isset($_POST['activos']) && is_array($_POST['activos'])
            ? array_map('intval', $_POST['activos'])
            : [];
        $activosSet = array_flip($activos);

        foreach ($allIds as $productId) {
            $this->repo->setEstado($centroId, $productId, isset($activosSet[$productId]));
        }

        $this->redirect(['centro_id' => $centroId, 'vfc_notice' => 'saved']);
    }

    private function renderProductsForm(int $centroId): void
    {
        $url = add_query_arg(['page' => self::SLUG, 'action' => 'save'], admin_url('admin.php'));
        $statusMap = $this->repo->statusMapForCentro($centroId);

        $query = new \WP_Query([
            'post_type' => 'product',
            'posts_per_page' => 200,
            'orderby' => 'title',
            'order' => 'ASC',
            'no_found_rows' => true,
            'post_status' => ['publish', 'draft', 'private'],
        ]);

        if (!$query->have_posts()) {
            echo '<p>' . esc_html__('No hay productos en el catálogo. Crea productos en WooCommerce.', 'vfc-core') . '</p>';
            return;
        }

        echo '<form method="post" action="' . esc_url($url) . '">';
        wp_nonce_field(self::NONCE_ACTION, self::NONCE_FIELD);
        echo '<input type="hidden" name="centro_id" value="' . (int) $centroId . '" />';

        echo '<p>' . esc_html__('Marca los productos disponibles para este centro. Los desmarcados quedarán excluidos.', 'vfc-core') . '</p>';
        echo '<table class="wp-list-table widefat fixed striped"><thead><tr>';
        echo '<th style="width:60px;">' . esc_html__('Activo', 'vfc-core') . '</th>';
        echo '<th>' . esc_html__('Producto', 'vfc-core') . '</th>';
        echo '<th>' . esc_html__('SKU', 'vfc-core') . '</th>';
        echo '<th>' . esc_html__('Precio', 'vfc-core') . '</th>';
        echo '</tr></thead><tbody>';

        while ($query->have_posts()) {
            $query->the_post();
            $productId = (int) get_the_ID();
            $activo = $statusMap[$productId] ?? true;
            $product = function_exists('wc_get_product') ? wc_get_product($productId) : null;

            echo '<tr>';
            echo '<td><input type="hidden" name="all_product_ids[]" value="' . (int) $productId . '" />';
            printf(
                '<input type="checkbox" name="activos[]" value="%d" %s />',
                (int) $productId,
                checked($activo, true, false)
            );
            echo '</td>';
            echo '<td>' . esc_html(get_the_title()) . '</td>';
            echo '<td>' . esc_html($product?->get_sku() ?? '') . '</td>';
            echo '<td>' . wp_kses_post(($product !== null ? $product->get_price_html() : '')) . '</td>';
            echo '</tr>';
        }
        wp_reset_postdata();

        echo '</tbody></table>';
        submit_button(__('Guardar selección', 'vfc-core'));
        echo '</form>';
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
        if (!empty($_GET['vfc_notice']) && (string) $_GET['vfc_notice'] === 'saved') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Selección guardada.', 'vfc-core') . '</p></div>';
        }
    }
}

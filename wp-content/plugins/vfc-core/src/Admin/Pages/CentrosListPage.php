<?php
declare(strict_types=1);

namespace VFC\Core\Admin\Pages;

use VFC\Core\Domain\Centro\Centro;
use VFC\Core\Domain\Centro\CentroRepository;
use VFC\Core\Roles\Capabilities;

if (!defined('ABSPATH')) {
    exit;
}

final class CentrosListPage
{
    public const PARENT_SLUG = 'vfc';
    public const SLUG = 'vfc-centros';
    private const NONCE_FIELD = 'vfc_centros_nonce';
    private const NONCE_ACTION_SAVE = 'vfc_centros_save';

    private CentroRepository $repo;

    public function __construct(?CentroRepository $repo = null)
    {
        $this->repo = $repo ?? new CentroRepository();
    }

    public function register(): void
    {
        add_action('admin_menu', [$this, 'registerMenu']);
        add_action('admin_init', [$this, 'handleActions']);
    }

    public function registerMenu(): void
    {
        add_menu_page(
            __('VFC', 'vfc-core'),
            __('VFC', 'vfc-core'),
            Capabilities::MANAGE_CENTROS,
            self::PARENT_SLUG,
            [$this, 'render'],
            'dashicons-welcome-learn-more',
            56
        );

        add_submenu_page(
            self::PARENT_SLUG,
            __('Centros', 'vfc-core'),
            __('Centros', 'vfc-core'),
            Capabilities::MANAGE_CENTROS,
            self::SLUG,
            [$this, 'render']
        );

        // El primer submenú duplica el parent; lo renombramos para que no aparezca «VFC» dos veces.
        global $submenu;
        if (isset($submenu[self::PARENT_SLUG][0][2]) && $submenu[self::PARENT_SLUG][0][2] === self::PARENT_SLUG) {
            $submenu[self::PARENT_SLUG][0][0] = __('Inicio', 'vfc-core');
        }
    }

    public function render(): void
    {
        if (!current_user_can(Capabilities::MANAGE_CENTROS)) {
            wp_die(esc_html__('No tienes permisos para acceder a esta página.', 'vfc-core'));
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

        if ($action === 'delete') {
            $this->handleDelete();
        }
    }

    private function handleSave(): void
    {
        if (!current_user_can(Capabilities::MANAGE_CENTROS)) {
            wp_die(esc_html__('Sin permisos.', 'vfc-core'));
        }
        check_admin_referer(self::NONCE_ACTION_SAVE, self::NONCE_FIELD);

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $centro = new Centro(
            id: $id > 0 ? $id : null,
            nombre: sanitize_text_field((string) ($_POST['nombre'] ?? '')),
            slug: sanitize_title((string) ($_POST['slug'] ?? '')),
            cif: $this->nullableField($_POST['cif'] ?? null),
            email: $this->nullableEmail($_POST['email'] ?? null),
            telefono: $this->nullableField($_POST['telefono'] ?? null),
            direccion: $this->nullableTextarea($_POST['direccion'] ?? null),
            estado: in_array(($_POST['estado'] ?? 'activo'), ['activo', 'inactivo'], true)
                ? (string) $_POST['estado']
                : 'activo'
        );

        if ($centro->nombre === '') {
            $this->redirectWithNotice(['action' => $id > 0 ? 'edit' : 'new', 'id' => $id, 'vfc_error' => 'nombre']);
            return;
        }

        try {
            $saved = $this->repo->save($centro);
        } catch (\RuntimeException $e) {
            $this->redirectWithNotice([
                'action' => $id > 0 ? 'edit' : 'new',
                'id' => $id,
                'vfc_error' => 'save',
                'vfc_msg' => rawurlencode($e->getMessage()),
            ]);
            return;
        }

        $this->redirectWithNotice([
            'action' => 'edit',
            'id' => (int) $saved->id,
            'vfc_notice' => $id > 0 ? 'updated' : 'created',
        ]);
    }

    private function handleDelete(): void
    {
        if (!current_user_can(Capabilities::MANAGE_CENTROS)) {
            wp_die(esc_html__('Sin permisos.', 'vfc-core'));
        }
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        check_admin_referer('vfc_delete_centro_' . $id);

        $this->repo->delete($id);
        $this->redirectWithNotice(['vfc_notice' => 'deleted']);
    }

    private function renderList(): void
    {
        $table = new CentrosListTable($this->repo);
        $table->prepare_items();

        $newUrl = add_query_arg(
            ['page' => self::SLUG, 'action' => 'new'],
            admin_url('admin.php')
        );

        echo '<div class="wrap">';
        echo '<h1 class="wp-heading-inline">' . esc_html__('Centros', 'vfc-core') . '</h1>';
        echo ' <a href="' . esc_url($newUrl) . '" class="page-title-action">' . esc_html__('Añadir nuevo', 'vfc-core') . '</a>';
        echo '<hr class="wp-header-end">';

        $this->maybeRenderNotice();

        echo '<form method="get">';
        echo '<input type="hidden" name="page" value="' . esc_attr(self::SLUG) . '" />';
        $table->search_box(__('Buscar centros', 'vfc-core'), 'vfc-search');
        $table->display();
        echo '</form>';
        echo '</div>';
    }

    private function renderForm(string $action): void
    {
        $centro = null;
        if ($action === 'edit') {
            $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
            $centro = $this->repo->find($id);
            if ($centro === null) {
                echo '<div class="wrap"><div class="notice notice-error"><p>' . esc_html__('Centro no encontrado.', 'vfc-core') . '</p></div></div>';
                return;
            }
        }

        $title = $centro === null ? __('Nuevo centro', 'vfc-core') : __('Editar centro', 'vfc-core');
        $formUrl = add_query_arg(['page' => self::SLUG, 'action' => 'save'], admin_url('admin.php'));
        $listUrl = add_query_arg(['page' => self::SLUG], admin_url('admin.php'));

        echo '<div class="wrap"><h1>' . esc_html($title) . '</h1>';

        $this->maybeRenderNotice();

        echo '<form method="post" action="' . esc_url($formUrl) . '">';
        wp_nonce_field(self::NONCE_ACTION_SAVE, self::NONCE_FIELD);
        if ($centro !== null) {
            echo '<input type="hidden" name="id" value="' . (int) $centro->id . '" />';
        }
        echo '<table class="form-table" role="presentation"><tbody>';

        $this->row(__('Nombre', 'vfc-core'), 'nombre', $centro?->nombre ?? '', true);
        $this->row(__('Slug', 'vfc-core'), 'slug', $centro?->slug ?? '', false, __('Identificador único en URL. Si lo dejas vacío se generará a partir del nombre.', 'vfc-core'));
        $this->row(__('CIF', 'vfc-core'), 'cif', $centro?->cif ?? '');
        $this->row(__('Email', 'vfc-core'), 'email', $centro?->email ?? '', false, '', 'email');
        $this->row(__('Teléfono', 'vfc-core'), 'telefono', $centro?->telefono ?? '');
        $this->rowTextarea(__('Dirección', 'vfc-core'), 'direccion', $centro?->direccion ?? '');
        $this->rowSelect(__('Estado', 'vfc-core'), 'estado', [
            'activo' => __('Activo', 'vfc-core'),
            'inactivo' => __('Inactivo', 'vfc-core'),
        ], $centro?->estado ?? 'activo');

        echo '</tbody></table>';

        submit_button($centro === null ? __('Crear centro', 'vfc-core') : __('Guardar cambios', 'vfc-core'));
        echo ' <a href="' . esc_url($listUrl) . '" class="button">' . esc_html__('Cancelar', 'vfc-core') . '</a>';
        echo '</form></div>';
    }

    private function row(string $label, string $name, string $value, bool $required = false, string $description = '', string $type = 'text'): void
    {
        echo '<tr><th scope="row"><label for="vfc-' . esc_attr($name) . '">' . esc_html($label) . '</label></th><td>';
        printf(
            '<input type="%s" id="vfc-%s" name="%s" value="%s" class="regular-text" %s />',
            esc_attr($type),
            esc_attr($name),
            esc_attr($name),
            esc_attr($value),
            $required ? 'required' : ''
        );
        if ($description !== '') {
            echo '<p class="description">' . esc_html($description) . '</p>';
        }
        echo '</td></tr>';
    }

    private function rowTextarea(string $label, string $name, string $value): void
    {
        echo '<tr><th scope="row"><label for="vfc-' . esc_attr($name) . '">' . esc_html($label) . '</label></th><td>';
        printf(
            '<textarea id="vfc-%s" name="%s" rows="3" class="large-text">%s</textarea>',
            esc_attr($name),
            esc_attr($name),
            esc_textarea($value)
        );
        echo '</td></tr>';
    }

    /**
     * @param array<string, string> $options
     */
    private function rowSelect(string $label, string $name, array $options, string $current): void
    {
        echo '<tr><th scope="row"><label for="vfc-' . esc_attr($name) . '">' . esc_html($label) . '</label></th><td>';
        echo '<select id="vfc-' . esc_attr($name) . '" name="' . esc_attr($name) . '">';
        foreach ($options as $value => $text) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($value),
                selected($current, $value, false),
                esc_html($text)
            );
        }
        echo '</select></td></tr>';
    }

    /**
     * @param array<string, string|int> $params
     */
    private function redirectWithNotice(array $params): void
    {
        $base = ['page' => self::SLUG];
        $url = add_query_arg(array_merge($base, $params), admin_url('admin.php'));
        wp_safe_redirect($url);
        exit;
    }

    private function maybeRenderNotice(): void
    {
        if (!empty($_GET['vfc_notice'])) {
            $notice = sanitize_key((string) $_GET['vfc_notice']);
            $messages = [
                'created' => __('Centro creado correctamente.', 'vfc-core'),
                'updated' => __('Cambios guardados.', 'vfc-core'),
                'deleted' => __('Centro eliminado.', 'vfc-core'),
            ];
            if (isset($messages[$notice])) {
                printf(
                    '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
                    esc_html($messages[$notice])
                );
            }
        }

        if (!empty($_GET['vfc_error'])) {
            $error = sanitize_key((string) $_GET['vfc_error']);
            $messages = [
                'nombre' => __('El nombre del centro es obligatorio.', 'vfc-core'),
                'save' => isset($_GET['vfc_msg'])
                    ? rawurldecode((string) $_GET['vfc_msg'])
                    : __('No se pudo guardar el centro.', 'vfc-core'),
            ];
            if (isset($messages[$error])) {
                printf(
                    '<div class="notice notice-error is-dismissible"><p>%s</p></div>',
                    esc_html($messages[$error])
                );
            }
        }
    }

    private function nullableField(mixed $value): ?string
    {
        $value = is_string($value) ? sanitize_text_field($value) : '';
        return $value === '' ? null : $value;
    }

    private function nullableEmail(mixed $value): ?string
    {
        if (!is_string($value) || $value === '') {
            return null;
        }
        $email = sanitize_email($value);
        return $email !== '' ? $email : null;
    }

    private function nullableTextarea(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }
        $value = sanitize_textarea_field($value);
        return $value === '' ? null : $value;
    }
}

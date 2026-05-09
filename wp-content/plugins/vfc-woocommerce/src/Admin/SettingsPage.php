<?php
declare(strict_types=1);

namespace VFC\Woo\Admin;

use VFC\Woo\Services\CronService;
use VFC\Woo\Services\PercentageService;

if (!defined('ABSPATH')) {
    exit;
}

final class SettingsPage
{
    public const SLUG = 'vfc-ajustes';
    private const PARENT_SLUG = 'vfc';
    private const NONCE_FIELD = 'vfc_settings_nonce';
    private const NONCE_ACTION = 'vfc_settings_save';

    public function register(): void
    {
        add_action('admin_menu', [$this, 'registerMenu'], 20);
        add_action('admin_init', [$this, 'handleActions']);
    }

    public function registerMenu(): void
    {
        add_submenu_page(
            self::PARENT_SLUG,
            __('Ajustes VFC', 'vfc-woocommerce'),
            __('Ajustes', 'vfc-woocommerce'),
            'manage_options',
            self::SLUG,
            [$this, 'render']
        );
    }

    public function render(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('No tienes permisos.', 'vfc-woocommerce'));
        }

        $defaultPctEmpresa = (string) get_option(PercentageService::OPTION_DEFAULT_PCT_EMPRESA, '0');
        $defaultPctAlumno = (string) get_option(PercentageService::OPTION_DEFAULT_PCT_ALUMNO, '');
        if ($defaultPctAlumno === '') {
            $defaultPctAlumno = (string) get_option(PercentageService::OPTION_DEFAULT, '0');
        }
        $bloqueoDias = (int) get_option(PercentageService::OPTION_BLOQUEO_DIAS, 15);

        $url = add_query_arg(['page' => self::SLUG, 'action' => 'save'], admin_url('admin.php'));
        $cronUrl = wp_nonce_url(
            add_query_arg(['page' => self::SLUG, 'action' => 'run_cron'], admin_url('admin.php')),
            'vfc_run_cron'
        );

        echo '<div class="wrap"><h1>' . esc_html__('Ajustes VFC', 'vfc-woocommerce') . '</h1>';
        $this->renderNotices();

        echo '<form method="post" action="' . esc_url($url) . '">';
        wp_nonce_field(self::NONCE_ACTION, self::NONCE_FIELD);
        echo '<table class="form-table" role="presentation"><tbody>';

        echo '<tr><th><label for="vfc-default-pct-empresa">' . esc_html__('% beneficio empresa global (sobre precio base)', 'vfc-woocommerce') . '</label></th><td>';
        printf(
            '<input type="number" min="0" max="1000" step="0.01" id="vfc-default-pct-empresa" name="default_pct_empresa" value="%s" />',
            esc_attr($defaultPctEmpresa)
        );
        echo '<p class="description">' . esc_html__('Se usa si el producto no define % empresa. Se suma al precio base junto con el % alumno.', 'vfc-woocommerce') . '</p>';
        echo '</td></tr>';

        echo '<tr><th><label for="vfc-default-pct-alumno">' . esc_html__('% beneficio alumno global (sobre precio base)', 'vfc-woocommerce') . '</label></th><td>';
        printf(
            '<input type="number" min="0" max="100" step="0.01" id="vfc-default-pct-alumno" name="default_pct_alumno" value="%s" />',
            esc_attr($defaultPctAlumno)
        );
        echo '<p class="description">' . esc_html__(
            'Abono al saldo por unidad = cantidad × (base × % / 100). Si está vacío se toma el antiguo «Porcentaje global por defecto» (vfc_default_porcentaje) hasta que lo sustituyas.',
            'vfc-woocommerce'
        ) . '</p>';
        echo '</td></tr>';

        echo '<tr><th><label for="vfc-bloqueo-dias">' . esc_html__('Días de bloqueo tras el pedido', 'vfc-woocommerce') . '</label></th><td>';
        printf(
            '<input type="number" min="0" max="365" step="1" id="vfc-bloqueo-dias" name="periodo_bloqueo_dias" value="%d" />',
            $bloqueoDias
        );
        echo '<p class="description">' . esc_html__('Período tras el cual los movimientos pasan de BLOQUEADO a CONFIRMADO automáticamente.', 'vfc-woocommerce') . '</p>';
        echo '</td></tr>';

        echo '</tbody></table>';
        submit_button(__('Guardar ajustes', 'vfc-woocommerce'));
        echo '</form>';

        echo '<hr /><h2>' . esc_html__('Mantenimiento', 'vfc-woocommerce') . '</h2>';
        echo '<p>' . esc_html__('Ejecuta manualmente la liberación de movimientos (BLOQUEADO → CONFIRMADO) sin esperar al cron diario.', 'vfc-woocommerce') . '</p>';
        echo '<p><a class="button" href="' . esc_url($cronUrl) . '">' . esc_html__('Ejecutar liberación ahora', 'vfc-woocommerce') . '</a></p>';
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
        } elseif ($action === 'run_cron') {
            $this->handleRunCron();
        }
    }

    private function handleSave(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Sin permisos.', 'vfc-woocommerce'));
        }
        check_admin_referer(self::NONCE_ACTION, self::NONCE_FIELD);

        $pctEmp = isset($_POST['default_pct_empresa']) ? (string) $_POST['default_pct_empresa'] : '0';
        if (!is_numeric($pctEmp)) {
            $pctEmp = '0';
        }
        $pctEmpF = (float) $pctEmp;
        if ($pctEmpF < 0) {
            $pctEmpF = 0.0;
        } elseif ($pctEmpF > 1000) {
            $pctEmpF = 1000.0;
        }
        update_option(PercentageService::OPTION_DEFAULT_PCT_EMPRESA, $pctEmpF);

        $pctAlu = isset($_POST['default_pct_alumno']) ? (string) $_POST['default_pct_alumno'] : '';
        if ($pctAlu === '' || !is_numeric($pctAlu)) {
            delete_option(PercentageService::OPTION_DEFAULT_PCT_ALUMNO);
        } else {
            $pctAluF = (float) $pctAlu;
            if ($pctAluF < 0) {
                $pctAluF = 0.0;
            } elseif ($pctAluF > 100) {
                $pctAluF = 100.0;
            }
            update_option(PercentageService::OPTION_DEFAULT_PCT_ALUMNO, $pctAluF);
        }

        $dias = isset($_POST['periodo_bloqueo_dias']) ? (int) $_POST['periodo_bloqueo_dias'] : 15;
        if ($dias < 0) {
            $dias = 0;
        } elseif ($dias > 365) {
            $dias = 365;
        }
        update_option(PercentageService::OPTION_BLOQUEO_DIAS, $dias);

        $this->redirect(['vfc_notice' => 'saved']);
    }

    private function handleRunCron(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Sin permisos.', 'vfc-woocommerce'));
        }
        check_admin_referer('vfc_run_cron');
        $count = (new CronService())->liberarBloqueados();
        $this->redirect(['vfc_notice' => 'cron_run', 'count' => $count]);
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
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Ajustes guardados.', 'vfc-woocommerce') . '</p></div>';
        }
        if (!empty($_GET['vfc_notice']) && (string) $_GET['vfc_notice'] === 'cron_run') {
            $count = isset($_GET['count']) ? (int) $_GET['count'] : 0;
            printf(
                '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
                esc_html(sprintf(
                    /* translators: %d: number of movements freed */
                    __('Liberación ejecutada. %d movimientos pasaron a CONFIRMADO.', 'vfc-woocommerce'),
                    $count
                ))
            );
        }
    }
}

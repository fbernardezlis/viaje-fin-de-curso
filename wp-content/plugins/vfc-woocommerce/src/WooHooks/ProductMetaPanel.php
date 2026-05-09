<?php
declare(strict_types=1);

namespace VFC\Woo\WooHooks;

use VFC\Woo\Services\PercentageService;
use VFC\Woo\Services\PricingService;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Metadatos de precio VFC en producto (base + % empresa + % alumno) y en variaciones (precio propio).
 * Al guardar, recalcula el precio regular de WooCommerce según el modelo acordado.
 */
final class ProductMetaPanel
{
    public function register(): void
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminProductPricing']);
        add_action('woocommerce_product_options_general_product_data', [$this, 'renderParentFields']);
        add_action('woocommerce_process_product_meta', [$this, 'saveParent'], 50, 1);

        add_action('woocommerce_variation_options_pricing', [$this, 'renderVariationFields'], 15, 3);
        add_action('woocommerce_save_product_variation', [$this, 'saveVariation'], 15, 2);
    }

    public function renderParentFields(): void
    {
        if (!function_exists('woocommerce_wp_text_input')) {
            return;
        }
        global $post;
        $pid = isset($post->ID) ? (int) $post->ID : 0;
        $baseRaw = $pid > 0 ? get_post_meta($pid, PricingService::META_BASE, true) : '';
        $pctERaw = $pid > 0 ? get_post_meta($pid, PricingService::META_PCT_EMPRESA, true) : '';
        $pctARaw = $pid > 0 ? get_post_meta($pid, PricingService::META_PCT_ALUMNO, true) : '';
        $dec = function_exists('wc_get_price_decimals') ? wc_get_price_decimals() : 2;
        $baseVal = is_numeric($baseRaw) ? wc_format_decimal((float) $baseRaw, $dec) : '';
        $pctEVal = is_numeric($pctERaw) ? (string) (int) round((float) $pctERaw) : '';
        $pctAVal = is_numeric($pctARaw) ? (string) (int) round((float) $pctARaw) : '';

        echo '<div class="options_group vfc_product_options">';
        echo '<h4 style="padding:0 12px;">' . esc_html__('Precio VFC (desde coste base)', 'vfc-woocommerce') . '</h4>';
        woocommerce_wp_text_input([
            'id' => PricingService::META_BASE,
            'name' => PricingService::META_BASE,
            'label' => __('Precio base / coste (sin IVA, €)', 'vfc-woocommerce'),
            'description' => __('Punto de partida para calcular márgenes. Si está vacío no se recalcula el precio de venta automáticamente.', 'vfc-woocommerce'),
            'desc_tip' => true,
            'type' => 'text',
            'data_type' => 'price',
            'value' => $baseVal,
        ]);
        woocommerce_wp_text_input([
            'id' => PricingService::META_PCT_EMPRESA,
            'name' => PricingService::META_PCT_EMPRESA,
            'label' => __('% beneficio empresa (sobre base)', 'vfc-woocommerce'),
            'description' => __('Se suma al precio base. Vacío = valor global en Ajustes VFC.', 'vfc-woocommerce'),
            'desc_tip' => true,
            'type' => 'number',
            'value' => $pctEVal,
            'custom_attributes' => [
                'min' => '0',
                'max' => '1000',
                'step' => '1',
                'class' => 'vfc-pct-input',
            ],
        ]);
        woocommerce_wp_text_input([
            'id' => PricingService::META_PCT_ALUMNO,
            'name' => PricingService::META_PCT_ALUMNO,
            'label' => __('% beneficio alumno / saldo (sobre base)', 'vfc-woocommerce'),
            'description' => __('Importe abonado al saldo = cantidad × (base × % / 100). Vacío = global o meta antigua «Porcentaje VFC» si existe.', 'vfc-woocommerce'),
            'desc_tip' => true,
            'type' => 'number',
            'value' => $pctAVal,
            'custom_attributes' => [
                'min' => '0',
                'max' => '100',
                'step' => '1',
                'class' => 'vfc-pct-input',
            ],
        ]);
        echo '<p class="form-field" style="padding:0 12px;color:#646970;">'
            . esc_html__(
                'Total sin IVA por unidad = base × (1 + %empresa/100 + %alumno/100). El IVA lo aplica WooCommerce al guardar el precio de catálogo según «Impuestos».',
                'vfc-woocommerce'
            )
            . '</p>';

        echo '<div id="vfc-pricing-preview" class="vfc-pricing-preview" style="margin:12px;padding:12px;border:1px solid #c3c4c7;background:#f6f7f7;border-radius:4px;max-width:520px;">';
        echo '<p style="margin:0 0 8px;font-weight:600;">' . esc_html__('Vista previa (se actualiza al cambiar base o %)', 'vfc-woocommerce') . '</p>';
        echo '<p id="vfc-prev-hint" class="description" style="margin:0 0 8px;color:#b32d2e;display:none;"></p>';
        echo '<table class="widefat striped" style="margin:0;"><tbody>';
        echo '<tr><td>' . esc_html__('Precio base (neto)', 'vfc-woocommerce') . '</td><td style="text-align:right;font-weight:600;"><span id="vfc-prev-base">—</span></td></tr>';
        echo '<tr><td><span id="vfc-prev-row-emp-label">' . esc_html__('% empresa → importe', 'vfc-woocommerce') . '</span></td><td style="text-align:right;"><span id="vfc-prev-emp-eur">—</span></td></tr>';
        echo '<tr><td><span id="vfc-prev-row-alu-label">' . esc_html__('% alumno → importe', 'vfc-woocommerce') . '</span></td><td style="text-align:right;"><span id="vfc-prev-alu-eur">—</span></td></tr>';
        echo '<tr><td><strong>' . esc_html__('Total sin IVA / unidad', 'vfc-woocommerce') . '</strong></td><td style="text-align:right;"><strong><span id="vfc-prev-net-total">—</span></strong></td></tr>';
        echo '<tr><td>' . esc_html__('Referencia con IVA / unidad (informativo)', 'vfc-woocommerce') . '</td><td style="text-align:right;"><span id="vfc-prev-gross-total">—</span></td></tr>';
        echo '</tbody></table>';
        echo '<p class="description" style="margin:8px 0 0;">' . esc_html__(
            'Los % en pantalla se muestran como enteros; el importe con IVA usa la clase de impuesto del producto y la base imponible del sitio (puede variar si cambias la clase de impuesto y recargas).',
            'vfc-woocommerce'
        ) . '</p>';
        echo '</div>';

        echo '</div>';
    }

    public function enqueueAdminProductPricing(string $hook): void
    {
        if ($hook !== 'post.php' && $hook !== 'post-new.php') {
            return;
        }
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if ($screen === null || $screen->post_type !== 'product') {
            return;
        }

        $percent = new PercentageService();
        $productId = isset($_GET['post']) ? (int) $_GET['post'] : 0;
        $taxClass = '';
        if ($productId > 0) {
            $taxClass = (string) get_post_meta($productId, '_tax_class', true);
        }

        $multiplier = 1.0;
        if (function_exists('wc_tax_enabled') && wc_tax_enabled() && class_exists('\WC_Tax')) {
            $rates = \WC_Tax::get_base_tax_rates($taxClass);
            if ($rates !== []) {
                $netProbe = 100.0;
                $taxes = \WC_Tax::calc_tax($netProbe, $rates, false);
                $multiplier = ($netProbe + (float) array_sum($taxes)) / $netProbe;
            }
        }

        wp_enqueue_script(
            'vfc-product-pricing-preview',
            VFC_WOO_URL . 'assets/js/vfc-product-pricing-preview.js',
            ['jquery'],
            VFC_WOO_VERSION,
            true
        );
        wp_localize_script('vfc-product-pricing-preview', 'VFC_PRODUCT_PRICING', [
            'defaults' => [
                'pctEmpresa' => $percent->defaultPctEmpresa(),
                'pctAlumno' => $percent->defaultPctAlumno(),
            ],
            'decimals' => function_exists('wc_get_price_decimals') ? wc_get_price_decimals() : 2,
            'grossMultiplier' => round($multiplier, 6),
            'currency' => function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : '€',
            'i18n' => [
                'dash' => '—',
                'globalPct' => __('(global: {{n}}%)', 'vfc-woocommerce'),
                'productPct' => __('({{n}}%)', 'vfc-woocommerce'),
                'invalidBase' => __('Indica un precio base numérico para ver la vista previa.', 'vfc-woocommerce'),
                'empRow' => __('% empresa', 'vfc-woocommerce'),
                'aluRow' => __('% alumno', 'vfc-woocommerce'),
                'arrowImporte' => __('→ importe', 'vfc-woocommerce'),
            ],
        ]);
    }

    public function saveParent(int $postId): void
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!isset($_POST['post_type']) || (string) $_POST['post_type'] !== 'product') {
            return;
        }
        if (!current_user_can('edit_post', $postId)) {
            return;
        }

        $this->persistFloatMeta($postId, PricingService::META_BASE, PricingService::META_BASE, true);
        $this->persistFloatMeta($postId, PricingService::META_PCT_EMPRESA, PricingService::META_PCT_EMPRESA, true);
        $this->persistFloatMeta($postId, PricingService::META_PCT_ALUMNO, PricingService::META_PCT_ALUMNO, true);

        $product = wc_get_product($postId);
        if (!$product instanceof \WC_Product) {
            return;
        }
        self::syncProductTree($product);
    }

    /**
     * @param \WP_Post $variation
     */
    public function renderVariationFields(int $loop, array $variation_data, \WP_Post $variation): void
    {
        $vid = (int) $variation->ID;
        $own = get_post_meta($vid, PricingService::META_PRECIO_PROPIO, true) === 'yes';
        $base = get_post_meta($vid, PricingService::META_BASE, true);
        $pctE = get_post_meta($vid, PricingService::META_PCT_EMPRESA, true);
        $pctA = get_post_meta($vid, PricingService::META_PCT_ALUMNO, true);

        echo '<div class="form-row form-row-full vfc-variation-pricing">';
        echo '<label><input type="checkbox" class="checkbox" name="variable_vfc_precio_propio[' . esc_attr((string) $loop) . ']" value="yes" '
            . checked($own, true, false) . ' /> '
            . esc_html__('Usar precio propio VFC (si no, se usa el padre al guardar)', 'vfc-woocommerce')
            . '</label>';
        echo '<p class="description">' . esc_html__(
            'Si está marcado, puede definir base y/o % en esta variación; los vacíos heredan del producto padre.',
            'vfc-woocommerce'
        ) . '</p>';
        echo '<p class="form-field form-field-first">';
        echo '<label>' . esc_html__('Base variación (€)', 'vfc-woocommerce');
        printf(
            '<input type="text" name="variable_vfc_precio_base[%s]" value="%s" class="short wc_input_price" placeholder="%s" /></label></p>',
            esc_attr((string) $loop),
            esc_attr(is_scalar($base) ? (string) $base : ''),
            esc_attr__('Opcional', 'vfc-woocommerce')
        );
        echo '<p class="form-field">';
        echo '<label>' . esc_html__('% empresa (variación)', 'vfc-woocommerce');
        printf(
            '<input type="number" step="0.01" min="0" max="1000" name="variable_vfc_pct_empresa[%s]" value="%s" class="short" /></label></p>',
            esc_attr((string) $loop),
            esc_attr(is_scalar($pctE) ? (string) $pctE : '')
        );
        echo '<p class="form-field">';
        echo '<label>' . esc_html__('% alumno (variación)', 'vfc-woocommerce');
        printf(
            '<input type="number" step="0.01" min="0" max="100" name="variable_vfc_pct_alumno[%s]" value="%s" class="short" /></label></p>',
            esc_attr((string) $loop),
            esc_attr(is_scalar($pctA) ? (string) $pctA : '')
        );
        echo '</div>';
    }

    public function saveVariation(int $variation_id, int $loop): void
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $variation_id)) {
            return;
        }

        $own = $this->postVariableCheckbox('variable_vfc_precio_propio', $loop);
        update_post_meta($variation_id, PricingService::META_PRECIO_PROPIO, $own ? 'yes' : 'no');

        $this->persistIndexedOptional($this->postVariableField('variable_vfc_precio_base', $loop), $variation_id, PricingService::META_BASE, true);
        $this->persistIndexedOptional($this->postVariableField('variable_vfc_pct_empresa', $loop), $variation_id, PricingService::META_PCT_EMPRESA, false);
        $this->persistIndexedOptional($this->postVariableField('variable_vfc_pct_alumno', $loop), $variation_id, PricingService::META_PCT_ALUMNO, false);

        $v = wc_get_product($variation_id);
        if ($v instanceof \WC_Product) {
            if (PricingService::syncRegularPrice($v)) {
                $v->save();
            }
        }
    }

    /**
     * Sincroniza precios: simple / variación directa, o todas las variaciones del padre.
     */
    public static function syncProductTree(\WC_Product $product): void
    {
        if ($product->is_type('simple')) {
            if (PricingService::syncRegularPrice($product)) {
                $product->save();
            }

            return;
        }

        if ($product->is_type('variable')) {
            foreach ($product->get_children() as $childId) {
                $v = wc_get_product((int) $childId);
                if ($v instanceof \WC_Product && PricingService::syncRegularPrice($v)) {
                    $v->save();
                }
            }
            wc_delete_product_transients($product->get_id());

            return;
        }

        if ($product->is_type('variation') && PricingService::syncRegularPrice($product)) {
            $product->save();
        }
    }

    private function postVariableCheckbox(string $field, int $loop): bool
    {
        if (!isset($_POST[$field]) || !is_array($_POST[$field])) {
            return false;
        }

        return !empty($_POST[$field][$loop]);
    }

    /**
     * @return mixed|null null si no viene en el POST
     */
    private function postVariableField(string $field, int $loop)
    {
        if (!isset($_POST[$field]) || !is_array($_POST[$field])) {
            return null;
        }
        if (!array_key_exists($loop, $_POST[$field])) {
            return null;
        }

        return $_POST[$field][$loop];
    }

    private function persistFloatMeta(int $postId, string $postKey, string $metaKey, bool $allowDeleteEmpty): void
    {
        if (!isset($_POST[$postKey])) {
            return;
        }
        $raw = wp_unslash((string) $_POST[$postKey]);
        $raw = trim($raw);
        if ($raw === '') {
            if ($allowDeleteEmpty) {
                delete_post_meta($postId, $metaKey);
            }

            return;
        }
        if (!is_numeric($raw)) {
            return;
        }
        $val = (float) wc_format_decimal($raw);
        if ($metaKey === PricingService::META_BASE && $val <= 0) {
            delete_post_meta($postId, $metaKey);

            return;
        }
        update_post_meta($postId, $metaKey, $val);
    }

    /**
     * @param mixed $raw
     */
    private function persistIndexedOptional($raw, int $postId, string $metaKey, bool $isBase): void
    {
        if ($raw === null) {
            return;
        }
        $s = trim(wp_unslash((string) $raw));
        if ($s === '') {
            delete_post_meta($postId, $metaKey);

            return;
        }
        if (!is_numeric($s)) {
            return;
        }
        $val = (float) wc_format_decimal($s);
        if ($isBase && $val <= 0) {
            delete_post_meta($postId, $metaKey);

            return;
        }
        update_post_meta($postId, $metaKey, $val);
    }
}

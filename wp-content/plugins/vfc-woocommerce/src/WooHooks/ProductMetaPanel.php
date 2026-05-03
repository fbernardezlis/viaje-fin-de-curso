<?php
declare(strict_types=1);

namespace VFC\Woo\WooHooks;

use VFC\Woo\Services\PercentageService;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Anade el campo "Porcentaje VFC (%)" en la pestana general del editor de producto.
 */
final class ProductMetaPanel
{
    public function register(): void
    {
        add_action('woocommerce_product_options_general_product_data', [$this, 'render']);
        add_action('woocommerce_process_product_meta', [$this, 'save']);
    }

    public function render(): void
    {
        if (!function_exists('woocommerce_wp_text_input')) {
            return;
        }
        echo '<div class="options_group vfc_product_options">';
        woocommerce_wp_text_input([
            'id' => PercentageService::META_PRODUCT,
            'label' => __('Porcentaje VFC (%)', 'vfc-woocommerce'),
            'description' => __('Porcentaje del subtotal sin IVA que se abona al saldo del alumno. Si se deja vacío se aplica el porcentaje global por defecto.', 'vfc-woocommerce'),
            'desc_tip' => true,
            'type' => 'number',
            'custom_attributes' => [
                'min' => '0',
                'max' => '100',
                'step' => '0.01',
            ],
        ]);
        echo '</div>';
    }

    public function save(int $postId): void
    {
        if (!isset($_POST[PercentageService::META_PRODUCT])) {
            return;
        }
        $raw = wp_unslash((string) $_POST[PercentageService::META_PRODUCT]);
        if ($raw === '') {
            delete_post_meta($postId, PercentageService::META_PRODUCT);
            return;
        }
        if (!is_numeric($raw)) {
            return;
        }
        $val = (float) $raw;
        if ($val < 0) {
            $val = 0.0;
        } elseif ($val > 100) {
            $val = 100.0;
        }
        update_post_meta($postId, PercentageService::META_PRODUCT, $val);
    }
}

<?php
declare(strict_types=1);

namespace VFC\Woo\Services;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Precio base (coste) + % empresa + % alumno sobre el mismo base → total sin IVA por unidad.
 * El IVA lo aplica WooCommerce al fijar el precio de catálogo según ajustes de la tienda.
 */
final class PricingService
{
    public const META_BASE = '_vfc_precio_base';
    public const META_PCT_EMPRESA = '_vfc_pct_empresa';
    public const META_PCT_ALUMNO = '_vfc_pct_alumno';
    /** Solo variaciones: si es "yes", se usan metas de la variación (base/% opcionales) en lugar del padre. */
    public const META_PRECIO_PROPIO = '_vfc_precio_propio';

    /** Metadatos en línea de pedido (trazabilidad). */
    public const LINE_META_BASE_UNIT = '_vfc_precio_base_unit';
    public const LINE_META_PCT_EMPRESA = '_vfc_pct_empresa_aplicado';
    public const LINE_META_PCT_ALUMNO = '_vfc_pct_alumno_aplicado';
    public const LINE_META_EMPRESA_UNIT = '_vfc_importe_empresa_sin_iva_unit';
    public const LINE_META_ALUMNO_UNIT = '_vfc_importe_alumno_sin_iva_unit';
    public const LINE_META_TOTAL_SIN_IVA_UNIT = '_vfc_total_sin_iva_unit';
    public const LINE_META_EMPRESA_LINE = '_vfc_importe_empresa_sin_iva_line';
    public const LINE_META_ALUMNO_LINE = '_vfc_importe_alumno_sin_iva_line';
    public const LINE_META_TOTAL_SIN_IVA_LINE = '_vfc_total_sin_iva_line';

    /** @deprecated Meta antigua: se usa como fallback de % alumno sobre base si no hay META_PCT_ALUMNO. */
    public const LEGACY_META_PCT_SUBTOTAL = '_vfc_porcentaje';

    /**
     * @return array{
     *   base: float,
     *   pct_empresa: float,
     *   pct_alumno: float,
     *   importe_empresa: float,
     *   importe_alumno: float,
     *   total_sin_iva: float
     * }
     */
    public static function breakdownUnit(float $base, float $pctEmpresa, float $pctAlumno): array
    {
        $pctEmpresa = max(0.0, min(1000.0, $pctEmpresa));
        $pctAlumno = max(0.0, min(1000.0, $pctAlumno));
        $importeEmpresa = round($base * $pctEmpresa / 100, 4);
        $importeAlumno = round($base * $pctAlumno / 100, 4);
        $totalSinIva = round($base + $importeEmpresa + $importeAlumno, 4);

        return [
            'base' => round($base, 4),
            'pct_empresa' => $pctEmpresa,
            'pct_alumno' => $pctAlumno,
            'importe_empresa' => $importeEmpresa,
            'importe_alumno' => $importeAlumno,
            'total_sin_iva' => $totalSinIva,
        ];
    }

    /**
     * Resuelve base y porcentajes efectivos para un producto simple o variación.
     *
     * @return array{base: float, pct_empresa: float, pct_alumno: float}|null null si no hay base > 0
     */
    public static function effectiveBaseAndPcts(\WC_Product $product): ?array
    {
        $percent = new PercentageService();

        if ($product->is_type('variation')) {
            $parentId = (int) $product->get_parent_id();
            if ($parentId <= 0) {
                return null;
            }
            $varId = $product->get_id();
            $own = get_post_meta($varId, self::META_PRECIO_PROPIO, true) === 'yes';

            if ($own) {
                $base = self::floatOrNull($varId, self::META_BASE) ?? self::floatOrNull($parentId, self::META_BASE);
            } else {
                $base = self::floatOrNull($parentId, self::META_BASE);
            }

            if ($base === null || $base <= 0) {
                return null;
            }

            if ($own) {
                $pctEmp = self::pctOrNull($varId, self::META_PCT_EMPRESA) ?? self::pctOrNull($parentId, self::META_PCT_EMPRESA);
                $pctAlu = self::pctOrNull($varId, self::META_PCT_ALUMNO) ?? self::pctOrNull($parentId, self::META_PCT_ALUMNO);
            } else {
                $pctEmp = self::pctOrNull($parentId, self::META_PCT_EMPRESA);
                $pctAlu = self::pctOrNull($parentId, self::META_PCT_ALUMNO);
            }

            $pctEmp = $pctEmp ?? $percent->defaultPctEmpresa();
            $pctAlu = $pctAlu ?? self::legacyPctAlumno($varId) ?? self::legacyPctAlumno($parentId) ?? $percent->defaultPctAlumno();
        } else {
            $postId = $product->get_id();
            $base = self::floatOrNull($postId, self::META_BASE);
            if ($base === null || $base <= 0) {
                return null;
            }
            $pctEmp = self::pctOrNull($postId, self::META_PCT_EMPRESA) ?? $percent->defaultPctEmpresa();
            $pctAlu = $percent->pctAlumnoOnBaseForPost($postId);
        }

        return [
            'base' => $base,
            'pct_empresa' => max(0.0, min(1000.0, $pctEmp)),
            'pct_alumno' => max(0.0, min(1000.0, $pctAlu)),
        ];
    }

    /**
     * Fija el precio regular de catálogo a partir del total sin IVA por unidad (IVA según WooCommerce).
     */
    public static function syncRegularPrice(\WC_Product $product): bool
    {
        $cfg = self::effectiveBaseAndPcts($product);
        if ($cfg === null) {
            return false;
        }
        $b = self::breakdownUnit($cfg['base'], $cfg['pct_empresa'], $cfg['pct_alumno']);
        $netUnit = $b['total_sin_iva'];
        $stored = self::wcNetTotalToCatalogPriceString($product, $netUnit);
        if ($stored === null) {
            return false;
        }
        $product->set_regular_price($stored);
        return true;
    }

    /**
     * Adjunta metadatos de desglose a una línea de pedido (idempotente si ya existen datos VFC).
     */
    public static function attachBreakdownToLineItem(\WC_Order_Item_Product $item, \WC_Product $product): void
    {
        $existing = $item->get_meta(self::LINE_META_BASE_UNIT, true);
        if ($existing !== '' && $existing !== false && is_numeric($existing)) {
            return;
        }

        $cfg = self::effectiveBaseAndPcts($product);
        if ($cfg === null || $cfg['base'] <= 0) {
            return;
        }

        $b = self::breakdownUnit($cfg['base'], $cfg['pct_empresa'], $cfg['pct_alumno']);
        $qty = max(1, (int) $item->get_quantity());

        $item->add_meta_data(self::LINE_META_BASE_UNIT, $b['base'], true);
        $item->add_meta_data(self::LINE_META_PCT_EMPRESA, $b['pct_empresa'], true);
        $item->add_meta_data(self::LINE_META_PCT_ALUMNO, $b['pct_alumno'], true);
        $item->add_meta_data(self::LINE_META_EMPRESA_UNIT, $b['importe_empresa'], true);
        $item->add_meta_data(self::LINE_META_ALUMNO_UNIT, $b['importe_alumno'], true);
        $item->add_meta_data(self::LINE_META_TOTAL_SIN_IVA_UNIT, $b['total_sin_iva'], true);
        $item->add_meta_data(self::LINE_META_EMPRESA_LINE, round($b['importe_empresa'] * $qty, 4), true);
        $item->add_meta_data(self::LINE_META_ALUMNO_LINE, round($b['importe_alumno'] * $qty, 4), true);
        $item->add_meta_data(self::LINE_META_TOTAL_SIN_IVA_LINE, round($b['total_sin_iva'] * $qty, 4), true);
    }

    private static function floatOrNull(int $postId, string $key): ?float
    {
        $raw = get_post_meta($postId, $key, true);
        if ($raw === '' || $raw === false || !is_numeric($raw)) {
            return null;
        }
        $v = (float) $raw;

        return $v > 0 ? $v : null;
    }

    /**
     * @return float|null null si no hay meta numérica
     */
    private static function pctOrNull(int $postId, string $key): ?float
    {
        $raw = get_post_meta($postId, $key, true);
        if ($raw === '' || $raw === false || !is_numeric($raw)) {
            return null;
        }

        return max(0.0, min(1000.0, (float) $raw));
    }

    private static function legacyPctAlumno(int $postId): ?float
    {
        $raw = get_post_meta($postId, self::LEGACY_META_PCT_SUBTOTAL, true);
        if ($raw === '' || $raw === false || !is_numeric($raw)) {
            return null;
        }

        return max(0.0, min(100.0, (float) $raw));
    }

    /**
     * Convierte total sin IVA por unidad al string que WooCommerce guarda en _regular_price.
     */
    private static function wcNetTotalToCatalogPriceString(\WC_Product $product, float $netTotalSinIvaUnit): ?string
    {
        if ($netTotalSinIvaUnit <= 0) {
            return null;
        }
        $decimals = wc_get_price_decimals();

        if (!function_exists('wc_tax_enabled') || !wc_tax_enabled()) {
            return wc_format_decimal($netTotalSinIvaUnit, $decimals);
        }

        $pricesIncludeTax = get_option('woocommerce_prices_include_tax') === 'yes';
        if (!$pricesIncludeTax) {
            return wc_format_decimal($netTotalSinIvaUnit, $decimals);
        }

        $taxClass = $product->get_tax_class();
        $rates = \WC_Tax::get_base_tax_rates($taxClass);
        if ($rates === []) {
            return wc_format_decimal($netTotalSinIvaUnit, $decimals);
        }

        $taxes = \WC_Tax::calc_tax($netTotalSinIvaUnit, $rates, false);
        $gross = $netTotalSinIvaUnit + (float) array_sum($taxes);

        return wc_format_decimal($gross, $decimals);
    }
}

<?php
declare(strict_types=1);

namespace VFC\Woo\Services;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * - Porcentaje global / por producto **sobre el subtotal sin IVA** (modelo clásico VFC): `forProduct()`.
 * - Porcentajes **sobre precio base** (empresa / alumno) para el nuevo modelo: `defaultPctEmpresa`, `defaultPctAlumno`, `pctAlumnoOnBaseForPost()`.
 */
final class PercentageService
{
    public const META_PRODUCT = '_vfc_porcentaje';
    public const OPTION_DEFAULT = 'vfc_default_porcentaje';
    public const OPTION_DEFAULT_PCT_EMPRESA = 'vfc_default_pct_empresa';
    public const OPTION_DEFAULT_PCT_ALUMNO = 'vfc_default_pct_alumno';
    public const OPTION_BLOQUEO_DIAS = 'vfc_periodo_bloqueo_dias';

    /**
     * % del subtotal sin IVA abonado al saldo (producto `_vfc_porcentaje` o global `vfc_default_porcentaje`).
     */
    public function forProduct(int $productId): float
    {
        if ($productId > 0) {
            $raw = get_post_meta($productId, self::META_PRODUCT, true);
            if ($raw !== '' && $raw !== false && is_numeric($raw)) {
                return self::clampPct((float) $raw);
            }
        }

        return $this->defaultPercentage();
    }

    public function defaultPercentage(): float
    {
        $raw = get_option(self::OPTION_DEFAULT, 0);
        if (!is_numeric($raw)) {
            return 0.0;
        }

        return self::clampPct((float) $raw);
    }

    /**
     * % alumno sobre **precio base** para un post (variación o simple): meta `_vfc_pct_alumno` o legacy `_vfc_porcentaje` o global.
     */
    public function pctAlumnoOnBaseForPost(int $postId): float
    {
        $raw = get_post_meta($postId, PricingService::META_PCT_ALUMNO, true);
        if ($raw !== '' && $raw !== false && is_numeric($raw)) {
            return self::clampPctWide((float) $raw);
        }
        $legacy = get_post_meta($postId, self::META_PRODUCT, true);
        if ($legacy !== '' && $legacy !== false && is_numeric($legacy)) {
            return self::clampPctWide((float) $legacy);
        }

        return $this->defaultPctAlumno();
    }

    public function defaultPctEmpresa(): float
    {
        $raw = get_option(self::OPTION_DEFAULT_PCT_EMPRESA, 0);
        if (!is_numeric($raw)) {
            return 0.0;
        }

        return self::clampPctWide((float) $raw);
    }

    public function defaultPctAlumno(): float
    {
        $raw = get_option(self::OPTION_DEFAULT_PCT_ALUMNO, null);
        if ($raw !== null && $raw !== '' && is_numeric($raw)) {
            return self::clampPctWide((float) $raw);
        }
        $legacy = get_option(self::OPTION_DEFAULT, 0);
        if (is_numeric($legacy)) {
            return self::clampPctWide((float) $legacy);
        }

        return 0.0;
    }

    public function bloqueoDias(): int
    {
        $raw = get_option(self::OPTION_BLOQUEO_DIAS, 15);
        $val = is_numeric($raw) ? (int) $raw : 15;

        return max(0, $val);
    }

    private static function clampPct(float $value): float
    {
        if ($value < 0) {
            return 0.0;
        }
        if ($value > 100) {
            return 100.0;
        }

        return $value;
    }

    private static function clampPctWide(float $value): float
    {
        if ($value < 0) {
            return 0.0;
        }
        if ($value > 1000) {
            return 1000.0;
        }

        return $value;
    }
}

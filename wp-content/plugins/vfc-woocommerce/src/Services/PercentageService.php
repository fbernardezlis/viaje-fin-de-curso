<?php
declare(strict_types=1);

namespace VFC\Woo\Services;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Resuelve el porcentaje del subtotal sin IVA que se abona al saldo del alumno.
 * Override por producto (`_vfc_porcentaje`) sobre el global (`vfc_default_porcentaje`).
 */
final class PercentageService
{
    public const META_PRODUCT = '_vfc_porcentaje';
    public const OPTION_DEFAULT = 'vfc_default_porcentaje';
    public const OPTION_BLOQUEO_DIAS = 'vfc_periodo_bloqueo_dias';

    public function forProduct(int $productId): float
    {
        if ($productId > 0) {
            $raw = get_post_meta($productId, self::META_PRODUCT, true);
            if ($raw !== '' && $raw !== false && is_numeric($raw)) {
                return self::clamp((float) $raw);
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
        return self::clamp((float) $raw);
    }

    public function bloqueoDias(): int
    {
        $raw = get_option(self::OPTION_BLOQUEO_DIAS, 15);
        $val = is_numeric($raw) ? (int) $raw : 15;
        return max(0, $val);
    }

    private static function clamp(float $value): float
    {
        if ($value < 0) {
            return 0.0;
        }
        if ($value > 100) {
            return 100.0;
        }
        return $value;
    }
}

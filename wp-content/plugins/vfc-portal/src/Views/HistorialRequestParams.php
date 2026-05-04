<?php
declare(strict_types=1);

namespace VFC\Portal\Views;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Lee y serializa filtros GET del historial de movimientos (portal alumno/tutor).
 * Claves en URL: hf_edicion, hf_estado, hf_tipo, hf_from, hf_to (evitan colisión con `page`).
 */
final class HistorialRequestParams
{
    public const Q_EDICION = 'hf_edicion';
    public const Q_ESTADO = 'hf_estado';
    public const Q_TIPO = 'hf_tipo';
    public const Q_FROM = 'hf_from';
    public const Q_TO = 'hf_to';

    /**
     * Argumentos válidos para {@see \VFC\Core\Domain\Saldo\SaldoRepository::historial()}.
     *
     * @return array{edicion_id?: int, estado?: string, tipo?: string, from?: string, to?: string}
     */
    public static function fromRequest(): array
    {
        $out = [];

        $edicionId = (int) ($_GET[self::Q_EDICION] ?? 0);
        if ($edicionId > 0) {
            $out['edicion_id'] = $edicionId;
        }

        $estado = strtoupper(sanitize_text_field((string) ($_GET[self::Q_ESTADO] ?? '')));
        if (in_array($estado, ['BLOQUEADO', 'CONFIRMADO', 'REVERTIDO'], true)) {
            $out['estado'] = $estado;
        }

        $tipo = strtolower(sanitize_text_field((string) ($_GET[self::Q_TIPO] ?? '')));
        if (in_array($tipo, ['abono', 'reverso'], true)) {
            $out['tipo'] = $tipo;
        }

        $from = self::normalizeDateStart((string) ($_GET[self::Q_FROM] ?? ''));
        if ($from !== null) {
            $out['from'] = $from;
        }
        $to = self::normalizeDateEnd((string) ($_GET[self::Q_TO] ?? ''));
        if ($to !== null) {
            $out['to'] = $to;
        }

        return $out;
    }

    /**
     * @param array{edicion_id?: int, estado?: string, tipo?: string, from?: string, to?: string} $filters
     * @return array<string, int|string>
     */
    public static function preservationQuery(array $filters): array
    {
        $q = [];
        if (!empty($filters['edicion_id'])) {
            $q[self::Q_EDICION] = (int) $filters['edicion_id'];
        }
        if (!empty($filters['estado'])) {
            $q[self::Q_ESTADO] = (string) $filters['estado'];
        }
        if (!empty($filters['tipo'])) {
            $q[self::Q_TIPO] = (string) $filters['tipo'];
        }
        if (!empty($filters['from'])) {
            $q[self::Q_FROM] = self::datePart((string) $filters['from']);
        }
        if (!empty($filters['to'])) {
            // Guardar solo la parte fecha en URL para inputs type=date
            $q[self::Q_TO] = self::datePart((string) $filters['to']);
        }

        return $q;
    }

    private static function datePart(string $datetime): string
    {
        if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $datetime, $m)) {
            return $m[1];
        }

        return $datetime;
    }

    private static function normalizeDateStart(string $raw): ?string
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            return null;
        }
        $parts = array_map('intval', explode('-', $raw));
        if (count($parts) !== 3 || !checkdate($parts[1], $parts[2], $parts[0])) {
            return null;
        }

        return sprintf('%04d-%02d-%02d 00:00:00', $parts[0], $parts[1], $parts[2]);
    }

    private static function normalizeDateEnd(string $raw): ?string
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            return null;
        }
        $parts = array_map('intval', explode('-', $raw));
        if (count($parts) !== 3 || !checkdate($parts[1], $parts[2], $parts[0])) {
            return null;
        }

        return sprintf('%04d-%02d-%02d 23:59:59', $parts[0], $parts[1], $parts[2]);
    }
}

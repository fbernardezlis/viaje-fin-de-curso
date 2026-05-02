<?php
declare(strict_types=1);

namespace VFC\Core\Domain\Edicion;

if (!defined('ABSPATH')) {
    exit;
}

final class EstadoEdicion
{
    public const BORRADOR = 'borrador';
    public const PENDIENTE = 'pendiente';
    public const APROBADA = 'aprobada';
    public const RECHAZADA = 'rechazada';
    public const ACTIVA = 'activa';
    public const CERRADA = 'cerrada';

    private const TRANSICIONES = [
        self::BORRADOR => [self::PENDIENTE],
        self::PENDIENTE => [self::APROBADA, self::RECHAZADA],
        self::APROBADA => [self::ACTIVA, self::CERRADA],
        self::RECHAZADA => [self::BORRADOR],
        self::ACTIVA => [self::CERRADA],
        self::CERRADA => [],
    ];

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return [
            self::BORRADOR,
            self::PENDIENTE,
            self::APROBADA,
            self::RECHAZADA,
            self::ACTIVA,
            self::CERRADA,
        ];
    }

    /**
     * Etiquetas localizadas para mostrar.
     *
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::BORRADOR => __('Borrador', 'vfc-core'),
            self::PENDIENTE => __('Pendiente de aprobación', 'vfc-core'),
            self::APROBADA => __('Aprobada', 'vfc-core'),
            self::RECHAZADA => __('Rechazada', 'vfc-core'),
            self::ACTIVA => __('Activa', 'vfc-core'),
            self::CERRADA => __('Cerrada', 'vfc-core'),
        ];
    }

    public static function isValid(string $estado): bool
    {
        return in_array($estado, self::all(), true);
    }

    public static function canTransition(string $from, string $to): bool
    {
        return self::isValid($from)
            && self::isValid($to)
            && in_array($to, self::TRANSICIONES[$from] ?? [], true);
    }

    /**
     * @return array<int, string>
     */
    public static function nextStates(string $from): array
    {
        return self::TRANSICIONES[$from] ?? [];
    }
}

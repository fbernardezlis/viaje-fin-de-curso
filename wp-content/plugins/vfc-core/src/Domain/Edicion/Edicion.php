<?php
declare(strict_types=1);

namespace VFC\Core\Domain\Edicion;

if (!defined('ABSPATH')) {
    exit;
}

final class Edicion
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $centroId,
        public readonly string $nombre,
        public readonly ?string $fechaInicio,
        public readonly ?string $fechaFin,
        public readonly string $estado,
        public readonly ?int $aprobadaPor = null,
        public readonly ?string $fechaAprobacion = null,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: isset($row['id']) ? (int) $row['id'] : null,
            centroId: (int) ($row['centro_id'] ?? 0),
            nombre: (string) ($row['nombre'] ?? ''),
            fechaInicio: self::nullable($row['fecha_inicio'] ?? null),
            fechaFin: self::nullable($row['fecha_fin'] ?? null),
            estado: (string) ($row['estado'] ?? EstadoEdicion::BORRADOR),
            aprobadaPor: isset($row['aprobada_por']) && $row['aprobada_por'] !== null
                ? (int) $row['aprobada_por']
                : null,
            fechaAprobacion: self::nullable($row['fecha_aprobacion'] ?? null),
            createdAt: self::nullable($row['created_at'] ?? null),
            updatedAt: self::nullable($row['updated_at'] ?? null),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'centro_id' => $this->centroId,
            'nombre' => $this->nombre,
            'fecha_inicio' => $this->fechaInicio,
            'fecha_fin' => $this->fechaFin,
            'estado' => $this->estado,
            'aprobada_por' => $this->aprobadaPor,
            'fecha_aprobacion' => $this->fechaAprobacion,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    private static function nullable(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        return (string) $value;
    }
}

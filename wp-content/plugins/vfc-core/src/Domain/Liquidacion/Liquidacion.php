<?php
declare(strict_types=1);

namespace VFC\Core\Domain\Liquidacion;

if (!defined('ABSPATH')) {
    exit;
}

final class Liquidacion
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $centroId,
        public readonly float $importe,
        public readonly string $fecha,
        public readonly ?string $referencia,
        public readonly ?string $notas,
        public readonly ?int $registradaPor,
        public readonly string $estado,
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
            importe: (float) ($row['importe'] ?? 0),
            fecha: (string) ($row['fecha'] ?? ''),
            referencia: self::nullable($row['referencia'] ?? null),
            notas: self::nullable($row['notas'] ?? null),
            registradaPor: isset($row['registrada_por']) && $row['registrada_por'] !== null
                ? (int) $row['registrada_por']
                : null,
            estado: (string) ($row['estado'] ?? 'registrada'),
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
            'importe' => $this->importe,
            'fecha' => $this->fecha,
            'referencia' => $this->referencia,
            'notas' => $this->notas,
            'registrada_por' => $this->registradaPor,
            'estado' => $this->estado,
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

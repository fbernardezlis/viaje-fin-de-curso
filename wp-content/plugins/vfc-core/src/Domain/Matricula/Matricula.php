<?php
declare(strict_types=1);

namespace VFC\Core\Domain\Matricula;

if (!defined('ABSPATH')) {
    exit;
}

final class Matricula
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $edicionId,
        public readonly int $alumnoUserId,
        public readonly string $alias,
        public readonly string $qrTokenHash,
        public readonly ?int $creadoPor = null,
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
            edicionId: (int) ($row['edicion_id'] ?? 0),
            alumnoUserId: (int) ($row['alumno_user_id'] ?? 0),
            alias: (string) ($row['alias'] ?? ''),
            qrTokenHash: (string) ($row['qr_token_hash'] ?? ''),
            creadoPor: isset($row['creado_por']) && $row['creado_por'] !== null ? (int) $row['creado_por'] : null,
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
            'edicion_id' => $this->edicionId,
            'alumno_user_id' => $this->alumnoUserId,
            'alias' => $this->alias,
            'qr_token_hash' => $this->qrTokenHash,
            'creado_por' => $this->creadoPor,
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

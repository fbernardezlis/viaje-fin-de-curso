<?php
declare(strict_types=1);

namespace VFC\Core\Domain\Centro;

if (!defined('ABSPATH')) {
    exit;
}

final class Centro
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $nombre,
        public readonly string $slug,
        public readonly ?string $cif,
        public readonly ?string $email,
        public readonly ?string $telefono,
        public readonly ?string $direccion,
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
            nombre: (string) ($row['nombre'] ?? ''),
            slug: (string) ($row['slug'] ?? ''),
            cif: self::nullableString($row['cif'] ?? null),
            email: self::nullableString($row['email'] ?? null),
            telefono: self::nullableString($row['telefono'] ?? null),
            direccion: self::nullableString($row['direccion'] ?? null),
            estado: (string) ($row['estado'] ?? 'activo'),
            createdAt: self::nullableString($row['created_at'] ?? null),
            updatedAt: self::nullableString($row['updated_at'] ?? null),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'slug' => $this->slug,
            'cif' => $this->cif,
            'email' => $this->email,
            'telefono' => $this->telefono,
            'direccion' => $this->direccion,
            'estado' => $this->estado,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        return (string) $value;
    }
}

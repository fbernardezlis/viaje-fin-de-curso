<?php
declare(strict_types=1);

namespace VFC\Core\Services;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Generación, hash y validación de tokens QR (alumno + edición).
 * El token en claro se persiste en `vfc_matriculas.qr_token` al crear la matrícula y no cambia
 * (salvo filas legacy sin columna, que reciben un token la primera vez que se consulta).
 */
final class QrTokenService
{
    public const TOKEN_BYTES = 32;

    /**
     * Devuelve un par {token, hash}.
     *
     * @return array{token: string, hash: string}
     */
    public function generate(): array
    {
        $raw = random_bytes(self::TOKEN_BYTES);
        $token = self::base64UrlEncode($raw);
        return [
            'token' => $token,
            'hash' => self::hash($token),
        ];
    }

    public function hash(string $token): string
    {
        return self::sha256($token);
    }

    public function verify(string $token, string $expectedHash): bool
    {
        return hash_equals($expectedHash, self::sha256($token));
    }

    /**
     * URL pública que el alumno comparte para que las compras se vinculen a él.
     */
    public function urlForToken(string $token): string
    {
        return home_url('/qr/' . rawurlencode($token));
    }

    private static function sha256(string $token): string
    {
        return hash('sha256', $token);
    }

    private static function base64UrlEncode(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }
}

<?php
declare(strict_types=1);

namespace VFC\Woo\Services;

use VFC\Core\Domain\Edicion\EdicionRepository;
use VFC\Core\Domain\Edicion\EstadoEdicion;
use VFC\Core\Domain\Matricula\Matricula;
use VFC\Core\Domain\Matricula\MatriculaRepository;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Gestiona la sesion del "beneficiario" (matricula activa) mediante una cookie persistente
 * firmada con HMAC + espejo en WC()->session.
 *
 * Formato cookie: "{matriculaId}.{expUnix}.{hmacHex}".
 */
final class BeneficiarioSession
{
    public const COOKIE = 'vfc_beneficiario';
    public const WC_SESSION_KEY = 'vfc_matricula_id';
    public const COOKIE_TTL_SECONDS = 30 * DAY_IN_SECONDS;

    private MatriculaRepository $matriculas;
    private EdicionRepository $ediciones;

    public function __construct(
        ?MatriculaRepository $matriculas = null,
        ?EdicionRepository $ediciones = null
    ) {
        $this->matriculas = $matriculas ?? new MatriculaRepository();
        $this->ediciones = $ediciones ?? new EdicionRepository();
    }

    /**
     * Emite la cookie firmada y sincroniza WC session.
     */
    public function issueCookie(int $matriculaId): void
    {
        $exp = time() + self::COOKIE_TTL_SECONDS;
        $value = $matriculaId . '.' . $exp . '.' . self::sign($matriculaId, $exp);

        if (!headers_sent()) {
            setcookie(
                self::COOKIE,
                $value,
                [
                    'expires' => $exp,
                    'path' => COOKIEPATH ?: '/',
                    'domain' => COOKIE_DOMAIN ?: '',
                    'secure' => is_ssl(),
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]
            );
        }
        $_COOKIE[self::COOKIE] = $value;

        if (function_exists('WC') && WC()->session) {
            WC()->session->set(self::WC_SESSION_KEY, $matriculaId);
        }
    }

    public function clearCookie(): void
    {
        if (!headers_sent()) {
            setcookie(self::COOKIE, '', [
                'expires' => time() - 3600,
                'path' => COOKIEPATH ?: '/',
                'domain' => COOKIE_DOMAIN ?: '',
                'secure' => is_ssl(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
        unset($_COOKIE[self::COOKIE]);

        if (function_exists('WC') && WC()->session) {
            WC()->session->__unset(self::WC_SESSION_KEY);
        }
    }

    /**
     * Resuelve la matricula activa desde la cookie.
     */
    public function readMatricula(): ?Matricula
    {
        $id = $this->readMatriculaId();
        if ($id === null) {
            return null;
        }
        $matricula = $this->matriculas->find($id);
        if ($matricula === null) {
            return null;
        }
        $edicion = $this->ediciones->find($matricula->edicionId);
        if ($edicion === null || $edicion->estado !== EstadoEdicion::ACTIVA) {
            return null;
        }
        return $matricula;
    }

    public function readMatriculaId(): ?int
    {
        $raw = $_COOKIE[self::COOKIE] ?? null;
        if (!is_string($raw) || $raw === '') {
            return null;
        }
        $parts = explode('.', $raw);
        if (count($parts) !== 3) {
            return null;
        }
        [$idStr, $expStr, $sig] = $parts;
        if (!ctype_digit($idStr) || !ctype_digit($expStr)) {
            return null;
        }
        $id = (int) $idStr;
        $exp = (int) $expStr;
        if ($exp < time()) {
            return null;
        }
        $expected = self::sign($id, $exp);
        if (!hash_equals($expected, $sig)) {
            return null;
        }
        return $id;
    }

    private static function sign(int $matriculaId, int $exp): string
    {
        return hash_hmac('sha256', $matriculaId . '.' . $exp, wp_salt('auth'));
    }
}

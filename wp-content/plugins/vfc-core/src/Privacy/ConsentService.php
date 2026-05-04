<?php
declare(strict_types=1);

namespace VFC\Core\Privacy;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Cookie HTTP-only firmada con preferencias RGPD (funcional = vinculo compra-alumno, etc.).
 *
 * No sustituye asesoramiento legal: los textos de paginas legales deben revisarse con el responsable.
 */
final class ConsentService
{
    public const COOKIE_NAME = 'vfc_consent';
    public const COOKIE_VERSION = 1;
    public const COOKIE_TTL_SECONDS = 400 * DAY_IN_SECONDS;

    public static function allowsFunctional(): bool
    {
        $s = self::readState();
        return $s !== null && $s['functional'];
    }

    public static function allowsAnalytics(): bool
    {
        $s = self::readState();
        return $s !== null && $s['analytics'];
    }

    /**
     * @return array{functional: bool, analytics: bool, updated: int}|null
     */
    public static function readState(): ?array
    {
        $raw = $_COOKIE[self::COOKIE_NAME] ?? null;
        if (!is_string($raw) || $raw === '') {
            return null;
        }
        $parts = explode('.', $raw, 3);
        if (count($parts) !== 3 || $parts[0] !== 'v' . (string) self::COOKIE_VERSION) {
            return null;
        }
        [$ver, $b64, $sig] = $parts;
        $expected = self::signPayload($b64);
        if (!hash_equals($expected, $sig)) {
            return null;
        }
        $json = base64_decode(strtr($b64, '-_', '+/'), true);
        if (!is_string($json)) {
            return null;
        }
        $data = json_decode($json, true);
        if (!is_array($data)) {
            return null;
        }
        $f = !empty($data['f']);
        $a = !empty($data['a']);
        $t = isset($data['t']) && is_numeric($data['t']) ? (int) $data['t'] : 0;
        return ['functional' => $f, 'analytics' => $a, 'updated' => $t];
    }

    public static function setPreferences(bool $functional, bool $analytics): void
    {
        $payload = wp_json_encode([
            'v' => self::COOKIE_VERSION,
            'f' => $functional ? 1 : 0,
            'a' => $analytics ? 1 : 0,
            't' => time(),
        ]);
        if (!is_string($payload)) {
            return;
        }
        $b64 = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
        $value = 'v' . (string) self::COOKIE_VERSION . '.' . $b64 . '.' . self::signPayload($b64);

        if (!headers_sent()) {
            setcookie(self::COOKIE_NAME, $value, [
                'expires' => time() + self::COOKIE_TTL_SECONDS,
                'path' => COOKIEPATH ?: '/',
                'domain' => COOKIE_DOMAIN ?: '',
                'secure' => is_ssl(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
        $_COOKIE[self::COOKIE_NAME] = $value;
    }

    public static function clearConsentCookie(): void
    {
        if (!headers_sent()) {
            setcookie(self::COOKIE_NAME, '', [
                'expires' => time() - 3600,
                'path' => COOKIEPATH ?: '/',
                'domain' => COOKIE_DOMAIN ?: '',
                'secure' => is_ssl(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
        unset($_COOKIE[self::COOKIE_NAME]);
    }

    /**
     * Revoca consentimientos opcionales y, si existe el modulo Woo, limpia la cookie de beneficiario.
     */
    public static function revokeOptionalCookies(): void
    {
        self::clearConsentCookie();
        if (class_exists(\VFC\Woo\Services\BeneficiarioSession::class)) {
            (new \VFC\Woo\Services\BeneficiarioSession())->clearCookie();
        }
    }

    private static function signPayload(string $b64): string
    {
        return substr(hash_hmac('sha256', $b64, wp_salt('auth')), 0, 32);
    }
}

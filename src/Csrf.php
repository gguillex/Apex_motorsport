<?php

declare(strict_types=1);

namespace App;

/**
 * Protección CSRF mediante token sincronizado en sesión.
 * Toda petición POST debe incluir el campo `_token`.
 */
final class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public static function token(): string
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::SESSION_KEY];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(self::token()) . '">';
    }

    public static function isValid(?string $token): bool
    {
        return is_string($token)
            && !empty($_SESSION[self::SESSION_KEY])
            && hash_equals($_SESSION[self::SESSION_KEY], $token);
    }

    public static function verify(): void
    {
        if (!self::isValid($_POST['_token'] ?? null)) {
            render_error(419, 'La sesión del formulario ha caducado. Vuelve atrás, recarga la página e inténtalo de nuevo.');
        }
    }
}

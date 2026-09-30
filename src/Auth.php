<?php

declare(strict_types=1);

namespace App;

/**
 * Gestión de la sesión del usuario autenticado.
 *
 * En sesión solo se guarda lo imprescindible: id, nombre de usuario y rol.
 */
final class Auth
{
    private const SESSION_KEY = 'user';

    /** @return array{id:int, usuario:string, rol:string}|null */
    public static function user(): ?array
    {
        return $_SESSION[self::SESSION_KEY] ?? null;
    }

    public static function id(): ?int
    {
        return self::user()['id'] ?? null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function isAdmin(): bool
    {
        return (self::user()['rol'] ?? null) === 'admin';
    }

    /** @param array{id:int|string, usuario:string, rol:string} $user */
    public static function login(array $user): void
    {
        // Nuevo identificador de sesión para evitar ataques de fijación de sesión.
        session_regenerate_id(true);
        $_SESSION[self::SESSION_KEY] = [
            'id'      => (int) $user['id'],
            'usuario' => $user['usuario'],
            'rol'     => $user['rol'],
        ];
    }

    /** Actualiza los datos del usuario en sesión (p. ej. tras cambiar su nombre). */
    public static function refresh(array $user): void
    {
        if (self::check()) {
            $_SESSION[self::SESSION_KEY]['usuario'] = $user['usuario'];
            $_SESSION[self::SESSION_KEY]['rol'] = $user['rol'];
        }
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            flash('info', 'Inicia sesión para continuar.');
            redirect('login.php');
        }
    }

    public static function requireAdmin(): void
    {
        self::requireLogin();
        if (!self::isAdmin()) {
            render_error(403, 'No tienes permiso para acceder a esta sección.');
        }
    }
}

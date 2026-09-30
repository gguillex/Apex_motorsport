<?php

declare(strict_types=1);

namespace App\Validation;

use App\Repository\UserRepository;

final class UserValidator
{
    public const MIN_PASSWORD = 8;

    /**
     * @param array<string, mixed> $input  Normalmente $_POST.
     * @param bool                 $withRole         Si es false (registro público) el rol siempre es "usuario".
     * @param bool                 $passwordRequired Si es false (edición) una contraseña vacía significa "no cambiarla".
     * @return array{0: array{usuario:string, password:string, rol:string}, 1: array<string, string>}
     */
    public static function validate(array $input, bool $withRole = false, bool $passwordRequired = true): array
    {
        $errors = [];

        $username = trim(input_string($input, 'usuario'));
        if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $username)) {
            $errors['usuario'] = 'Entre 3 y 50 caracteres: letras, números, punto, guion o guion bajo.';
        }

        $password = input_string($input, 'password');
        if ($passwordRequired || $password !== '') {
            $errors += self::passwordErrors($password, input_string($input, 'password_confirmation'));
        }

        $role = 'usuario';
        if ($withRole) {
            $role = input_string($input, 'rol');
            if (!in_array($role, UserRepository::ROLES, true)) {
                $errors['rol'] = 'Rol no válido.';
            }
        }

        return [['usuario' => $username, 'password' => $password, 'rol' => $role], $errors];
    }

    /**
     * Reglas de contraseña compartidas por el alta, la edición y el cambio de contraseña.
     *
     * @return array<string, string> Errores indexados por campo (password / password_confirmation).
     */
    public static function passwordErrors(string $password, string $confirmation): array
    {
        if (strlen($password) < self::MIN_PASSWORD) {
            return ['password' => 'La contraseña debe tener al menos ' . self::MIN_PASSWORD . ' caracteres.'];
        }
        if (strlen($password) > 72) {
            return ['password' => 'La contraseña no puede superar 72 caracteres.'];
        }
        if ($password !== $confirmation) {
            return ['password_confirmation' => 'Las contraseñas no coinciden.'];
        }
        return [];
    }
}

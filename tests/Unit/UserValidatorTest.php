<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Validation\UserValidator;
use Tests\TestCase;

final class UserValidatorTest extends TestCase
{
    public function testAcceptsValidRegistration(): void
    {
        [$data, $errors] = UserValidator::validate([
            'usuario' => 'maria.garcia', 'password' => 'secreto123', 'password_confirmation' => 'secreto123',
        ]);

        $this->assertSame([], $errors);
        $this->assertSame('maria.garcia', $data['usuario']);
        $this->assertSame('usuario', $data['rol']);
    }

    public function testPublicRegistrationCannotChooseAdminRole(): void
    {
        [$data] = UserValidator::validate([
            'usuario' => 'hacker', 'password' => 'secreto123', 'password_confirmation' => 'secreto123', 'rol' => 'admin',
        ]);

        $this->assertSame('usuario', $data['rol']);
    }

    public function testAdminFormValidatesRole(): void
    {
        [, $errors] = UserValidator::validate([
            'usuario' => 'pepe', 'password' => 'secreto123', 'password_confirmation' => 'secreto123',
            'rol' => "admin'); DROP TABLE usuarios; --",
        ], withRole: true);

        $this->assertArrayHasKey('rol', $errors);
    }

    public function testRejectsInvalidUsernames(): void
    {
        foreach (['ab', 'con espacio', '<script>', str_repeat('x', 51), ''] as $username) {
            [, $errors] = UserValidator::validate([
                'usuario' => $username, 'password' => 'secreto123', 'password_confirmation' => 'secreto123',
            ]);
            $this->assertArrayHasKey('usuario', $errors, "Debería rechazar «{$username}»");
        }
    }

    public function testRejectsShortPassword(): void
    {
        [, $errors] = UserValidator::validate(['usuario' => 'pepe', 'password' => '1234', 'password_confirmation' => '1234']);
        $this->assertArrayHasKey('password', $errors);
    }

    public function testRejectsMismatchedConfirmation(): void
    {
        [, $errors] = UserValidator::validate(['usuario' => 'pepe', 'password' => 'secreto123', 'password_confirmation' => 'secreto124']);
        $this->assertArrayHasKey('password_confirmation', $errors);
    }

    public function testPasswordIsOptionalWhenEditing(): void
    {
        [$data, $errors] = UserValidator::validate(['usuario' => 'pepe', 'password' => '', 'rol' => 'usuario'], withRole: true, passwordRequired: false);
        $this->assertSame([], $errors);
        $this->assertSame('', $data['password']);

        [, $errors] = UserValidator::validate(['usuario' => 'pepe', 'password' => '123', 'rol' => 'usuario'], withRole: true, passwordRequired: false);
        $this->assertArrayHasKey('password', $errors, 'Si se escribe una contraseña, debe cumplir las reglas');
    }
}

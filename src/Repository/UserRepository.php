<?php

declare(strict_types=1);

namespace App\Repository;

use App\Exception\DomainError;
use PDO;

final class UserRepository
{
    public const ROLES = ['usuario', 'admin'];

    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return list<array<string, mixed>> */
    public function all(): array
    {
        return $this->pdo->query('SELECT id, usuario, rol, created_at FROM usuarios ORDER BY usuario')->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, usuario, rol, created_at FROM usuarios WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Devuelve el usuario si las credenciales son correctas, o null en caso contrario.
     *
     * @return array<string, mixed>|null
     */
    public function verifyCredentials(string $username, string $password): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, usuario, password, rol FROM usuarios WHERE usuario = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            return null;
        }

        if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
            $this->setPassword((int) $user['id'], $password);
        }

        unset($user['password']);
        return $user;
    }

    public function usernameExists(string $username, int $exceptId = 0): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM usuarios WHERE usuario = ? AND id <> ?');
        $stmt->execute([$username, $exceptId]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * @throws DomainError si el nombre de usuario ya existe.
     */
    public function create(string $username, string $password, string $role = 'usuario'): int
    {
        if ($this->usernameExists($username)) {
            throw new DomainError('Ese nombre de usuario ya está en uso.');
        }

        $stmt = $this->pdo->prepare('INSERT INTO usuarios (usuario, password, rol) VALUES (?, ?, ?)');
        $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), $role]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Actualiza nombre, rol y (opcionalmente) contraseña de un usuario.
     *
     * @param string|null $password Nueva contraseña, o null para conservar la actual.
     * @throws DomainError si el nombre está en uso, si un administrador intenta
     *                     cambiar su propio rol o si se quedaría sin administradores.
     */
    public function update(int $id, string $username, string $role, ?string $password, int $currentUserId): void
    {
        $user = $this->find($id);
        if ($user === null) {
            throw new DomainError('El usuario no existe.');
        }
        if ($this->usernameExists($username, $id)) {
            throw new DomainError('Ese nombre de usuario ya está en uso.');
        }
        if ($role !== $user['rol']) {
            if ($id === $currentUserId) {
                throw new DomainError('No puedes cambiar tu propio rol.');
            }
            if ($user['rol'] === 'admin' && $this->countAdmins() <= 1) {
                throw new DomainError('Debe existir al menos un administrador.');
            }
        }

        $stmt = $this->pdo->prepare('UPDATE usuarios SET usuario = ?, rol = ? WHERE id = ?');
        $stmt->execute([$username, $role, $id]);

        if ($password !== null) {
            $this->setPassword($id, $password);
        }
    }

    /**
     * Cambio de contraseña por el propio usuario (exige la contraseña actual).
     *
     * @throws DomainError si la contraseña actual no es correcta.
     */
    public function changePassword(int $id, string $currentPassword, string $newPassword): void
    {
        $stmt = $this->pdo->prepare('SELECT password FROM usuarios WHERE id = ?');
        $stmt->execute([$id]);
        $hash = $stmt->fetchColumn();

        if (!is_string($hash) || !password_verify($currentPassword, $hash)) {
            throw new DomainError('La contraseña actual no es correcta.');
        }
        $this->setPassword($id, $newPassword);
    }

    private function setPassword(int $id, string $password): void
    {
        $stmt = $this->pdo->prepare('UPDATE usuarios SET password = ? WHERE id = ?');
        $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
    }

    /**
     * @throws DomainError si se intenta borrar la propia cuenta, el último
     *                     administrador o un usuario con compras.
     */
    public function delete(int $id, int $currentUserId): void
    {
        $user = $this->find($id);
        if ($user === null) {
            throw new DomainError('El usuario no existe.');
        }
        if ($id === $currentUserId) {
            throw new DomainError('No puedes borrar tu propia cuenta.');
        }
        if ($user['rol'] === 'admin' && $this->countAdmins() <= 1) {
            throw new DomainError('Debe existir al menos un administrador.');
        }
        if ($this->hasPurchases($id)) {
            throw new DomainError('No se puede borrar un usuario con compras registradas.');
        }

        $stmt = $this->pdo->prepare('DELETE FROM usuarios WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function countAdmins(): int
    {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'admin'")->fetchColumn();
    }

    public function hasPurchases(int $id): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM compras WHERE id_usuario = ? LIMIT 1');
        $stmt->execute([$id]);
        return (bool) $stmt->fetchColumn();
    }
}

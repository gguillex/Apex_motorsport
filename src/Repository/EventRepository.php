<?php

declare(strict_types=1);

namespace App\Repository;

use App\Exception\DomainError;
use PDO;

final class EventRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return list<array<string, mixed>> */
    public function all(): array
    {
        return $this->pdo->query('SELECT * FROM eventos ORDER BY fecha')->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM eventos WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * @param array{fecha:string, nombre:string, descripcion:string} $data
     * @throws DomainError si ya hay un evento ese día.
     */
    public function create(array $data): int
    {
        $this->assertDateIsFree($data['fecha']);
        $stmt = $this->pdo->prepare('INSERT INTO eventos (fecha, nombre, descripcion) VALUES (?, ?, ?)');
        $stmt->execute([$data['fecha'], $data['nombre'], $data['descripcion']]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @param array{fecha:string, nombre:string, descripcion:string} $data
     * @throws DomainError si ya hay otro evento ese día.
     */
    public function update(int $id, array $data): void
    {
        $this->assertDateIsFree($data['fecha'], $id);
        $stmt = $this->pdo->prepare('UPDATE eventos SET fecha = ?, nombre = ?, descripcion = ? WHERE id = ?');
        $stmt->execute([$data['fecha'], $data['nombre'], $data['descripcion'], $id]);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare('DELETE FROM eventos WHERE id = ?')->execute([$id]);
    }

    private function assertDateIsFree(string $date, int $exceptId = 0): void
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM eventos WHERE fecha = ? AND id <> ?');
        $stmt->execute([$date, $exceptId]);
        if ($stmt->fetchColumn()) {
            throw new DomainError('Ya hay un evento programado ese día.');
        }
    }
}

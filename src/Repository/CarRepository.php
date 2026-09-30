<?php

declare(strict_types=1);

namespace App\Repository;

use App\Exception\DomainError;
use PDO;

final class CarRepository
{
    /** Columnas editables desde el panel de administración. */
    public const FIELDS = ['marca', 'modelo', 'anio', 'precio', 'motor', 'potencia', 'aceleracion', 'velocidad', 'stock', 'imagen'];

    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return list<array<string, mixed>> */
    public function all(): array
    {
        return $this->pdo->query('SELECT * FROM coches ORDER BY marca, modelo')->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM coches WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /** @return list<string> */
    public function brands(): array
    {
        return $this->pdo->query('SELECT DISTINCT marca FROM coches ORDER BY marca')->fetchAll(PDO::FETCH_COLUMN);
    }

    /** @return list<int> */
    public function years(): array
    {
        return array_map('intval', $this->pdo->query('SELECT DISTINCT anio FROM coches ORDER BY anio DESC')->fetchAll(PDO::FETCH_COLUMN));
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): int
    {
        $columns = implode(', ', self::FIELDS);
        $placeholders = implode(', ', array_map(static fn ($f) => ':' . $f, self::FIELDS));

        $stmt = $this->pdo->prepare("INSERT INTO coches ($columns) VALUES ($placeholders)");
        $stmt->execute($this->only($data));
        return (int) $this->pdo->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): void
    {
        $assignments = implode(', ', array_map(static fn ($f) => "$f = :$f", self::FIELDS));

        $stmt = $this->pdo->prepare("UPDATE coches SET $assignments WHERE id = :id");
        $stmt->execute($this->only($data) + ['id' => $id]);
    }

    /**
     * @throws DomainError si el coche tiene compras asociadas.
     */
    public function delete(int $id): void
    {
        if ($this->hasPurchases($id)) {
            throw new DomainError('No se puede borrar un coche con compras registradas. Si ya no está a la venta, pon su stock a 0.');
        }
        $stmt = $this->pdo->prepare('DELETE FROM coches WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function hasPurchases(int $id): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM compras WHERE id_coche = ? LIMIT 1');
        $stmt->execute([$id]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function only(array $data): array
    {
        $values = [];
        foreach (self::FIELDS as $field) {
            $values[$field] = $data[$field] ?? null;
        }
        return $values;
    }
}

<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class PurchaseRepository
{
    private const SELECT = 'SELECT c.id, c.fecha, c.precio, co.id AS id_coche, co.marca, co.modelo, co.imagen
                            FROM compras c
                            JOIN coches co ON co.id = c.id_coche';

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function create(int $userId, int $carId, string $price): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO compras (id_usuario, id_coche, precio) VALUES (?, ?, ?)');
        $stmt->execute([$userId, $carId, $price]);
        return (int) $this->pdo->lastInsertId();
    }

    /** @return list<array<string, mixed>> */
    public function forUser(int $userId): array
    {
        $stmt = $this->pdo->prepare(self::SELECT . ' WHERE c.id_usuario = ? ORDER BY c.fecha DESC, c.id DESC');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Devuelve la compra solo si pertenece al usuario indicado.
     *
     * @return array<string, mixed>|null
     */
    public function findForUser(int $id, int $userId): ?array
    {
        $stmt = $this->pdo->prepare(self::SELECT . ' WHERE c.id = ? AND c.id_usuario = ?');
        $stmt->execute([$id, $userId]);
        return $stmt->fetch() ?: null;
    }
}

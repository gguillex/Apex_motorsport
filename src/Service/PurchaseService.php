<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\DomainError;
use App\Exception\OutOfStockException;
use App\Repository\PurchaseRepository;
use PDO;
use Throwable;

/**
 * Realiza una compra de forma atómica: bloquea la fila del coche, comprueba
 * el stock, lo descuenta y registra la compra con el precio vigente.
 * Así dos compras simultáneas nunca pueden dejar el stock en negativo.
 */
final class PurchaseService
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @return int Id de la compra creada.
     * @throws OutOfStockException si no quedan unidades.
     * @throws DomainError si el coche no existe.
     */
    public function purchase(int $userId, int $carId): int
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('SELECT precio, stock FROM coches WHERE id = ? FOR UPDATE');
            $stmt->execute([$carId]);
            $car = $stmt->fetch();

            if (!$car) {
                throw new DomainError('El vehículo no existe.');
            }
            if ((int) $car['stock'] <= 0) {
                throw new OutOfStockException();
            }

            $this->pdo->prepare('UPDATE coches SET stock = stock - 1 WHERE id = ?')->execute([$carId]);
            $purchaseId = (new PurchaseRepository($this->pdo))->create($userId, $carId, (string) $car['precio']);

            $this->pdo->commit();
            return $purchaseId;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}

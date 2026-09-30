<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Exception\DomainError;
use App\Exception\OutOfStockException;
use App\Repository\CarRepository;
use App\Repository\PurchaseRepository;
use App\Repository\UserRepository;
use App\Service\PurchaseService;
use Tests\TestCase;
use Tests\TestDatabase;

final class PurchaseServiceTest extends TestCase
{
    private PurchaseService $service;
    private int $userId;

    public function setUp(): void
    {
        TestDatabase::reset();
        $this->service = new PurchaseService(db());
        $this->userId = (new UserRepository(db()))->create('cliente', 'secreto123');
    }

    private function stock(int $carId): int
    {
        return (int) (new CarRepository(db()))->find($carId)['stock'];
    }

    public function testPurchaseDecrementsStockAndStoresPrice(): void
    {
        $before = $this->stock(1);
        $purchaseId = $this->service->purchase($this->userId, 1);

        $this->assertSame($before - 1, $this->stock(1));
        $purchase = (new PurchaseRepository(db()))->findForUser($purchaseId, $this->userId);
        $this->assertSame('245000.00', $purchase['precio']);
    }

    public function testInvoicePriceDoesNotChangeWhenCarPriceChanges(): void
    {
        $purchaseId = $this->service->purchase($this->userId, 1);
        db()->exec('UPDATE coches SET precio = 1 WHERE id = 1');

        $purchase = (new PurchaseRepository(db()))->findForUser($purchaseId, $this->userId);
        $this->assertSame('245000.00', $purchase['precio']);
    }

    public function testPurchaseDateUsesApplicationTimezone(): void
    {
        $purchaseId = $this->service->purchase($this->userId, 1);
        $fecha = (new PurchaseRepository(db()))->findForUser($purchaseId, $this->userId)['fecha'];

        $diff = abs(strtotime($fecha) - time());
        $this->assertTrue($diff < 120, "La fecha guardada ($fecha) no coincide con la hora de PHP (" . date('Y-m-d H:i:s') . ')');
    }

    public function testCannotBuyWhenOutOfStock(): void
    {
        // El Bugatti (id 5) tiene 1 unidad.
        $this->service->purchase($this->userId, 5);
        $this->assertSame(0, $this->stock(5));

        $this->assertThrows(OutOfStockException::class, fn () => $this->service->purchase($this->userId, 5));
        $this->assertSame(0, $this->stock(5), 'El stock nunca debe ser negativo');
        $this->assertSame(1, count((new PurchaseRepository(db()))->forUser($this->userId)));
    }

    public function testUnknownCarThrowsAndRollsBack(): void
    {
        $this->assertThrows(DomainError::class, fn () => $this->service->purchase($this->userId, 9999), 'no existe');
        $this->assertFalse(db()->inTransaction());
    }

    public function testConcurrentPurchasesNeverOversell(): void
    {
        // Lanza varias compras simultáneas en procesos separados sobre un coche con 1 unidad.
        $config = var_export(TEST_CONFIG, true);
        $script = tempnam(sys_get_temp_dir(), 'apex');
        file_put_contents($script, '<?php
            require ' . var_export(ROOT_PATH . '/src/bootstrap.php', true) . ';
            App\Config::load(' . $config . ');
            try { (new App\Service\PurchaseService(db()))->purchase(' . $this->userId . ', 5); echo "OK"; }
            catch (App\Exception\OutOfStockException) { echo "AGOTADO"; }');

        $processes = [];
        for ($i = 0; $i < 5; $i++) {
            $processes[] = popen(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($script), 'r');
        }
        $results = array_map(static function ($p) {
            $out = stream_get_contents($p);
            pclose($p);
            return trim($out);
        }, $processes);
        unlink($script);

        $this->assertSame(1, count(array_filter($results, static fn ($r) => $r === 'OK')), 'Resultados: ' . implode(', ', $results));
        $this->assertSame(4, count(array_filter($results, static fn ($r) => $r === 'AGOTADO')));
        $this->assertSame(0, $this->stock(5));
    }
}

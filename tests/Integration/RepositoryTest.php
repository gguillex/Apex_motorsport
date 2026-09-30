<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Exception\DomainError;
use App\Repository\CarRepository;
use App\Repository\PurchaseRepository;
use App\Repository\UserRepository;
use Tests\TestCase;
use Tests\TestDatabase;

final class RepositoryTest extends TestCase
{
    private CarRepository $cars;
    private UserRepository $users;

    public function setUp(): void
    {
        TestDatabase::reset();
        $this->cars = new CarRepository(db());
        $this->users = new UserRepository(db());
    }

    private function carData(array $overrides = []): array
    {
        return array_merge([
            'marca' => 'Pagani', 'modelo' => 'Huayra', 'anio' => 2023, 'precio' => '2800000.00', 'motor' => 'V12 AMG',
            'potencia' => 800, 'aceleracion' => '2.8', 'velocidad' => 383, 'stock' => 2, 'imagen' => 'assets/img/coches/x.jpg',
        ], $overrides);
    }

    public function testSeedDataIsLoaded(): void
    {
        $this->assertSame(6, count($this->cars->all()));
        $this->assertSame(['Aston Martin', 'Bugatti', 'Ferrari', 'Lamborghini', 'McLaren', 'Porsche'], $this->cars->brands());
        $this->assertSame([2024, 2023, 2022], $this->cars->years());
    }

    public function testCreateFindUpdateAndDeleteCar(): void
    {
        $id = $this->cars->create($this->carData());
        $car = $this->cars->find($id);
        $this->assertSame('Huayra', $car['modelo']);

        $this->cars->update($id, $this->carData(['modelo' => 'Utopia', 'stock' => 0]));
        $car = $this->cars->find($id);
        $this->assertSame('Utopia', $car['modelo']);
        $this->assertSame(0, (int) $car['stock']);

        $this->cars->delete($id);
        $this->assertNull($this->cars->find($id));
    }

    public function testSqlInjectionIsStoredAsPlainText(): void
    {
        $payload = "x'); DROP TABLE coches; --";
        $id = $this->cars->create($this->carData(['modelo' => $payload]));

        $this->assertSame($payload, $this->cars->find($id)['modelo']);
        $this->assertSame(7, count($this->cars->all()));
    }

    public function testCannotDeleteCarWithPurchases(): void
    {
        $userId = $this->users->create('cliente', 'secreto123');
        (new PurchaseRepository(db()))->create($userId, 1, '245000.00');

        $this->assertThrows(DomainError::class, fn () => $this->cars->delete(1), 'compras');
        $this->assertTrue($this->cars->find(1) !== null);
    }

    public function testPasswordsAreHashedAndVerified(): void
    {
        $id = $this->users->create('laura', 'secreto123');

        $stored = db()->query("SELECT password FROM usuarios WHERE id = $id")->fetchColumn();
        $this->assertTrue(str_starts_with($stored, '$2y$'), 'La contraseña debe guardarse con bcrypt');
        $this->assertSame('laura', $this->users->verifyCredentials('laura', 'secreto123')['usuario']);
        $this->assertNull($this->users->verifyCredentials('laura', 'incorrecta'));
        $this->assertNull($this->users->verifyCredentials('noexiste', 'secreto123'));
        $this->assertFalse(array_key_exists('password', $this->users->verifyCredentials('laura', 'secreto123')));
    }

    public function testSeedAdminCanLogIn(): void
    {
        $admin = $this->users->verifyCredentials('admin', 'admin123');
        $this->assertSame('admin', $admin['rol']);
    }

    public function testLoginSqlInjectionFails(): void
    {
        $this->assertNull($this->users->verifyCredentials("admin' OR '1'='1", "' OR '1'='1"));
    }

    public function testDuplicateUsernameIsRejected(): void
    {
        $this->assertThrows(DomainError::class, fn () => $this->users->create('admin', 'otraclave123'), 'en uso');
    }

    public function testUserDeletionRules(): void
    {
        $adminId = 1;
        $clientId = $this->users->create('cliente', 'secreto123');
        $buyerId = $this->users->create('comprador', 'secreto123');
        (new PurchaseRepository(db()))->create($buyerId, 2, '198000.00');

        $this->assertThrows(DomainError::class, fn () => $this->users->delete($adminId, $adminId), 'propia cuenta');
        $this->assertThrows(DomainError::class, fn () => $this->users->delete($adminId, $clientId), 'al menos un administrador');
        $this->assertThrows(DomainError::class, fn () => $this->users->delete($buyerId, $adminId), 'compras');

        $this->users->delete($clientId, $adminId);
        $this->assertNull($this->users->find($clientId));
    }

    public function testPurchasesAreScopedToTheirOwner(): void
    {
        $ana = $this->users->create('ana', 'secreto123');
        $luis = $this->users->create('luis', 'secreto123');
        $purchases = new PurchaseRepository(db());
        $id = $purchases->create($ana, 3, '174000.00');

        $this->assertSame(1, count($purchases->forUser($ana)));
        $this->assertSame(0, count($purchases->forUser($luis)));
        $this->assertSame('911 GT3', $purchases->findForUser($id, $ana)['modelo']);
        $this->assertNull($purchases->findForUser($id, $luis));
    }

    public function testUpdateUserRules(): void
    {
        $adminId = 1;
        $clientId = $this->users->create('cliente', 'secreto123');

        $this->users->update($clientId, 'cliente2', 'admin', null, $adminId);
        $this->assertSame('admin', $this->users->find($clientId)['rol']);
        $this->assertSame('cliente2', $this->users->find($clientId)['usuario']);
        $this->assertTrue($this->users->verifyCredentials('cliente2', 'secreto123') !== null, 'Sin contraseña nueva se conserva la actual');

        $this->users->update($clientId, 'cliente2', 'admin', 'nuevaclave123', $adminId);
        $this->assertNull($this->users->verifyCredentials('cliente2', 'secreto123'));
        $this->assertTrue($this->users->verifyCredentials('cliente2', 'nuevaclave123') !== null);

        $this->assertThrows(DomainError::class, fn () => $this->users->update($clientId, 'admin', 'admin', null, $adminId), 'en uso');
        $this->assertThrows(DomainError::class, fn () => $this->users->update($adminId, 'admin', 'usuario', null, $adminId), 'propio rol');

        // Con un único administrador no se le puede quitar el rol.
        $this->users->update($clientId, 'cliente2', 'usuario', null, $adminId);
        $otherId = $this->users->create('otro', 'secreto123');
        $this->assertThrows(DomainError::class, fn () => $this->users->update($adminId, 'admin', 'usuario', null, $otherId), 'al menos un administrador');
    }

    public function testChangeOwnPassword(): void
    {
        $id = $this->users->create('laura', 'secreto123');

        $this->assertThrows(DomainError::class, fn () => $this->users->changePassword($id, 'incorrecta', 'nuevaclave123'), 'actual');
        $this->users->changePassword($id, 'secreto123', 'nuevaclave123');

        $this->assertNull($this->users->verifyCredentials('laura', 'secreto123'));
        $this->assertTrue($this->users->verifyCredentials('laura', 'nuevaclave123') !== null);
    }
}

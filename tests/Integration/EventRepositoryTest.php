<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Exception\DomainError;
use App\Repository\EventRepository;
use Tests\TestCase;
use Tests\TestDatabase;

final class EventRepositoryTest extends TestCase
{
    private EventRepository $events;

    public function setUp(): void
    {
        TestDatabase::reset();
        $this->events = new EventRepository(db());
    }

    public function testSeedEventsAreOrderedByDate(): void
    {
        $all = $this->events->all();
        $this->assertSame(14, count($all));
        $dates = array_column($all, 'fecha');
        $sorted = $dates;
        sort($sorted);
        $this->assertSame($sorted, $dates);
    }

    public function testCreateUpdateAndDelete(): void
    {
        $id = $this->events->create(['fecha' => '2027-05-01', 'nombre' => 'Prueba', 'descripcion' => 'Desc']);
        $this->assertSame('Prueba', $this->events->find($id)['nombre']);

        $this->events->update($id, ['fecha' => '2027-05-02', 'nombre' => 'Prueba 2', 'descripcion' => 'Desc 2']);
        $event = $this->events->find($id);
        $this->assertSame('2027-05-02', $event['fecha']);
        $this->assertSame('Prueba 2', $event['nombre']);

        $this->events->delete($id);
        $this->assertNull($this->events->find($id));
    }

    public function testOnlyOneEventPerDay(): void
    {
        $this->assertThrows(DomainError::class, fn () => $this->events->create(['fecha' => '2026-10-15', 'nombre' => 'X', 'descripcion' => 'Y']), 'ese día');

        // Editar un evento manteniendo su propia fecha sí está permitido.
        $id = (int) db()->query("SELECT id FROM eventos WHERE fecha = '2026-10-15'")->fetchColumn();
        $this->events->update($id, ['fecha' => '2026-10-15', 'nombre' => 'Renombrado', 'descripcion' => 'Y']);
        $this->assertSame('Renombrado', $this->events->find($id)['nombre']);

        // Pero no moverlo a un día ocupado por otro.
        $this->assertThrows(DomainError::class, fn () => $this->events->update($id, ['fecha' => '2026-11-14', 'nombre' => 'X', 'descripcion' => 'Y']));
    }
}

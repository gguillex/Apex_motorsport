<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Validation\EventValidator;
use Tests\TestCase;

final class EventValidatorTest extends TestCase
{
    public function testAcceptsValidEvent(): void
    {
        [$data, $errors] = EventValidator::validate(['fecha' => '2026-11-14', 'nombre' => '  Track Day ', 'descripcion' => 'Jornada en pista']);

        $this->assertSame([], $errors);
        $this->assertSame('Track Day', $data['nombre']);
        $this->assertSame('2026-11-14', $data['fecha']);
    }

    public function testRejectsInvalidDates(): void
    {
        foreach (['', '2026-02-30', '14/11/2026', '2026-13-01', 'mañana'] as $date) {
            [, $errors] = EventValidator::validate(['fecha' => $date, 'nombre' => 'X', 'descripcion' => 'Y']);
            $this->assertArrayHasKey('fecha', $errors, "Debería rechazar la fecha «{$date}»");
        }
    }

    public function testRequiresNameAndDescription(): void
    {
        [, $errors] = EventValidator::validate(['fecha' => '2026-11-14', 'nombre' => ' ', 'descripcion' => '']);
        $this->assertArrayHasKey('nombre', $errors);
        $this->assertArrayHasKey('descripcion', $errors);
    }

    public function testRejectsTooLongText(): void
    {
        [, $errors] = EventValidator::validate(['fecha' => '2026-11-14', 'nombre' => str_repeat('a', 121), 'descripcion' => str_repeat('b', 1001)]);
        $this->assertArrayHasKey('nombre', $errors);
        $this->assertArrayHasKey('descripcion', $errors);
    }
}

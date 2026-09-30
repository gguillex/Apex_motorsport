<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Validation\CarValidator;
use Tests\TestCase;

final class CarValidatorTest extends TestCase
{
    private function validInput(array $overrides = []): array
    {
        return array_merge([
            'marca' => 'Ferrari', 'modelo' => 'SF90', 'anio' => '2024', 'precio' => '450000',
            'motor' => 'V8 Híbrido', 'potencia' => '1000', 'aceleracion' => '2.5',
            'velocidad' => '340', 'stock' => '3',
        ], $overrides);
    }

    public function testAcceptsValidDataAndNormalizesTypes(): void
    {
        [$data, $errors] = CarValidator::validate($this->validInput(['marca' => '  Ferrari  ']));

        $this->assertSame([], $errors);
        $this->assertSame('Ferrari', $data['marca']);
        $this->assertSame(2024, $data['anio']);
        $this->assertSame('450000.00', $data['precio']);
        $this->assertSame('2.5', $data['aceleracion']);
        $this->assertSame(3, $data['stock']);
    }

    public function testAcceptsDecimalCommaForSpanishInput(): void
    {
        [$data, $errors] = CarValidator::validate($this->validInput(['aceleracion' => '3,4', 'precio' => '199999,99']));

        $this->assertSame([], $errors);
        $this->assertSame('3.4', $data['aceleracion']);
        $this->assertSame('199999.99', $data['precio']);
    }

    public function testRequiresTextFields(): void
    {
        [, $errors] = CarValidator::validate($this->validInput(['marca' => '', 'modelo' => '   ', 'motor' => '']));

        $this->assertArrayHasKey('marca', $errors);
        $this->assertArrayHasKey('modelo', $errors);
        $this->assertArrayHasKey('motor', $errors);
    }

    public function testRejectsOutOfRangeNumbers(): void
    {
        [, $errors] = CarValidator::validate($this->validInput([
            'anio' => '1800', 'precio' => '0', 'stock' => '-1', 'velocidad' => '9999', 'aceleracion' => 'rápido',
        ]));

        foreach (['anio', 'precio', 'stock', 'velocidad', 'aceleracion'] as $field) {
            $this->assertArrayHasKey($field, $errors);
        }
    }

    public function testRejectsTooLongText(): void
    {
        [, $errors] = CarValidator::validate($this->validInput(['marca' => str_repeat('a', 51)]));
        $this->assertArrayHasKey('marca', $errors);
    }

    public function testArrayInputsDoNotCrash(): void
    {
        [, $errors] = CarValidator::validate($this->validInput(['marca' => ['x'], 'anio' => ['2024']]));

        $this->assertArrayHasKey('marca', $errors);
        $this->assertArrayHasKey('anio', $errors);
    }
}

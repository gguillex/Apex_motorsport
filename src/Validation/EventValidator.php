<?php

declare(strict_types=1);

namespace App\Validation;

use DateTimeImmutable;

final class EventValidator
{
    /**
     * @param array<string, mixed> $input Normalmente $_POST.
     * @return array{0: array{fecha:string, nombre:string, descripcion:string}, 1: array<string, string>}
     */
    public static function validate(array $input): array
    {
        $errors = [];

        $fecha = trim(input_string($input, 'fecha'));
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
        if ($date === false || $date->format('Y-m-d') !== $fecha) {
            $errors['fecha'] = 'Introduce una fecha válida.';
        }

        $nombre = trim(input_string($input, 'nombre'));
        if ($nombre === '') {
            $errors['nombre'] = 'Este campo es obligatorio.';
        } elseif (mb_strlen($nombre) > 120) {
            $errors['nombre'] = 'Máximo 120 caracteres.';
        }

        $descripcion = trim(input_string($input, 'descripcion'));
        if ($descripcion === '') {
            $errors['descripcion'] = 'Este campo es obligatorio.';
        } elseif (mb_strlen($descripcion) > 1000) {
            $errors['descripcion'] = 'Máximo 1000 caracteres.';
        }

        return [['fecha' => $fecha, 'nombre' => $nombre, 'descripcion' => $descripcion], $errors];
    }
}

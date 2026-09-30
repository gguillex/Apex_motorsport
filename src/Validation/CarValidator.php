<?php

declare(strict_types=1);

namespace App\Validation;

/**
 * Valida y normaliza los datos del formulario de coches.
 * La imagen se gestiona aparte (ver App\Service\ImageUploader).
 */
final class CarValidator
{
    /**
     * @param array<string, mixed> $input Normalmente $_POST.
     * @return array{0: array<string, mixed>, 1: array<string, string>} [datos normalizados, errores por campo]
     */
    public static function validate(array $input): array
    {
        $errors = [];
        $data = [];

        foreach (['marca' => 50, 'modelo' => 80, 'motor' => 100] as $field => $max) {
            $value = trim(input_string($input, $field));
            if ($value === '') {
                $errors[$field] = 'Este campo es obligatorio.';
            } elseif (mb_strlen($value) > $max) {
                $errors[$field] = "Máximo $max caracteres.";
            }
            $data[$field] = $value;
        }

        $maxYear = (int) date('Y') + 1;
        $data['anio']        = self::int($input, 'anio', 1900, $maxYear, "Introduce un año entre 1900 y $maxYear.", $errors);
        $data['potencia']    = self::int($input, 'potencia', 0, 5000, 'Introduce una potencia entre 0 y 5000 CV.', $errors);
        $data['velocidad']   = self::int($input, 'velocidad', 0, 600, 'Introduce una velocidad entre 0 y 600 km/h.', $errors);
        $data['stock']       = self::int($input, 'stock', 0, 100000, 'El stock debe ser un número entero igual o mayor que 0.', $errors);
        $data['aceleracion'] = self::decimal($input, 'aceleracion', 0, 99.9, 1, 'Introduce una aceleración entre 0 y 99,9 s.', $errors);
        $data['precio']      = self::decimal($input, 'precio', 0.01, 9999999999.99, 2, 'Introduce un precio mayor que 0.', $errors);

        return [$data, $errors];
    }

    private static function int(array $input, string $field, int $min, int $max, string $message, array &$errors): ?int
    {
        $value = filter_var(trim(input_string($input, $field)), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => $min, 'max_range' => $max],
        ]);
        if ($value === false) {
            $errors[$field] = $message;
            return null;
        }
        return $value;
    }

    private static function decimal(array $input, string $field, float $min, float $max, int $decimals, string $message, array &$errors): ?string
    {
        // Acepta tanto "3,5" como "3.5".
        $raw = str_replace(',', '.', trim(input_string($input, $field)));
        $value = filter_var($raw, FILTER_VALIDATE_FLOAT);
        if ($value === false || $value < $min || $value > $max) {
            $errors[$field] = $message;
            return null;
        }
        return number_format($value, $decimals, '.', '');
    }
}

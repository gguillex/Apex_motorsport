<?php
/**
 * GET api/coches.php — Catálogo en formato JSON (consumido por assets/js/catalog.js).
 */

declare(strict_types=1);

require __DIR__ . '/../../src/bootstrap.php';

use App\Repository\CarRepository;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

try {
    $cars = array_map(static fn (array $car): array => [
        'id'          => (int) $car['id'],
        'marca'       => $car['marca'],
        'modelo'      => $car['modelo'],
        'anio'        => (int) $car['anio'],
        'precio'      => (float) $car['precio'],
        'motor'       => $car['motor'],
        'potencia'    => (int) $car['potencia'],
        'aceleracion' => (float) $car['aceleracion'],
        'velocidad'   => (int) $car['velocidad'],
        'stock'       => (int) $car['stock'],
        'imagen'      => $car['imagen'],
    ], (new CarRepository(db()))->all());

    echo json_encode($cars, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    error_log((string) $e);
    http_response_code(500);
    echo json_encode(['error' => 'No se ha podido cargar el catálogo.']);
}

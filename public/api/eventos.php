<?php
/**
 * GET api/eventos.php — Eventos del calendario en formato JSON
 * (objeto indexado por fecha "AAAA-MM-DD", consumido por assets/js/calendar.js).
 */

declare(strict_types=1);

require __DIR__ . '/../../src/bootstrap.php';

use App\Repository\EventRepository;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

try {
    $events = [];
    foreach ((new EventRepository(db()))->all() as $event) {
        $events[$event['fecha']] = ['name' => $event['nombre'], 'desc' => $event['descripcion']];
    }
    // (object) para que un calendario vacío se serialice como {} y no como [].
    echo json_encode((object) $events, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    error_log((string) $e);
    http_response_code(500);
    echo json_encode(['error' => 'No se han podido cargar los eventos.']);
}

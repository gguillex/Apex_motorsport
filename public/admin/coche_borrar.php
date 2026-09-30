<?php
/**
 * POST admin/coche_borrar.php — Borra un coche (si no tiene compras).
 */

declare(strict_types=1);

require __DIR__ . '/../../src/bootstrap.php';

use App\Auth;
use App\Csrf;
use App\Exception\DomainError;
use App\Repository\CarRepository;
use App\Service\ImageUploader;

Auth::requireAdmin();

if (!is_post()) {
    redirect('admin/');
}
Csrf::verify();

$repo = new CarRepository(db());
$car = $repo->find(post_id());

if ($car === null) {
    flash('error', 'El coche no existe.');
    redirect('admin/');
}

try {
    $repo->delete((int) $car['id']);
    (new ImageUploader(PUBLIC_PATH, (string) config('uploads.dir'), (int) config('uploads.max_bytes')))->delete($car['imagen']);
    flash('success', car_name($car) . ' borrado correctamente.');
} catch (DomainError $e) {
    flash('error', $e->getMessage());
}

redirect('admin/');

<?php
/**
 * POST admin/evento_borrar.php — Borra un evento del calendario.
 */

declare(strict_types=1);

require __DIR__ . '/../../src/bootstrap.php';

use App\Auth;
use App\Csrf;
use App\Repository\EventRepository;

Auth::requireAdmin();

if (!is_post()) {
    redirect('admin/eventos.php');
}
Csrf::verify();

$repo = new EventRepository(db());
$event = $repo->find(post_id());

if ($event === null) {
    flash('error', 'El evento no existe.');
} else {
    $repo->delete((int) $event['id']);
    flash('success', 'Evento «' . $event['nombre'] . '» borrado correctamente.');
}

redirect('admin/eventos.php');

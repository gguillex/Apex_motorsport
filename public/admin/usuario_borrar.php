<?php
/**
 * POST admin/usuario_borrar.php — Borra un usuario.
 */

declare(strict_types=1);

require __DIR__ . '/../../src/bootstrap.php';

use App\Auth;
use App\Csrf;
use App\Exception\DomainError;
use App\Repository\UserRepository;

Auth::requireAdmin();

if (!is_post()) {
    redirect('admin/usuarios.php');
}
Csrf::verify();

try {
    (new UserRepository(db()))->delete(post_id(), Auth::id());
    flash('success', 'Usuario borrado correctamente.');
} catch (DomainError $e) {
    flash('error', $e->getMessage());
}

redirect('admin/usuarios.php');

<?php
/**
 * POST comprar.php — Registra la compra de un vehículo y redirige a la factura.
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use App\Auth;
use App\Csrf;
use App\Exception\DomainError;
use App\Service\PurchaseService;

if (!is_post()) {
    redirect('index.php');
}

Auth::requireLogin();
Csrf::verify();

$carId = post_id();

try {
    $purchaseId = (new PurchaseService(db()))->purchase(Auth::id(), $carId);
} catch (DomainError $e) {
    flash('error', $e->getMessage());
    redirect($carId > 0 ? 'coche.php?id=' . $carId : 'index.php');
}

flash('success', '¡Compra realizada con éxito! Aquí tienes tu factura.');
redirect('factura.php?id=' . $purchaseId);

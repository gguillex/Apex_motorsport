<?php
/**
 * Historial de compras del usuario autenticado.
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use App\Auth;
use App\Repository\PurchaseRepository;

Auth::requireLogin();

$purchases = (new PurchaseRepository(db()))->forUser(Auth::id());

view('layout/header', ['title' => 'Mis compras', 'styles' => ['admin.css']]);
?>

  <main class="page page--narrow" id="contenido">
    <header class="page-header">
      <h1 class="page-title">Mis compras</h1>
      <p class="section-subtitle">
        Conectado como <strong><?= e(Auth::user()['usuario']) ?></strong> ·
        <a href="<?= e(url('cuenta.php')) ?>">Mi cuenta</a>
      </p>
    </header>

    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th scope="col">Nº orden</th>
            <th scope="col">Fecha</th>
            <th scope="col">Vehículo</th>
            <th scope="col">Importe</th>
            <th scope="col"><span class="sr-only">Acciones</span></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($purchases as $p): ?>
          <tr>
            <td>#<?= (int) $p['id'] ?></td>
            <td><?= e(format_datetime($p['fecha'])) ?></td>
            <td><?= e(car_name($p)) ?></td>
            <td><?= e(format_price($p['precio'])) ?></td>
            <td><a href="<?= e(url('factura.php?id=' . (int) $p['id'])) ?>" class="btn-action btn-action--primary">Factura</a></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$purchases): ?>
          <tr>
            <td colspan="5" class="data-table__empty">
              Todavía no has realizado ninguna compra. <a href="<?= e(url('index.php')) ?>#catalogo">Ver catálogo</a>
            </td>
          </tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </main>

<?php view('layout/footer');

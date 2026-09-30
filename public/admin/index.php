<?php
/**
 * Panel de administración: listado de coches.
 */

declare(strict_types=1);

require __DIR__ . '/../../src/bootstrap.php';

use App\Auth;
use App\Csrf;
use App\Repository\CarRepository;

Auth::requireAdmin();

$cars = (new CarRepository(db()))->all();

view('layout/admin_header', ['title' => 'Gestión de coches', 'section' => 'coches']);
?>

  <p class="toolbar">
    <a href="<?= e(url('admin/coche.php')) ?>" class="btn btn--primary">+ Añadir coche</a>
  </p>

  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th scope="col">ID</th>
          <th scope="col"><span class="sr-only">Foto</span></th>
          <th scope="col">Marca</th>
          <th scope="col">Modelo</th>
          <th scope="col">Año</th>
          <th scope="col">Precio</th>
          <th scope="col">Stock</th>
          <th scope="col">Acciones</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($cars as $car): ?>
        <tr>
          <td><?= (int) $car['id'] ?></td>
          <td><img src="<?= e(url($car['imagen'])) ?>" alt="" class="data-table__thumb" loading="lazy"></td>
          <td><?= e($car['marca']) ?></td>
          <td><?= e($car['modelo']) ?></td>
          <td><?= (int) $car['anio'] ?></td>
          <td><?= e(format_price($car['precio'])) ?></td>
          <td><span class="badge<?= (int) $car['stock'] === 0 ? ' badge--danger' : '' ?>"><?= (int) $car['stock'] ?></span></td>
          <td class="data-table__actions">
            <a href="<?= e(url('admin/coche.php?id=' . (int) $car['id'])) ?>" class="btn-action btn-action--primary">Editar</a>
            <form action="<?= e(url('admin/coche_borrar.php')) ?>" method="post" class="inline-form"
                  data-confirm="¿Seguro que quieres borrar el <?= e(car_name($car)) ?>? Esta acción no se puede deshacer.">
              <?= Csrf::field() ?>
              <input type="hidden" name="id" value="<?= (int) $car['id'] ?>">
              <button type="submit" class="btn-action btn-action--danger">Borrar</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$cars): ?>
        <tr><td colspan="8" class="data-table__empty">No hay coches registrados.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

<?php view('layout/admin_footer');

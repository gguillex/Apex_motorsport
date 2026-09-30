<?php
/**
 * Panel de administración: eventos del calendario.
 */

declare(strict_types=1);

require __DIR__ . '/../../src/bootstrap.php';

use App\Auth;
use App\Csrf;
use App\Repository\EventRepository;

Auth::requireAdmin();

$events = (new EventRepository(db()))->all();
$today = date('Y-m-d');

view('layout/admin_header', ['title' => 'Gestión de eventos', 'section' => 'eventos']);
?>

  <p class="toolbar">
    <a href="<?= e(url('admin/evento.php')) ?>" class="btn btn--primary">+ Añadir evento</a>
  </p>

  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th scope="col">Fecha</th>
          <th scope="col">Evento</th>
          <th scope="col">Descripción</th>
          <th scope="col">Acciones</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($events as $event): ?>
        <tr<?= $event['fecha'] < $today ? ' class="is-past"' : '' ?>>
          <td class="data-table__nowrap"><?= e((new DateTimeImmutable($event['fecha']))->format('d/m/Y')) ?></td>
          <td><?= e($event['nombre']) ?></td>
          <td class="data-table__clip" title="<?= e($event['descripcion']) ?>"><?= e($event['descripcion']) ?></td>
          <td class="data-table__actions">
            <a href="<?= e(url('admin/evento.php?id=' . (int) $event['id'])) ?>" class="btn-action btn-action--primary">Editar</a>
            <form action="<?= e(url('admin/evento_borrar.php')) ?>" method="post" class="inline-form"
                  data-confirm="¿Seguro que quieres borrar el evento «<?= e($event['nombre']) ?>»?">
              <?= Csrf::field() ?>
              <input type="hidden" name="id" value="<?= (int) $event['id'] ?>">
              <button type="submit" class="btn-action btn-action--danger">Borrar</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$events): ?>
        <tr><td colspan="4" class="data-table__empty">No hay eventos programados.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
  <p class="muted-text">Los eventos pasados aparecen atenuados.</p>

<?php view('layout/admin_footer');

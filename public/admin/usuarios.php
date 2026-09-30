<?php
/**
 * Panel de administración: listado de usuarios.
 */

declare(strict_types=1);

require __DIR__ . '/../../src/bootstrap.php';

use App\Auth;
use App\Csrf;
use App\Repository\UserRepository;

Auth::requireAdmin();

$users = (new UserRepository(db()))->all();

view('layout/admin_header', ['title' => 'Gestión de usuarios', 'section' => 'usuarios']);
?>

  <p class="toolbar">
    <a href="<?= e(url('admin/usuario.php')) ?>" class="btn btn--primary">+ Añadir usuario</a>
  </p>

  <div class="table-wrap">
    <table class="data-table data-table--narrow">
      <thead>
        <tr>
          <th scope="col">ID</th>
          <th scope="col">Usuario</th>
          <th scope="col">Rol</th>
          <th scope="col">Alta</th>
          <th scope="col">Acciones</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($users as $user): ?>
        <tr>
          <td><?= (int) $user['id'] ?></td>
          <td><?= e($user['usuario']) ?></td>
          <td><span class="badge<?= $user['rol'] === 'admin' ? ' badge--accent' : '' ?>"><?= e($user['rol']) ?></span></td>
          <td><?= e(format_datetime($user['created_at'])) ?></td>
          <td class="data-table__actions">
            <a href="<?= e(url('admin/usuario.php?id=' . (int) $user['id'])) ?>" class="btn-action btn-action--primary">Editar</a>
            <?php if ((int) $user['id'] === Auth::id()): ?>
              <span class="muted-text">Tu cuenta</span>
            <?php else: ?>
              <form action="<?= e(url('admin/usuario_borrar.php')) ?>" method="post" class="inline-form"
                    data-confirm="¿Seguro que quieres borrar al usuario «<?= e($user['usuario']) ?>»?">
                <?= Csrf::field() ?>
                <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                <button type="submit" class="btn-action btn-action--danger">Borrar</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

<?php view('layout/admin_footer');

<?php
/**
 * Alta (sin ?id) y edición (con ?id=N) de usuarios desde el panel.
 * Al editar, la contraseña es opcional: si se deja vacía se conserva.
 */

declare(strict_types=1);

require __DIR__ . '/../../src/bootstrap.php';

use App\Auth;
use App\Csrf;
use App\Exception\DomainError;
use App\Repository\UserRepository;
use App\Validation\UserValidator;

Auth::requireAdmin();

$repo = new UserRepository(db());

$id = query_id();
$existing = $id > 0 ? $repo->find($id) : null;
if ($id > 0 && $existing === null) {
    render_error(404, 'El usuario que intentas editar no existe.');
}
$isEdit = $existing !== null;
$isSelf = $isEdit && (int) $existing['id'] === Auth::id();

$data = $existing ?? ['usuario' => '', 'rol' => 'usuario'];
$errors = [];
$formError = null;

if (is_post()) {
    Csrf::verify();

    [$data, $errors] = UserValidator::validate($_POST, withRole: true, passwordRequired: !$isEdit);

    if (!$errors) {
        try {
            if ($isEdit) {
                $repo->update($id, $data['usuario'], $data['rol'], $data['password'] !== '' ? $data['password'] : null, Auth::id());
                if ($isSelf) {
                    Auth::refresh($data);
                }
                flash('success', 'Usuario «' . $data['usuario'] . '» actualizado correctamente.');
            } else {
                $repo->create($data['usuario'], $data['password'], $data['rol']);
                flash('success', 'Usuario «' . $data['usuario'] . '» creado correctamente.');
            }
            redirect('admin/usuarios.php');
        } catch (DomainError $e) {
            $formError = $e->getMessage();
        }
    }
}

$title = $isEdit ? 'Editar usuario' : 'Añadir usuario';
view('layout/admin_header', ['title' => $title, 'section' => 'usuarios']);
?>

  <section class="card card--form card--narrow">
    <?php if ($formError): ?>
      <p class="alert alert--error" role="alert"><?= e($formError) ?></p>
    <?php endif; ?>
    <form action="" method="post" class="form" novalidate>
      <?= Csrf::field() ?>
      <p class="form-group">
        <label for="usuario">Nombre de usuario</label>
        <input type="text" id="usuario" name="usuario" value="<?= e($data['usuario']) ?>" autocomplete="off"
               required<?= field_attrs($errors, 'usuario') ?>>
        <?= field_error($errors, 'usuario') ?>
      </p>
      <p class="form-group">
        <label for="password">
          <?= $isEdit ? 'Nueva contraseña' : 'Contraseña' ?>
          <span class="hint">(<?= $isEdit ? 'déjala vacía para no cambiarla; ' : '' ?>mínimo <?= UserValidator::MIN_PASSWORD ?> caracteres)</span>
        </label>
        <input type="password" id="password" name="password" autocomplete="new-password"
               minlength="<?= UserValidator::MIN_PASSWORD ?>"<?= $isEdit ? '' : ' required' ?><?= field_attrs($errors, 'password') ?>>
        <?= field_error($errors, 'password') ?>
      </p>
      <p class="form-group">
        <label for="password_confirmation">Repite la contraseña</label>
        <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password"
               <?= $isEdit ? '' : 'required' ?><?= field_attrs($errors, 'password_confirmation') ?>>
        <?= field_error($errors, 'password_confirmation') ?>
      </p>
      <p class="form-group">
        <label for="rol">Rol</label>
        <?php if ($isSelf): ?>
          <input type="hidden" name="rol" value="<?= e($data['rol']) ?>">
          <select id="rol" disabled>
            <option><?= $data['rol'] === 'admin' ? 'Administrador' : 'Cliente' ?></option>
          </select>
          <span class="hint">No puedes cambiar tu propio rol.</span>
        <?php else: ?>
          <select id="rol" name="rol" required<?= field_attrs($errors, 'rol') ?>>
            <option value="usuario"<?= $data['rol'] === 'usuario' ? ' selected' : '' ?>>Cliente</option>
            <option value="admin"<?= $data['rol'] === 'admin' ? ' selected' : '' ?>>Administrador</option>
          </select>
        <?php endif; ?>
        <?= field_error($errors, 'rol') ?>
      </p>
      <p class="form-actions">
        <button type="submit" class="btn btn--primary"><?= $isEdit ? 'Guardar cambios' : 'Crear usuario' ?></button>
        <a href="<?= e(url('admin/usuarios.php')) ?>" class="btn btn--outline">Cancelar</a>
      </p>
    </form>
  </section>

<?php view('layout/admin_footer');

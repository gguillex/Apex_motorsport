<?php
/**
 * Mi cuenta: datos del usuario y cambio de contraseña.
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use App\Auth;
use App\Csrf;
use App\Exception\DomainError;
use App\Repository\UserRepository;
use App\Validation\UserValidator;

Auth::requireLogin();

$repo = new UserRepository(db());
$user = $repo->find(Auth::id());
if ($user === null) {
    // La cuenta se ha borrado mientras la sesión seguía abierta.
    Auth::logout();
    redirect('login.php');
}

$errors = [];

if (is_post()) {
    Csrf::verify();

    $current = input_string($_POST, 'current_password');
    $new = input_string($_POST, 'password');
    $errors = UserValidator::passwordErrors($new, input_string($_POST, 'password_confirmation'));
    if ($current === '') {
        $errors['current_password'] = 'Introduce tu contraseña actual.';
    }

    if (!$errors) {
        try {
            $repo->changePassword((int) $user['id'], $current, $new);
            flash('success', 'Contraseña actualizada correctamente.');
            redirect('cuenta.php');
        } catch (DomainError $e) {
            $errors['current_password'] = $e->getMessage();
        }
    }
}

view('layout/header', ['title' => 'Mi cuenta', 'styles' => ['forms.css']]);
?>

  <main class="page centered-page" id="contenido">
    <section class="card card--narrow">
      <h1 class="card__title">Mi cuenta</h1>

      <dl class="account-summary">
        <div><dt>Usuario</dt><dd><?= e($user['usuario']) ?></dd></div>
        <div><dt>Tipo de cuenta</dt><dd><?= $user['rol'] === 'admin' ? 'Administrador' : 'Cliente' ?></dd></div>
        <div><dt>Cliente desde</dt><dd><?= e((new DateTimeImmutable($user['created_at']))->format('d/m/Y')) ?></dd></div>
      </dl>

      <p class="account-links">
        <a href="<?= e(url('mis_compras.php')) ?>" class="btn btn--outline btn--block">Ver mis compras</a>
      </p>

      <h2 class="account-subtitle">Cambiar contraseña</h2>
      <form action="<?= e(url('cuenta.php')) ?>" method="post" class="form" novalidate>
        <?= Csrf::field() ?>
        <p class="form-group">
          <label for="current_password">Contraseña actual</label>
          <input type="password" id="current_password" name="current_password" autocomplete="current-password"
                 required<?= field_attrs($errors, 'current_password') ?>>
          <?= field_error($errors, 'current_password') ?>
        </p>
        <p class="form-group">
          <label for="password">Nueva contraseña <span class="hint">(mínimo <?= UserValidator::MIN_PASSWORD ?> caracteres)</span></label>
          <input type="password" id="password" name="password" autocomplete="new-password"
                 minlength="<?= UserValidator::MIN_PASSWORD ?>" required<?= field_attrs($errors, 'password') ?>>
          <?= field_error($errors, 'password') ?>
        </p>
        <p class="form-group">
          <label for="password_confirmation">Repite la nueva contraseña</label>
          <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password"
                 required<?= field_attrs($errors, 'password_confirmation') ?>>
          <?= field_error($errors, 'password_confirmation') ?>
        </p>
        <button type="submit" class="btn btn--primary btn--block">Actualizar contraseña</button>
      </form>
    </section>
  </main>

<?php view('layout/footer');

<?php
/**
 * Registro público de clientes (siempre con rol "usuario").
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use App\Auth;
use App\Csrf;
use App\Exception\DomainError;
use App\Repository\UserRepository;
use App\Validation\UserValidator;

if (Auth::check()) {
    redirect('index.php');
}

$errors = [];
$data = ['usuario' => ''];

if (is_post()) {
    Csrf::verify();

    [$data, $errors] = UserValidator::validate($_POST);

    if (!$errors) {
        try {
            $users = new UserRepository(db());
            $id = $users->create($data['usuario'], $data['password']);
            Auth::login(['id' => $id, 'usuario' => $data['usuario'], 'rol' => 'usuario']);
            flash('success', 'Cuenta creada correctamente. ¡Bienvenido a APEX Motorsport!');
            redirect('index.php');
        } catch (DomainError $e) {
            $errors['usuario'] = $e->getMessage();
        }
    }
}

view('layout/header', ['title' => 'Crear cuenta', 'styles' => ['forms.css']]);
?>

  <main class="page centered-page" id="contenido">
    <section class="card card--narrow">
      <h1 class="card__title">Crear cuenta</h1>

      <form action="<?= e(url('registro.php')) ?>" method="post" class="form" novalidate>
        <?= Csrf::field() ?>
        <p class="form-group">
          <label for="usuario">Nombre de usuario</label>
          <input type="text" id="usuario" name="usuario" value="<?= e($data['usuario']) ?>"
                 autocomplete="username" required<?= field_attrs($errors, 'usuario') ?>>
          <?= field_error($errors, 'usuario') ?>
        </p>
        <p class="form-group">
          <label for="password">Contraseña <span class="hint">(mínimo <?= UserValidator::MIN_PASSWORD ?> caracteres)</span></label>
          <input type="password" id="password" name="password" autocomplete="new-password"
                 minlength="<?= UserValidator::MIN_PASSWORD ?>" required<?= field_attrs($errors, 'password') ?>>
          <?= field_error($errors, 'password') ?>
        </p>
        <p class="form-group">
          <label for="password_confirmation">Repite la contraseña</label>
          <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password"
                 required<?= field_attrs($errors, 'password_confirmation') ?>>
          <?= field_error($errors, 'password_confirmation') ?>
        </p>
        <button type="submit" class="btn btn--primary btn--block">Crear cuenta</button>
      </form>

      <p class="card__footer">¿Ya tienes cuenta? <a href="<?= e(url('login.php')) ?>">Inicia sesión</a></p>
    </section>
  </main>

<?php view('layout/footer');

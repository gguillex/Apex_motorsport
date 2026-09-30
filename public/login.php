<?php
/**
 * Inicio de sesión (GET muestra el formulario, POST lo procesa).
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use App\Auth;
use App\Csrf;
use App\Repository\UserRepository;

if (Auth::check()) {
    redirect(Auth::isAdmin() ? 'admin/' : 'index.php');
}

$error = null;
$username = '';

if (is_post()) {
    Csrf::verify();

    $username = trim(input_string($_POST, 'usuario'));
    $user = (new UserRepository(db()))->verifyCredentials($username, input_string($_POST, 'password'));

    if ($user !== null) {
        Auth::login($user);
        flash('success', 'Bienvenido, ' . $user['usuario'] . '.');
        redirect($user['rol'] === 'admin' ? 'admin/' : 'index.php');
    }

    $error = 'Usuario o contraseña incorrectos.';
}

view('layout/header', ['title' => 'Iniciar sesión', 'styles' => ['forms.css']]);
?>

  <main class="page centered-page" id="contenido">
    <section class="card card--narrow">
      <h1 class="card__title">Iniciar sesión</h1>

      <?php if ($error): ?>
        <p class="alert alert--error" role="alert"><?= e($error) ?></p>
      <?php endif; ?>

      <form action="<?= e(url('login.php')) ?>" method="post" class="form" novalidate>
        <?= Csrf::field() ?>
        <p class="form-group">
          <label for="usuario">Usuario</label>
          <input type="text" id="usuario" name="usuario" value="<?= e($username) ?>" autocomplete="username" required autofocus>
        </p>
        <p class="form-group">
          <label for="password">Contraseña</label>
          <input type="password" id="password" name="password" autocomplete="current-password" required>
        </p>
        <button type="submit" class="btn btn--primary btn--block">Entrar</button>
      </form>

      <p class="card__footer">¿No tienes cuenta? <a href="<?= e(url('registro.php')) ?>">Regístrate</a></p>
    </section>
  </main>

<?php view('layout/footer');

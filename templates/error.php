<?php
/**
 * Página de error independiente (no depende de la base de datos).
 *
 * @var int         $status
 * @var string      $message
 * @var string|null $detail  Solo en modo debug.
 */
$titles = [403 => 'Acceso denegado', 404 => 'Página no encontrada', 419 => 'Sesión caducada', 500 => 'Error del servidor'];
$heading = $titles[$status] ?? 'Error';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex">
  <title><?= e($heading) ?> — APEX Motorsport</title>
  <link rel="stylesheet" href="<?= e(asset('assets/css/reset.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('assets/css/styles.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('assets/css/forms.css')) ?>">
</head>
<body class="centered-page">
  <main class="card card--narrow error-page">
    <p class="error-page__code"><?= (int) $status ?></p>
    <h1 class="card__title"><?= e($heading) ?></h1>
    <p><?= e($message) ?></p>
    <?php if (!empty($detail)): ?>
      <pre class="error-page__detail"><?= e($detail) ?></pre>
    <?php endif; ?>
    <p class="card__footer"><a href="<?= e(url('index.php')) ?>" class="btn btn--outline">&larr; Volver al inicio</a></p>
  </main>
</body>
</html>

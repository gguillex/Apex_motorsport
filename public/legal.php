<?php
/**
 * Avisos legales: privacidad, aviso legal, cookies, términos y accesibilidad.
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

$tabs = [
    'privacidad'    => 'Privacidad',
    'aviso'         => 'Aviso legal',
    'cookies'       => 'Cookies',
    'terminos'      => 'Términos',
    'accesibilidad' => 'Accesibilidad',
];

view('layout/header', [
    'title'       => 'Avisos legales',
    'styles'      => ['legal.css'],
    'description' => 'APEX Motorsport — Avisos legales y políticas',
]);
?>

  <header class="site-header site-header--compact">
    <h1 class="logo-text">Avisos legales</h1>
    <p class="tagline">Política de privacidad, términos y condiciones</p>
  </header>

  <main class="section-catalog" id="contenido">
    <div class="legal-tabs" role="tablist" aria-label="Secciones legales">
      <?php $first = true; foreach ($tabs as $key => $label): ?>
        <button type="button" role="tab" class="legal-tab<?= $first ? ' active' : '' ?>"
                id="tab-<?= e($key) ?>" data-tab="<?= e($key) ?>"
                aria-controls="panel-<?= e($key) ?>" aria-selected="<?= $first ? 'true' : 'false' ?>"
                tabindex="<?= $first ? '0' : '-1' ?>"><?= e($label) ?></button>
      <?php $first = false; endforeach; ?>
    </div>

    <?php view('legal/panels'); ?>
  </main>

<?php view('layout/footer', ['scripts' => ['legal.js']]);

<?php
/**
 * Cabecera común de la web pública.
 *
 * @var string        $title      Título de la página (sin el nombre de la marca).
 * @var list<string>  $styles     Hojas de estilo adicionales de assets/css/.
 * @var string        $bodyClass  Clase opcional para <body>.
 * @var bool          $isHome     true en la portada (los enlaces del menú son anclas locales).
 * @var string        $description Meta descripción opcional.
 */
$styles      ??= [];
$bodyClass   ??= '';
$isHome      ??= false;
$description ??= 'APEX Motorsport — Exclusividad en movimiento. Compra y venta de supercars de alta gama.';
$appName     = config('app.name', 'APEX Motorsport');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="<?= e($description) ?>">
  <meta name="author" content="APEX Motorsport S.L.">
  <title><?= e(isset($title) ? "$title — $appName" : $appName) ?></title>
  <link rel="stylesheet" href="<?= e(asset('assets/css/reset.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('assets/css/styles.css')) ?>">
  <?php foreach ($styles as $style): ?>
  <link rel="stylesheet" href="<?= e(asset('assets/css/' . $style)) ?>">
  <?php endforeach; ?>
  <link rel="stylesheet" href="<?= e(asset('assets/css/responsive.css')) ?>">
</head>
<body<?= $bodyClass !== '' ? ' class="' . e($bodyClass) . '"' : '' ?>>

  <a class="skip-link" href="#contenido">Saltar al contenido</a>

  <?php view('partials/nav', ['isHome' => $isHome]); ?>

  <?php view('partials/flash'); ?>

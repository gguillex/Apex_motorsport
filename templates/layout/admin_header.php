<?php
/**
 * Cabecera del panel de administración.
 *
 * @var string $title   Título de la sección.
 * @var string $section Sección activa del menú: "coches" | "eventos" | "usuarios".
 */
use App\Auth;
use App\Csrf;

$section ??= '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex">
  <title><?= e($title) ?> — Panel APEX Motorsport</title>
  <link rel="stylesheet" href="<?= e(asset('assets/css/reset.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('assets/css/styles.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('assets/css/forms.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('assets/css/admin.css')) ?>">
</head>
<body class="admin-page">

<main class="admin-main" id="contenido">
  <header class="admin-header">
    <h1><?= e($title) ?></h1>
    <nav class="admin-nav" aria-label="Menú de administración">
      <a href="<?= e(url('cuenta.php')) ?>" class="usuario-info" title="Mi cuenta">Hola, <strong><?= e(Auth::user()['usuario']) ?></strong></a>
      <a href="<?= e(url('admin/')) ?>" class="btn btn--outline btn--sm<?= $section === 'coches' ? ' is-active' : '' ?>">Coches</a>
      <a href="<?= e(url('admin/eventos.php')) ?>" class="btn btn--outline btn--sm<?= $section === 'eventos' ? ' is-active' : '' ?>">Eventos</a>
      <a href="<?= e(url('admin/usuarios.php')) ?>" class="btn btn--outline btn--sm<?= $section === 'usuarios' ? ' is-active' : '' ?>">Usuarios</a>
      <a href="<?= e(url('index.php')) ?>" class="btn btn--outline btn--sm">Ver web</a>
      <form action="<?= e(url('logout.php')) ?>" method="post" class="inline-form">
        <?= Csrf::field() ?>
        <button type="submit" class="btn btn--danger btn--sm">Salir</button>
      </form>
    </nav>
  </header>

  <?php view('partials/flash'); ?>

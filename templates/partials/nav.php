<?php
/** @var bool $isHome */
use App\Auth;
use App\Csrf;

$home = $isHome ? '' : url('index.php');
$user = Auth::user();
?>
<nav class="nav-main" aria-label="Navegación principal">
  <ul class="nav-list" id="nav-list">
    <li class="nav-item"><a href="<?= e($home) ?>#inicio" class="nav-link">Inicio</a></li>
    <li class="nav-item"><a href="<?= e($home) ?>#catalogo" class="nav-link">Catálogo</a></li>
    <li class="nav-item"><a href="<?= e(url('comparar.php')) ?>" class="nav-link">Comparar</a></li>
    <li class="nav-item"><a href="<?= e($home) ?>#eventos" class="nav-link">Eventos</a></li>
    <li class="nav-item"><a href="<?= e($home) ?>#concesionarios" class="nav-link">Concesionarios</a></li>
    <li class="nav-item"><a href="<?= e($home) ?>#contacto" class="nav-link">Contacto</a></li>

    <?php if ($user === null): ?>
      <li class="nav-item nav-item--spaced">
        <a href="<?= e(url('login.php')) ?>" class="nav-link nav-link--accent">Iniciar sesión</a>
      </li>
      <li class="nav-item">
        <a href="<?= e(url('registro.php')) ?>" class="nav-link">Registrarse</a>
      </li>
    <?php else: ?>
      <li class="nav-item nav-item--spaced">
        <a href="<?= e(url('cuenta.php')) ?>" class="nav-link nav-link--accent" title="Mi cuenta">
          <?= e($user['usuario']) ?>
        </a>
      </li>
      <li class="nav-item"><a href="<?= e(url('mis_compras.php')) ?>" class="nav-link">Mis compras</a></li>
      <?php if (Auth::isAdmin()): ?>
        <li class="nav-item"><a href="<?= e(url('admin/')) ?>" class="nav-link">Panel admin</a></li>
      <?php endif; ?>
      <li class="nav-item">
        <form action="<?= e(url('logout.php')) ?>" method="post" class="inline-form">
          <?= Csrf::field() ?>
          <button type="submit" class="nav-link nav-link--button nav-link--danger">Salir</button>
        </form>
      </li>
    <?php endif; ?>
  </ul>

  <button type="button" class="nav-toggle" aria-controls="nav-list" aria-expanded="false" aria-label="Abrir menú">
    <span class="nav-toggle__line"></span>
    <span class="nav-toggle__line"></span>
    <span class="nav-toggle__line"></span>
  </button>
</nav>

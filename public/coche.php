<?php
/**
 * Ficha de un vehículo.
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use App\Auth;
use App\Csrf;
use App\Repository\CarRepository;

$car = (new CarRepository(db()))->find(query_id());
if ($car === null) {
    render_error(404, 'El vehículo que buscas no existe o ya no está disponible.');
}

$stock = (int) $car['stock'];
$stockClass = match (true) {
    $stock === 0 => 'stock-out',
    $stock <= 2  => 'stock-low',
    default      => 'stock-ok',
};

view('layout/header', ['title' => car_name($car), 'styles' => ['coche.css']]);
?>

  <main class="page car-detail" id="contenido">
    <a href="<?= e(url('index.php')) ?>#catalogo" class="btn btn--outline car-detail__back">&larr; Volver al catálogo</a>

    <article class="car-detail__panel">
      <figure>
        <img src="<?= e(url($car['imagen'])) ?>" alt="<?= e(car_name($car)) ?>" class="car-detail__img">
      </figure>

      <section>
        <p class="car-detail__brand"><?= e($car['marca']) ?></p>
        <h1 class="car-detail__model"><?= e($car['modelo']) ?></h1>

        <dl class="car-detail__specs">
          <div><dt>Año</dt><dd><?= (int) $car['anio'] ?></dd></div>
          <div><dt>Motor</dt><dd><?= e($car['motor']) ?></dd></div>
          <?php if ((int) $car['potencia'] > 0): ?>
            <div><dt>Potencia</dt><dd><?= (int) $car['potencia'] ?> CV</dd></div>
          <?php endif; ?>
          <?php if ((float) $car['aceleracion'] > 0): ?>
            <div><dt>0–100 km/h</dt><dd><?= e(number_format((float) $car['aceleracion'], 1, ',', '')) ?> s</dd></div>
          <?php endif; ?>
          <?php if ((int) $car['velocidad'] > 0): ?>
            <div><dt>Velocidad máx.</dt><dd><?= (int) $car['velocidad'] ?> km/h</dd></div>
          <?php endif; ?>
        </dl>

        <p class="car-detail__price"><?= e(format_price($car['precio'])) ?></p>

        <p class="car-detail__stock <?= $stockClass ?>">
          <?php if ($stock === 0): ?>
            Agotado — sin unidades disponibles
          <?php elseif ($stock <= 2): ?>
            ¡Últimas <?= $stock ?> unidad<?= $stock === 1 ? '' : 'es' ?>!
          <?php else: ?>
            Stock disponible: <?= $stock ?> unidades
          <?php endif; ?>
        </p>

        <?php if (!Auth::check()): ?>
          <a href="<?= e(url('login.php')) ?>" class="btn btn--primary btn--block">Inicia sesión para comprar</a>
        <?php elseif ($stock > 0): ?>
          <form action="<?= e(url('comprar.php')) ?>" method="post"
                data-confirm="¿Confirmas la compra del <?= e(car_name($car)) ?> por <?= e(format_price($car['precio'])) ?>?">
            <?= Csrf::field() ?>
            <input type="hidden" name="id" value="<?= (int) $car['id'] ?>">
            <button type="submit" class="btn btn--primary btn--block">Comprar ahora</button>
          </form>
        <?php else: ?>
          <button type="button" class="btn btn--primary btn--block" disabled>Agotado</button>
        <?php endif; ?>

        <a href="<?= e(url('comparar.php?id=' . (int) $car['id'])) ?>" class="btn btn--outline btn--block car-detail__compare">Comparar con otro modelo</a>
      </section>
    </article>
  </main>

<?php view('layout/footer');

<?php
/**
 * Comparador cara a cara de dos vehículos.
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use App\Repository\CarRepository;

$repo = new CarRepository(db());
$allCars = $repo->all();

$selected = [];
foreach (['id', 'id2'] as $param) {
    $id = query_id($param);
    $selected[$param] = $id > 0 ? $repo->find($id) : null;
}

/**
 * Filas de la comparativa: etiqueta, campo, formateador y criterio de "mejor"
 * ('max' = gana el valor más alto, 'min' = el más bajo, null = sin ganador).
 */
$rows = [
    ['Año',            'anio',        static fn ($c) => (string) (int) $c['anio'],                                    'max'],
    ['Motor',          'motor',       static fn ($c) => $c['motor'],                                                  null],
    ['Potencia',       'potencia',    static fn ($c) => (int) $c['potencia'] . ' CV',                                  'max'],
    ['0–100 km/h',     'aceleracion', static fn ($c) => number_format((float) $c['aceleracion'], 1, ',', '') . ' s',   'min'],
    ['Velocidad máx.', 'velocidad',   static fn ($c) => (int) $c['velocidad'] . ' km/h',                               'max'],
    ['Precio',         'precio',      static fn ($c) => format_price($c['precio']),                                    'min'],
];

$bothSelected = $selected['id'] !== null && $selected['id2'] !== null;

/** Devuelve true si $car gana en el campo indicado frente al otro coche. */
$isBetter = static function (array $car, array $other, string $field, ?string $rule): bool {
    if ($rule === null || (float) $car[$field] <= 0 || (float) $other[$field] <= 0) {
        return false;
    }
    return $rule === 'max' ? $car[$field] > $other[$field] : $car[$field] < $other[$field];
};

view('layout/header', ['title' => 'Comparador', 'styles' => ['comparar.css']]);
?>

  <main class="page compare-container" id="contenido">
    <header class="page-header">
      <h1 class="page-title">Cara a cara</h1>
      <p class="section-subtitle">Elige dos modelos y compara sus prestaciones. Los mejores valores aparecen destacados.</p>
    </header>

    <form action="<?= e(url('comparar.php')) ?>" method="get" class="compare-grid compare-form" data-autosubmit>
      <?php foreach (['id' => 'primer', 'id2' => 'segundo'] as $param => $ordinal):
          $car = $selected[$param];
          $other = $selected[$param === 'id' ? 'id2' : 'id']; ?>
        <article class="compare-col">
          <label for="select-<?= $param ?>" class="sr-only">Selecciona el <?= $ordinal ?> vehículo</label>
          <select name="<?= $param ?>" id="select-<?= $param ?>" class="selector">
            <option value="">Selecciona el <?= $ordinal ?> vehículo…</option>
            <?php foreach ($allCars as $option): ?>
              <option value="<?= (int) $option['id'] ?>"<?= $car && (int) $car['id'] === (int) $option['id'] ? ' selected' : '' ?>>
                <?= e(car_name($option)) ?>
              </option>
            <?php endforeach; ?>
          </select>

          <?php if ($car): ?>
            <img src="<?= e(url($car['imagen'])) ?>" alt="<?= e(car_name($car)) ?>" class="compare-img">
            <h2 class="compare-title"><?= e(car_name($car)) ?></h2>
            <dl class="compare-specs">
              <?php foreach ($rows as [$label, $field, $format, $rule]): ?>
                <div class="<?= $bothSelected && $isBetter($car, $other, $field, $rule) ? 'is-better' : '' ?>">
                  <dt><?= e($label) ?></dt>
                  <dd><?= e($format($car)) ?></dd>
                </div>
              <?php endforeach; ?>
            </dl>
            <a href="<?= e(url('coche.php?id=' . (int) $car['id'])) ?>" class="btn btn--primary btn--block compare-cta">Ver ficha y comprar</a>
          <?php else: ?>
            <p class="compare-placeholder">Ningún vehículo seleccionado.</p>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>

      <noscript>
        <p class="compare-submit"><button type="submit" class="btn btn--primary">Comparar</button></p>
      </noscript>
    </form>
  </main>

<?php view('layout/footer');

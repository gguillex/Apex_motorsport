<?php
/**
 * Portada: buscador, catálogo (cargado por AJAX desde api/coches.php),
 * calendario de eventos, concesionarios y enlaces de interés.
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use App\Repository\CarRepository;

$cars   = new CarRepository(db());
$brands = $cars->brands();
$years  = $cars->years();

$priceOptions = [200000, 500000, 1000000, 3000000];

$links = [
    ['https://www.motor1.com',     'Motor1',          'Noticias y análisis del sector'],
    ['https://www.topgear.com',    'Top Gear',        'La biblia del automovilismo mundial'],
    ['https://www.autoblog.com',   'Autoblog',        'Reseñas y tendencias globales'],
    ['https://www.fia.com',        'FIA',             'Federación Internacional del Automóvil'],
    ['https://www.lemans.org',     'Le Mans Oficial', 'La carrera de resistencia más célebre'],
    ['https://www.formula1.com',   'Formula 1',       'Campeonato Mundial de F1 oficial'],
];

view('layout/header', [
    'title'       => 'Supercars de Alta Gama',
    'isHome'      => true,
    'description' => 'APEX Motorsport — Exclusividad en movimiento. Compra y venta de supercars de alta gama: Ferrari, Lamborghini, Porsche, McLaren y más.',
]);
?>

  <header class="site-header" id="inicio">
    <canvas id="logo" width="240" height="240" role="img" aria-label="Logotipo de APEX Motorsport"></canvas>
    <p class="logo-text">APEX Motorsport</p>
    <p class="tagline">Exclusividad en movimiento &mdash; supercars de alta gama para quienes exigen lo mejor</p>
  </header>

  <main id="contenido">

    <section id="busqueda" class="section-search" aria-labelledby="search-heading">
      <header class="section-header">
        <h1 id="search-heading">Encuentra tu supercar ideal</h1>
        <p class="section-subtitle">Explora nuestra selección exclusiva de los mejores fabricantes del mundo</p>
      </header>

      <form class="search-form" action="#catalogo" method="get" role="search">
        <div class="search-main-field">
          <label for="search-input" class="sr-only">Buscar por marca o modelo</label>
          <input type="search" id="search-input" name="q" class="search-input"
                 placeholder="Busca tu supercar: marca, modelo…" autocomplete="off">
          <button type="submit" class="btn btn--primary search-btn">Buscar</button>
        </div>

        <fieldset class="search-filters">
          <legend class="search-filters__legend">Filtros de búsqueda</legend>

          <label for="filter-brand" class="filter-label">Marca</label>
          <select id="filter-brand" name="brand" class="filter-select">
            <option value="">Todas las marcas</option>
            <?php foreach ($brands as $brand): ?>
              <option value="<?= e(mb_strtolower($brand)) ?>"><?= e($brand) ?></option>
            <?php endforeach; ?>
          </select>

          <label for="filter-price" class="filter-label">Precio máximo</label>
          <select id="filter-price" name="price" class="filter-select">
            <option value="">Sin límite</option>
            <?php foreach ($priceOptions as $price): ?>
              <option value="<?= $price ?>">Hasta <?= e(format_price($price)) ?></option>
            <?php endforeach; ?>
          </select>

          <label for="filter-year" class="filter-label">Año</label>
          <select id="filter-year" name="year" class="filter-select">
            <option value="">Todos los años</option>
            <?php foreach ($years as $year): ?>
              <option value="<?= $year ?>"><?= $year ?></option>
            <?php endforeach; ?>
          </select>
        </fieldset>
      </form>
    </section>

    <section id="catalogo" class="section-catalog" aria-labelledby="catalog-heading">
      <header class="section-header">
        <h2 id="catalog-heading">Catálogo exclusivo</h2>
        <p class="section-subtitle">Una selección de los supercars más extraordinarios disponibles en el mercado</p>
      </header>

      <p id="search-results-count" class="sr-only" aria-live="polite" aria-atomic="true"></p>

      <div class="catalog-grid" id="catalog-grid" data-api="<?= e(url('api/coches.php')) ?>" data-base="<?= e(url()) ?>" aria-busy="true">
        <p class="catalog-status">Cargando catálogo…</p>
      </div>
      <p class="catalog-status" id="catalog-empty" hidden>No hay vehículos que coincidan con tu búsqueda.</p>

      <noscript>
        <p class="catalog-status">Activa JavaScript para ver el catálogo interactivo.</p>
      </noscript>
    </section>

    <section id="eventos" class="section-events" aria-labelledby="events-heading">
      <header class="section-header">
        <h2 id="events-heading">Próximos eventos</h2>
        <p class="section-subtitle">El mundo del motor en tu agenda &mdash; selecciona un día marcado para ver los detalles</p>
      </header>

      <div class="events-layout">
        <section class="calendar-wrapper" aria-label="Calendario de eventos" data-api="<?= e(url('api/eventos.php')) ?>">
          <header class="calendar-nav">
            <button type="button" id="cal-prev" class="cal-btn" aria-label="Mes anterior">&#8249;</button>
            <h3 id="cal-month-year" class="calendar-title" aria-live="polite"></h3>
            <button type="button" id="cal-next" class="cal-btn" aria-label="Mes siguiente">&#8250;</button>
          </header>

          <table class="calendar-table">
            <thead>
              <tr>
                <th scope="col" abbr="Lunes">Lun</th>
                <th scope="col" abbr="Martes">Mar</th>
                <th scope="col" abbr="Miércoles">Mié</th>
                <th scope="col" abbr="Jueves">Jue</th>
                <th scope="col" abbr="Viernes">Vie</th>
                <th scope="col" abbr="Sábado">Sáb</th>
                <th scope="col" abbr="Domingo">Dom</th>
              </tr>
            </thead>
            <tbody id="cal-body"></tbody>
          </table>
        </section>

        <aside class="event-panel" aria-live="polite">
          <p id="event-panel-default" class="event-panel__placeholder">
            Haz clic en un día <strong>resaltado</strong> en el calendario para ver los detalles del evento.
          </p>
          <span id="event-panel-label" class="event-panel__label" hidden>Evento del día</span>
          <h4 id="event-panel-name" class="event-panel__name" hidden></h4>
          <p id="event-panel-desc" class="event-panel__desc" hidden></p>
          <p id="event-panel-date" class="event-panel__date" hidden></p>
        </aside>
      </div>
    </section>

    <section id="concesionarios" class="section-dealers" aria-labelledby="dealers-heading">
      <header class="section-header">
        <h2 id="dealers-heading">Encuentra tu concesionario más cercano</h2>
        <p class="section-subtitle">Visítanos y vive la experiencia APEX Motorsport en persona</p>
      </header>

      <div class="dealers-content">
        <button type="button" id="btn-geolocate" class="btn btn--primary btn--geo">
          <span class="btn-icon" aria-hidden="true">&#x2295;</span>
          <span class="btn-label">Encontrar concesionarios cercanos</span>
        </button>

        <p id="geo-error" class="alert alert--error" role="alert" hidden></p>

        <section id="geo-result" class="geo-result" hidden>
          <p id="geo-intro" class="geo-intro"></p>
          <ul id="dealers-list" class="dealers-list"></ul>
        </section>
      </div>
    </section>

    <aside class="links-aside" aria-labelledby="aside-heading">
      <header>
        <h2 id="aside-heading" class="aside-title">El mundo del motor</h2>
        <p class="aside-subtitle">Fuentes de referencia del sector automovilístico de alta gama</p>
      </header>

      <nav aria-label="Enlaces externos">
        <ul class="links-list">
          <?php foreach ($links as [$href, $name, $desc]): ?>
            <li>
              <a href="<?= e($href) ?>" target="_blank" rel="noopener noreferrer" class="link-item">
                <span class="link-item__name"><?= e($name) ?></span>
                <span class="link-item__desc"><?= e($desc) ?></span>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </nav>
    </aside>

  </main>

<?php view('layout/footer', [
    'jquery'  => true,
    'scripts' => ['logo.js', 'catalog.js', 'calendar.js', 'geolocation.js'],
]);

/**
 * Catálogo de la portada: carga los coches desde la API mediante AJAX (jQuery)
 * y aplica los filtros del buscador en el cliente.
 */
(function ($) {
  'use strict';

  const grid = document.getElementById('catalog-grid');
  if (!grid || !$) return;

  const baseUrl = grid.dataset.base || '';
  const emptyMsg = document.getElementById('catalog-empty');
  const announcer = document.getElementById('search-results-count');
  const form = document.querySelector('.search-form');
  const inputs = {
    query: document.getElementById('search-input'),
    brand: document.getElementById('filter-brand'),
    price: document.getElementById('filter-price'),
    year: document.getElementById('filter-year')
  };

  const priceFormat = new Intl.NumberFormat('es-ES', { maximumFractionDigits: 0 });
  let cars = [];

  /** Crea un elemento con clase y texto (el texto nunca se interpreta como HTML). */
  function el(tag, className, text) {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (text !== undefined) node.textContent = text;
    return node;
  }

  function stockLabel(stock) {
    if (stock === 0) return 'Agotado';
    if (stock <= 2) return 'Últimas unidades';
    return 'Disponible';
  }

  function specsText(car) {
    const parts = [car.motor];
    if (car.potencia > 0) parts.push(`${car.potencia} CV`);
    if (car.aceleracion > 0) parts.push(`0–100 en ${String(car.aceleracion).replace('.', ',')} s`);
    if (car.velocidad > 0) parts.push(`${car.velocidad} km/h`);
    return parts.join(' · ');
  }

  function buildCard(car) {
    const article = el('article', 'car-card');
    article.dataset.id = car.id;

    const figure = el('figure', 'car-card__figure');
    const img = el('img', 'car-card__image');
    img.src = baseUrl + car.imagen;
    img.alt = `${car.marca} ${car.modelo}`;
    img.loading = 'lazy';
    figure.append(img, el('figcaption', 'car-card__caption' + (car.stock === 0 ? ' car-card__caption--out' : ''), stockLabel(car.stock)));

    const header = el('header', 'car-card__header');
    header.append(el('span', 'car-card__brand', car.marca), el('h3', 'car-card__model', car.modelo));

    const footer = el('footer', 'car-card__footer car-card__footer--actions');
    const compare = el('a', 'btn btn--outline btn--compact', 'Comparar');
    compare.href = `${baseUrl}comparar.php?id=${encodeURIComponent(car.id)}`;
    const detail = el('a', 'btn btn--primary btn--compact', car.stock === 0 ? 'Ver ficha' : 'Comprar');
    detail.href = `${baseUrl}coche.php?id=${encodeURIComponent(car.id)}`;
    footer.append(compare, detail);

    article.append(
      figure,
      header,
      el('p', 'car-card__specs', specsText(car)),
      el('p', 'car-card__price', `${priceFormat.format(car.precio)} €`),
      footer
    );
    return article;
  }

  function currentFilters() {
    return {
      query: inputs.query ? inputs.query.value.trim().toLowerCase() : '',
      brand: inputs.brand ? inputs.brand.value : '',
      maxPrice: inputs.price && inputs.price.value ? Number(inputs.price.value) : Infinity,
      year: inputs.year && inputs.year.value ? Number(inputs.year.value) : 0
    };
  }

  function matches(car, f) {
    const brand = car.marca.toLowerCase();
    const haystack = `${brand} ${car.modelo.toLowerCase()} ${car.anio}`;
    return (!f.query || haystack.includes(f.query))
      && (!f.brand || brand === f.brand)
      && car.precio <= f.maxPrice
      && (!f.year || car.anio === f.year);
  }

  function render() {
    const filters = currentFilters();
    const visible = cars.filter((car) => matches(car, filters));

    grid.replaceChildren(...visible.map(buildCard));
    if (emptyMsg) emptyMsg.hidden = visible.length > 0;
    if (announcer) {
      const n = visible.length;
      announcer.textContent = `${n} vehículo${n !== 1 ? 's' : ''} encontrado${n !== 1 ? 's' : ''}.`;
    }
  }

  function showError(message) {
    grid.replaceChildren(el('p', 'catalog-status catalog-status--error', message));
  }

  $.ajax({ url: grid.dataset.api, method: 'GET', dataType: 'json' })
    .done((data) => {
      cars = Array.isArray(data) ? data : [];
      render();
    })
    .fail(() => showError('No se ha podido cargar el catálogo. Inténtalo de nuevo más tarde.'))
    .always(() => grid.setAttribute('aria-busy', 'false'));

  if (form) {
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      render();
      document.getElementById('catalogo').scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  }

  Object.values(inputs).forEach((input) => {
    if (input) input.addEventListener(input.tagName === 'SELECT' ? 'change' : 'input', render);
  });
})(window.jQuery);

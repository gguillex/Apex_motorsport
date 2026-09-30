/**
 * Concesionarios: obtiene la ubicación del usuario (Geolocation API) y muestra
 * los concesionarios ordenados por distancia (fórmula del haversine).
 */
(function () {
  'use strict';

  const DEALERS = [
    { name: 'APEX Motorsport Madrid',    address: 'Paseo de la Castellana, 259 · 28046 Madrid',        lat: 40.4654, lon: -3.6882 },
    { name: 'APEX Motorsport Barcelona', address: 'Av. Diagonal, 640 · 08017 Barcelona',               lat: 41.3966, lon:  2.1362 },
    { name: 'APEX Motorsport Marbella',  address: 'Puerto Banús, Local 12 · 29660 Marbella, Málaga',   lat: 36.4913, lon: -4.9598 }
  ];

  const ERROR_MESSAGES = {
    1: 'Has denegado el permiso de ubicación. Actívalo en tu navegador para ver los concesionarios cercanos.',
    2: 'No se ha podido determinar tu ubicación.',
    3: 'Se ha agotado el tiempo de espera para obtener la ubicación.'
  };

  const button = document.getElementById('btn-geolocate');
  if (!button) return;

  const label = button.querySelector('.btn-label');
  const result = document.getElementById('geo-result');
  const intro = document.getElementById('geo-intro');
  const list = document.getElementById('dealers-list');
  const errorBox = document.getElementById('geo-error');

  /** Distancia en km entre dos coordenadas. */
  function haversine(lat1, lon1, lat2, lon2) {
    const R = 6371;
    const rad = Math.PI / 180;
    const dLat = (lat2 - lat1) * rad;
    const dLon = (lon2 - lon1) * rad;
    const a = Math.sin(dLat / 2) ** 2 +
              Math.cos(lat1 * rad) * Math.cos(lat2 * rad) * Math.sin(dLon / 2) ** 2;
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  }

  function formatDistance(km) {
    return km < 1 ? `${Math.round(km * 1000)} m` : `${Math.round(km).toLocaleString('es-ES')} km`;
  }

  function span(className, text) {
    const node = document.createElement('span');
    node.className = className;
    node.textContent = text;
    return node;
  }

  function setBusy(busy, text) {
    button.disabled = busy;
    label.textContent = text;
  }

  function showError(message) {
    errorBox.textContent = message;
    errorBox.hidden = false;
  }

  function showDealers(position) {
    const { latitude, longitude } = position.coords;

    const sorted = DEALERS
      .map((d) => ({ ...d, dist: haversine(latitude, longitude, d.lat, d.lon) }))
      .sort((a, b) => a.dist - b.dist);

    intro.textContent = `Tu ubicación: ${latitude.toFixed(5)}, ${longitude.toFixed(5)}`;
    list.replaceChildren(...sorted.map((d) => {
      const li = document.createElement('li');
      const address = document.createElement('address');
      address.append(
        span('dealer-name', d.name),
        span('dealer-address', d.address),
        span('dealer-distance', `A ${formatDistance(d.dist)} de tu ubicación`)
      );
      li.append(address);
      return li;
    }));

    result.hidden = false;
    setBusy(false, 'Actualizar ubicación');
  }

  button.addEventListener('click', () => {
    errorBox.hidden = true;

    if (!('geolocation' in navigator)) {
      showError('Tu navegador no permite la geolocalización.');
      return;
    }

    setBusy(true, 'Localizando…');
    navigator.geolocation.getCurrentPosition(showDealers, (error) => {
      setBusy(false, 'Encontrar concesionarios cercanos');
      showError(ERROR_MESSAGES[error.code] || 'Se ha producido un error al obtener tu ubicación.');
    }, { timeout: 10000, maximumAge: 300000 });
  });
})();

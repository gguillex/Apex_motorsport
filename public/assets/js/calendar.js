/**
 * Calendario de eventos de la portada. Los eventos se gestionan desde el panel
 * de administración y se obtienen de la API al cargar la página.
 */
(function () {
  'use strict';

  const wrapper = document.querySelector('.calendar-wrapper');
  if (!wrapper) return;

  // Eventos indexados por fecha "AAAA-MM-DD"; se cargan desde api/eventos.php.
  let EVENTS = {};

  const MONTHS = [
    'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
  ];

  const today = new Date();
  let viewYear  = today.getFullYear();
  let viewMonth = today.getMonth();

  const calBody      = document.getElementById('cal-body');
  const calTitle     = document.getElementById('cal-month-year');
  const btnPrev      = document.getElementById('cal-prev');
  const btnNext      = document.getElementById('cal-next');
  const panelLabel   = document.getElementById('event-panel-label');
  const panelName    = document.getElementById('event-panel-name');
  const panelDesc    = document.getElementById('event-panel-desc');
  const panelDate    = document.getElementById('event-panel-date');
  const panelDefault = document.getElementById('event-panel-default');

  function toKey(year, month, day) {
    const mm = String(month + 1).padStart(2, '0');
    const dd = String(day).padStart(2, '0');
    return `${year}-${mm}-${dd}`;
  }

  function formatDate(key) {
    const [y, m, d] = key.split('-');
    return `${parseInt(d)} de ${MONTHS[parseInt(m) - 1]} de ${y}`;
  }

  function renderCalendar(year, month) {
    calTitle.textContent = `${MONTHS[month]} ${year}`;
    calBody.innerHTML = '';

    const firstWeekday = new Date(year, month, 1).getDay();
    const startOffset = (firstWeekday === 0) ? 6 : firstWeekday - 1;

    const daysInMonth     = new Date(year, month + 1, 0).getDate();
    const daysInPrevMonth = new Date(year, month,     0).getDate();

    const totalCells = Math.ceil((startOffset + daysInMonth) / 7) * 7;

    let currentDay   = 1;
    let nextMonthDay = 1;
    let row = null;

    for (let i = 0; i < totalCells; i++) {
      if (i % 7 === 0) {
        row = document.createElement('tr');
        calBody.appendChild(row);
      }

      const td = document.createElement('td');

      if (i < startOffset) {
        td.textContent = daysInPrevMonth - startOffset + i + 1;
        td.classList.add('other-month');
        td.setAttribute('aria-hidden', 'true');

      } else if (currentDay > daysInMonth) {
        td.textContent = nextMonthDay++;
        td.classList.add('other-month');
        td.setAttribute('aria-hidden', 'true');

      } else {
        td.textContent = currentDay;
        const key = toKey(year, month, currentDay);

        if (
          year  === today.getFullYear() &&
          month === today.getMonth()    &&
          currentDay === today.getDate()
        ) {
          td.classList.add('today');
        }

        if (EVENTS[key]) {
          td.classList.add('has-event');
          td.setAttribute('role', 'button');
          td.setAttribute('tabindex', '0');
          td.setAttribute('aria-label', `${currentDay} de ${MONTHS[month]}: ${EVENTS[key].name}`);
          td.dataset.key = key;

          td.addEventListener('click', () => handleEventSelect(key, td));
          td.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
              e.preventDefault();
              handleEventSelect(key, td);
            }
          });
        } else {
          td.setAttribute('aria-label', `${currentDay} de ${MONTHS[month]}`);
        }

        currentDay++;
      }

      row.appendChild(td);
    }
  }

  function handleEventSelect(key, cell) {
    calBody.querySelectorAll('.selected').forEach(el => el.classList.remove('selected'));
    cell.classList.add('selected');

    const ev = EVENTS[key];
    if (!ev) return;

    if (panelDefault) panelDefault.hidden = true;
    if (panelLabel)   { panelLabel.hidden   = false; }
    if (panelName)    { panelName.textContent  = ev.name;          panelName.hidden  = false; }
    if (panelDesc)    { panelDesc.textContent  = ev.desc;          panelDesc.hidden  = false; }
    if (panelDate)    { panelDate.textContent  = formatDate(key);  panelDate.hidden  = false; }
  }

  if (btnPrev) {
    btnPrev.addEventListener('click', () => {
      viewMonth--;
      if (viewMonth < 0) { viewMonth = 11; viewYear--; }
      renderCalendar(viewYear, viewMonth);
    });
  }

  if (btnNext) {
    btnNext.addEventListener('click', () => {
      viewMonth++;
      if (viewMonth > 11) { viewMonth = 0; viewYear++; }
      renderCalendar(viewYear, viewMonth);
    });
  }

  renderCalendar(viewYear, viewMonth);

  fetch(wrapper.dataset.api, { headers: { Accept: 'application/json' } })
    .then((res) => {
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      return res.json();
    })
    .then((data) => {
      EVENTS = data;
      renderCalendar(viewYear, viewMonth);
    })
    .catch(() => {
      if (panelDefault) panelDefault.textContent = 'No se han podido cargar los eventos. Inténtalo más tarde.';
    });
})();

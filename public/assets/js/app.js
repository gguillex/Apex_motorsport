/**
 * Comportamientos comunes a todas las páginas:
 *  - Menú de navegación responsive.
 *  - Confirmación de formularios con [data-confirm].
 *  - Envío automático de formularios con [data-autosubmit] al cambiar un <select>.
 *  - Botones de impresión [data-print].
 *  - Cierre automático de los mensajes flash.
 */
(function () {
  'use strict';

  /* ---------- Menú responsive ---------- */
  const navToggle = document.querySelector('.nav-toggle');
  const navList = document.getElementById('nav-list');

  if (navToggle && navList) {
    const setOpen = (open) => {
      navList.classList.toggle('nav-list--open', open);
      navToggle.setAttribute('aria-expanded', String(open));
      navToggle.setAttribute('aria-label', open ? 'Cerrar menú' : 'Abrir menú');
    };
    const isOpen = () => navList.classList.contains('nav-list--open');

    navToggle.addEventListener('click', () => setOpen(!isOpen()));

    navList.querySelectorAll('a.nav-link').forEach((link) => {
      link.addEventListener('click', () => setOpen(false));
    });

    document.addEventListener('click', (e) => {
      if (isOpen() && !navToggle.contains(e.target) && !navList.contains(e.target)) {
        setOpen(false);
      }
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && isOpen()) {
        setOpen(false);
        navToggle.focus();
      }
    });
  }

  /* ---------- Confirmaciones ---------- */
  document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (e) => {
      if (!window.confirm(form.dataset.confirm)) {
        e.preventDefault();
      }
    });
  });

  /* ---------- Envío automático ---------- */
  document.querySelectorAll('form[data-autosubmit]').forEach((form) => {
    form.querySelectorAll('select').forEach((select) => {
      select.addEventListener('change', () => form.submit());
    });
  });

  /* ---------- Imprimir ---------- */
  document.querySelectorAll('[data-print]').forEach((btn) => {
    btn.addEventListener('click', () => window.print());
  });

  /* ---------- Mensajes flash ---------- */
  document.querySelectorAll('.flash-stack .alert').forEach((alert) => {
    const dismiss = () => alert.classList.add('alert--hidden');
    alert.addEventListener('click', dismiss);
    window.setTimeout(dismiss, 6000);
  });
})();

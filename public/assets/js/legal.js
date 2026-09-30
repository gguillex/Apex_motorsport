/**
 * Pestañas de la página legal (patrón ARIA "tabs"), sincronizadas con el
 * fragmento de la URL (#privacidad, #cookies…).
 */
(function () {
  'use strict';

  const tabs = Array.from(document.querySelectorAll('.legal-tab'));
  if (!tabs.length) return;

  function activate(tab, { focus = false, updateHash = false } = {}) {
    tabs.forEach((t) => {
      const selected = t === tab;
      t.classList.toggle('active', selected);
      t.setAttribute('aria-selected', String(selected));
      t.tabIndex = selected ? 0 : -1;
      const panel = document.getElementById(t.getAttribute('aria-controls'));
      if (panel) panel.hidden = !selected;
    });
    if (focus) tab.focus();
    if (updateHash) history.replaceState(null, '', `#${tab.dataset.tab}`);
  }

  tabs.forEach((tab, index) => {
    tab.addEventListener('click', () => activate(tab, { updateHash: true }));

    // Navegación con flechas entre pestañas.
    tab.addEventListener('keydown', (e) => {
      const offsets = { ArrowRight: 1, ArrowLeft: -1 };
      if (e.key in offsets) {
        e.preventDefault();
        const next = tabs[(index + offsets[e.key] + tabs.length) % tabs.length];
        activate(next, { focus: true, updateHash: true });
      }
    });
  });

  function fromHash() {
    const key = window.location.hash.slice(1);
    const tab = tabs.find((t) => t.dataset.tab === key);
    if (tab) activate(tab);
  }

  fromHash();
  window.addEventListener('hashchange', fromHash);
})();

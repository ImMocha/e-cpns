/* ==========================================================================
   LONTAR · theme.js
   Dark mode for the app shell (admin + peserta). The initial theme is applied
   by an inline script in layouts/base.blade.php (before first paint, so there
   is no flash); this module only handles the [data-theme-toggle] buttons and
   keeps every copy of them in sync.

   Preference is stored per-browser in localStorage under `theme`.
   ========================================================================== */
(function () {
  'use strict';

  const KEY = 'theme';
  const root = document.documentElement;

  function stored() {
    try { return localStorage.getItem(KEY); } catch (err) { return null; }
  }

  function current() {
    return root.dataset.theme === 'dark' ? 'dark' : 'light';
  }

  function syncButtons() {
    const dark = current() === 'dark';
    document.querySelectorAll('[data-theme-toggle]').forEach((btn) => {
      btn.setAttribute('aria-pressed', String(dark));
      const label = dark
        ? (btn.dataset.labelLight || 'Mode terang')
        : (btn.dataset.labelDark || 'Mode gelap');
      btn.setAttribute('aria-label', label);
      btn.setAttribute('title', label);
    });
  }

  function refreshCharts() {
    if (window.LCharts && window.LCharts.refresh) window.LCharts.refresh();
  }

  function apply(theme) {
    root.dataset.theme = theme;
    syncButtons();
    refreshCharts();
  }

  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-theme-toggle]');
    if (!btn) return;
    const next = current() === 'dark' ? 'light' : 'dark';
    try { localStorage.setItem(KEY, next); } catch (err) { /* storage blocked */ }
    apply(next);
  });

  /* The inline head script may have already set the attribute; if the page
     was cached with a stale value (or none), reconcile it here. */
  const saved = stored();
  if (saved === 'light' || saved === 'dark') {
    if (root.dataset.theme !== saved) apply(saved);
    else syncButtons();
  } else {
    syncButtons();
  }

  window.LTheme = { current: current, set: apply };
})();

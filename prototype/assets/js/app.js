/* ==========================================================================
   LONTAR · app.js
   Shared behaviour for every prototype page. No dependencies, no build step.
   Exposes a tiny global namespace: window.L = { toast, modal, confirmAction }
   ========================================================================== */
(function () {
  'use strict';

  const $  = (sel, ctx = document) => ctx.querySelector(sel);
  const $$ = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));

  /* ======================================================================
     TOAST
     ====================================================================== */
  function toastHost() {
    let host = $('.toast-host');
    if (!host) {
      host = document.createElement('div');
      host.className = 'toast-host';
      host.setAttribute('role', 'status');
      host.setAttribute('aria-live', 'polite');
      document.body.appendChild(host);
    }
    return host;
  }

  const TOAST_ICON = {
    success: '<path d="M20 6 9 17l-5-5"/>',
    error:   '<path d="M18 6 6 18M6 6l12 12"/>',
    warn:    '<path d="M12 9v4M12 17h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/>',
    info:    '<path d="M12 16v-4M12 8h.01"/><circle cx="12" cy="12" r="10"/>'
  };

  window.toast = function (message, type = 'info', ms = 3200) {
    const host = toastHost();
    const el = document.createElement('div');
    el.className = 'toast toast--' + type;
    el.innerHTML =
      '<span class="toast__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" ' +
      'stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">' +
      (TOAST_ICON[type] || TOAST_ICON.info) + '</svg></span><span></span>';
    el.lastElementChild.textContent = message;
    host.appendChild(el);
    setTimeout(() => {
      el.classList.add('is-out');
      setTimeout(() => el.remove(), 260);
    }, ms);
  };

  /* ======================================================================
     MODAL
     ====================================================================== */
  let lastFocused = null;

  window.openModal = function (idOrEl) {
    const modal = typeof idOrEl === 'string' ? document.getElementById(idOrEl) : idOrEl;
    if (!modal) return;
    lastFocused = document.activeElement;
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    const focusable = modal.querySelector('[autofocus], button, input, select, textarea, a[href]');
    if (focusable) setTimeout(() => focusable.focus(), 40);
  };

  window.closeModal = function (idOrEl) {
    const modal = typeof idOrEl === 'string' ? document.getElementById(idOrEl) : idOrEl;
    if (!modal) return;
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    if (!$('.modal.is-open')) document.body.style.overflow = '';
    if (lastFocused && lastFocused.focus) lastFocused.focus();
  };

  document.addEventListener('click', (e) => {
    const open = e.target.closest('[data-modal-open]');
    if (open) { e.preventDefault(); window.openModal(open.dataset.modalOpen); return; }

    const close = e.target.closest('[data-modal-close]');
    if (close) { e.preventDefault(); window.closeModal(close.closest('.modal') || close.dataset.modalClose); return; }

    if (e.target.classList.contains('modal__backdrop')) {
      window.closeModal(e.target.closest('.modal'));
    }
  });

  document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    const open = $('.modal.is-open');
    if (open) { window.closeModal(open); return; }
    const mobile = $('[data-nav-mobile].is-open');
    if (mobile) mobile.classList.remove('is-open');
    const drawer = $('.side.is-open');
    if (drawer) closeDrawer();
  });

  /* Confirm dialog ------------------------------------------------------ */
  window.confirmAction = function (opts) {
    const o = Object.assign(
      { title: 'Konfirmasi', body: 'Lanjutkan aksi ini?', ok: 'Ya, lanjutkan', cancel: 'Batal', tone: 'primary', icon: 'help' },
      opts || {}
    );
    const wrap = document.createElement('div');
    wrap.className = 'modal is-open';
    wrap.setAttribute('aria-hidden', 'false');
    wrap.setAttribute('role', 'dialog');
    wrap.innerHTML =
      '<div class="modal__backdrop"></div>' +
      '<div class="modal__panel">' +
        '<button class="modal__close" type="button" aria-label="Tutup"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg></button>' +
        '<div class="card__icon ico--' + o.tone + '" style="position:static;margin-bottom:1rem">' +
          '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.1 9a3 3 0 0 1 5.8 1c0 2-3 3-3 3M12 17h.01"/></svg>' +
        '</div>' +
        '<h3 class="modal__title"></h3>' +
        '<p class="text-soft" style="margin-top:.6rem"></p>' +
        '<div class="modal__foot">' +
          '<button class="btn btn--ghost" data-act="cancel"></button>' +
          '<button class="btn btn--' + o.tone + '" data-act="ok"></button>' +
        '</div>' +
      '</div>';
    $('h3', wrap).textContent = o.title;
    $('.modal__panel p', wrap).textContent = o.body;
    $('[data-act="cancel"]', wrap).textContent = o.cancel;
    $('[data-act="ok"]', wrap).textContent = o.ok;
    document.body.appendChild(wrap);
    document.body.style.overflow = 'hidden';
    $('[data-act="ok"]', wrap).focus();

    const done = () => { wrap.remove(); document.body.style.overflow = ''; };
    $('[data-act="cancel"]', wrap).addEventListener('click', done);
    $('.modal__backdrop', wrap).addEventListener('click', done);
    $('.modal__close', wrap).addEventListener('click', done);
    $('[data-act="ok"]', wrap).addEventListener('click', () => { done(); if (o.onOk) o.onOk(); });
    if (window.LIcons && window.LIcons.hydrate) window.LIcons.hydrate(wrap);
  };

  /* ======================================================================
     MOBILE NAV + OFF-CANVAS DRAWER
     ====================================================================== */
  document.addEventListener('click', (e) => {
    const navToggle = e.target.closest('[data-nav-toggle]');
    if (navToggle) {
      const panel = document.getElementById(navToggle.dataset.navToggle);
      if (panel) panel.classList.toggle('is-open');
      navToggle.setAttribute('aria-expanded', panel ? panel.classList.contains('is-open') : 'false');
      return;
    }
    if (e.target.closest('[data-drawer-toggle]')) { openDrawer(); return; }
    if (e.target.closest('[data-drawer-close]') || e.target.classList.contains('side__backdrop')) { closeDrawer(); }
  });

  function openDrawer() {
    const d = $('.side');
    if (!d) return;
    d.classList.add('is-open');
    const b = $('.side__backdrop');
    if (b) b.classList.add('is-open');
    d.setAttribute('aria-hidden', 'false');
  }
  function closeDrawer() {
    const d = $('.side');
    if (!d) return;
    d.classList.remove('is-open');
    d.setAttribute('aria-hidden', 'true');
    const b = $('.side__backdrop');
    if (b) b.classList.remove('is-open');
  }
  $$('.side__link').forEach((a) => a.addEventListener('click', () => { if (window.innerWidth <= 1024) closeDrawer(); }));

  /* ======================================================================
     TABS  ·  data-tabs > [data-tab] + [data-tab-panel]
     ====================================================================== */
  function activateTab(btn) {
    const root = btn.closest('[data-tabs]') || document;
    const panels = $$('[data-tab-panel]', root);
    const wants = btn.dataset.tab;
    let found = false;
    $$('[data-tab]', root).forEach((b) => b.classList.toggle('is-active', b === btn));
    panels.forEach((p) => {
      const match = wants ? p.id === wants : false;
      p.classList.toggle('is-active', match);
      if (match) found = true;
    });
    if (!found && panels.length) {
      const idx = $$('[data-tab]', root).indexOf(btn);
      panels.forEach((p, i) => p.classList.toggle('is-active', i === idx));
    }
    $$('[data-tab]', root).forEach((b) => b.setAttribute('aria-selected', b === btn ? 'true' : 'false'));
  }
  document.addEventListener('click', (e) => {
    const tab = e.target.closest('[data-tab]');
    if (tab) activateTab(tab);
  });
  function syncTabs() {
    $$('[data-tabs]').forEach((root) => {
      const active = $('[data-tab].is-active', root);
      if (active) activateTab(active);
    });
  }

  /* ======================================================================
     ACCORDION
     ====================================================================== */
  document.addEventListener('click', (e) => {
    const trig = e.target.closest('.acc-trigger');
    if (!trig) return;
    const panel = document.getElementById(trig.getAttribute('aria-controls')) ||
                  trig.nextElementSibling;
    const isOpen = trig.getAttribute('aria-expanded') === 'true';
    const group = trig.closest('[data-accordion]');
    if (group && group.dataset.accordion === 'single' && !isOpen) {
      $$('.acc-trigger[aria-expanded="true"]', group).forEach((o) => {
        o.setAttribute('aria-expanded', 'false');
        const op = document.getElementById(o.getAttribute('aria-controls')) || o.nextElementSibling;
        if (op) op.classList.remove('is-open');
      });
    }
    trig.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
    if (panel) panel.classList.toggle('is-open', !isOpen);
  });

  /* ======================================================================
     PILLS (single-select filter group)
     ====================================================================== */
  document.addEventListener('click', (e) => {
    const pill = e.target.closest('.pill');
    if (!pill || !pill.parentElement) return;
    $$('.pill', pill.parentElement).forEach((p) => p.classList.toggle('is-active', p === pill));
    const group = pill.closest('[data-pill-filter]');
    if (!group) return;
    const targetSel = group.dataset.pillFilter;
    const value = pill.dataset.value;
    $$(targetSel).forEach((card) => {
      const cat = card.dataset.category || '';
      card.hidden = !(value === 'all' || cat.split(' ').includes(value));
    });
  });

  /* ======================================================================
     REVEAL ON SCROLL
     ====================================================================== */
  function initReveal() {
    const items = $$('.reveal');
    if (!items.length) return;
    if (!('IntersectionObserver' in window) || REDUCED()) {
      items.forEach((el) => el.classList.add('is-in'));
      return;
    }
    const io = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('is-in');
        io.unobserve(entry.target);
      });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
    items.forEach((el) => { if (el.dataset.delay) el.style.setProperty('--delay', el.dataset.delay + 'ms'); io.observe(el); });
  }

  /* ======================================================================
     COUNTERS / PROGRESS / RINGS
     ====================================================================== */
  const REDUCED = () =>
    typeof window.matchMedia === 'function' &&
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function tween(from, to, ms, onFrame) {
    if (REDUCED()) { onFrame(to); return; }
    const t0 = performance.now();
    const step = (now) => {
      const p = Math.min(1, (now - t0) / ms);
      const eased = 1 - Math.pow(1 - p, 3);
      onFrame(from + (to - from) * eased);
      if (p < 1) requestAnimationFrame(step);
    };
    requestAnimationFrame(step);
  }

  function formatNum(n, opts) {
    return new Intl.NumberFormat('id-ID', opts).format(n);
  }

  function initMeters() {
    $$('[data-count-to]').forEach((el) => {
      const to = parseFloat(el.dataset.countTo);
      const dec = parseInt(el.dataset.countDec || '0', 10);
      const prefix = el.dataset.countPrefix || '';
      const suffix = el.dataset.countSuffix || '';
      const dur = parseInt(el.dataset.countDur || '1200', 10);
      const fmt = (v) => prefix + formatNum(v, { minimumFractionDigits: dec, maximumFractionDigits: dec }) + suffix;
      if (!('IntersectionObserver' in window)) { el.textContent = fmt(to); return; }
      const io = new IntersectionObserver((entries) => {
        entries.forEach((en) => {
          if (!en.isIntersecting) return;
          tween(0, to, dur, (v) => { el.textContent = fmt(v); });
          io.unobserve(en.target);
        });
      }, { threshold: 0.4 });
      io.observe(el);
    });

    $$('[data-progress]').forEach((el) => {
      const to = parseFloat(el.dataset.progress);
      const host = $('.progress', el.closest('.progress-wrap') || el.parentElement) || el;
      const bar = $('.progress__bar', host) || el;
      setTimeout(() => { bar.style.width = Math.max(0, Math.min(100, to)) + '%'; }, 120);
    });

    $$('[data-pct]').forEach((el) => {
      const to = parseFloat(el.dataset.pct);
      const isRing = el.classList.contains('ring');
      setTimeout(() => tween(0, to, isRing ? 1100 : 900, (v) => { el.style.setProperty('--pct', v); }), 120);
    });
  }

  /* ======================================================================
     PASSWORD TOGGLE
     ====================================================================== */
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-toggle-password]');
    if (!btn) return;
    const input = document.querySelector(btn.dataset.togglePassword);
    if (!input) return;
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    btn.setAttribute('aria-label', show ? 'Sembunyikan sandi' : 'Tampilkan sandi');
    btn.innerHTML = show
      ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9.9 4.2A10.9 10.9 0 0 1 12 4c7 0 10 8 10 8a18.5 18.5 0 0 1-2.2 3.2M6.6 6.6A18.5 18.5 0 0 0 2 12s3 8 10 8a10.8 10.8 0 0 0 5.4-1.5"/><path d="M14.1 14.1a3 3 0 1 1-4.2-4.2"/><path d="m2 2 20 20"/></svg>'
      : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2.1 12S5 4 12 4c2 0 3.9.6 5.5 1.5"/><path d="M21.9 12S19 20 12 20c-2 0-3.9-.6-5.5-1.5"/><path d="M12 15a3 3 0 0 0 3-3"/></svg>';
  });

  /* ======================================================================
     FORM VALIDATION  ·  form[data-validate]
     Rules: required, type=email, minlength, data-match="#other"
     ====================================================================== */
  function fieldOf(input) { return input.closest('.field') || input.parentElement; }

  function validateInput(input) {
    const field = fieldOf(input);
    let msg = '';
    const v = (input.value || '').trim();
    if (input.required && !v) msg = input.dataset.msgRequired || 'Wajib diisi.';
    else if (input.type === 'email' && v && !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v)) msg = 'Format email belum benar.';
    else if (input.minLength > 0 && v && v.length < input.minLength) msg = 'Minimal ' + input.minLength + ' karakter.';
    else if (input.dataset.match) {
      const other = document.querySelector(input.dataset.match);
      if (other && v !== other.value) msg = input.dataset.msgMatch || 'Tidak sama dengan kolom sebelumnya.';
    }
    field.classList.toggle('is-invalid', !!msg);
    if (msg) { const slot = $('.field__error', field); if (slot) slot.lastElementChild.textContent = msg; }
    return !msg;
  }

  document.addEventListener('submit', (e) => {
    const form = e.target.closest('form[data-validate]');
    if (!form) return;
    e.preventDefault();
    const inputs = $$('[required], [data-match], input[type="email"], [data-rules]', form);
    let ok = true;
    inputs.forEach((i) => { if (!validateInput(i)) ok = false; });
    if (!ok) {
      window.toast('Periksa lagi isian yang ditandai.', 'error');
      const first = $('.field.is-invalid input, .field.is-invalid select', form);
      if (first) first.focus();
      return;
    }
    const redirect = form.dataset.validateRedirect;
    const msg = form.dataset.validateMsg || 'Formulir valid.';
    if (redirect) { window.toast(msg, 'success'); setTimeout(() => (location.href = redirect), 700); }
    else form.dispatchEvent(new CustomEvent('form:valid', { bubbles: true }));
  });

  document.addEventListener('blur', (e) => {
    if (e.target.matches('form[data-validate] [required], form[data-validate] input[type="email"], form[data-validate] [data-match]')) {
      validateInput(e.target);
    }
  }, true);

  document.addEventListener('input', (e) => {
    if (e.target.closest('.field.is-invalid')) validateInput(e.target);
  });

  /* ======================================================================
     DEMO FORM SUBMIT  ·  form[data-demo-submit]
     ====================================================================== */
  document.addEventListener('submit', (e) => {
    const form = e.target.closest('form[data-demo-submit]');
    if (!form) return;
    e.preventDefault();
    const btn = $('[type="submit"]', form);
    const original = btn ? btn.innerHTML : '';
    if (btn) { btn.disabled = true; btn.innerHTML = 'Memproses…'; }
    setTimeout(() => {
      if (btn) { btn.disabled = false; btn.innerHTML = original; }
      const msg = form.dataset.demoSubmit || 'Berhasil disimpan.';
      window.toast(msg, 'success');
      const redirect = form.dataset.demoRedirect;
      if (redirect) setTimeout(() => (location.href = redirect), 800);
      else form.reset();
    }, 750);
  });

  /* ======================================================================
     TABLE: client-side filter + sort
     ====================================================================== */
  function initTables() {
    $$('[data-table-filter]').forEach((input) => {
      const table = document.getElementById(input.dataset.tableFilter);
      if (!table) return;
      input.addEventListener('input', () => {
        const q = input.value.toLowerCase().trim();
        let shown = 0;
        $$('tbody tr', table).forEach((tr) => {
          const hit = !q || tr.textContent.toLowerCase().includes(q);
          tr.hidden = !hit;
          if (hit) shown++;
        });
        const empty = table.parentElement.querySelector('[data-table-empty]');
        if (empty) empty.hidden = shown !== 0;
      });
    });

    $$('th.is-sortable').forEach((th) => {
      th.setAttribute('tabindex', '0');
      const run = () => {
        const table = th.closest('table');
        const idx = Array.from(th.parentElement.children).indexOf(th);
        const dir = th.getAttribute('aria-sort') === 'ascending' ? -1 : 1;
        $$('th', th.parentElement).forEach((h) => h.removeAttribute('aria-sort'));
        th.setAttribute('aria-sort', dir === 1 ? 'ascending' : 'descending');
        const tbody = table.tBodies[0];
        const rows = Array.from(tbody.rows);
        rows.sort((a, b) => {
          const av = (a.cells[idx].dataset.sort || a.cells[idx].textContent).trim();
          const bv = (b.cells[idx].dataset.sort || b.cells[idx].textContent).trim();
          const an = parseFloat(av.replace(/[^\d.-]/g, ''));
          const bn = parseFloat(bv.replace(/[^\d.-]/g, ''));
          const bothNum = !isNaN(an) && !isNaN(bn) && /\d/.test(av + bv);
          return bothNum ? (an - bn) * dir : av.localeCompare(bv, 'id') * dir;
        });
        rows.forEach((r) => tbody.appendChild(r));
      };
      th.addEventListener('click', run);
      th.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); run(); } });
    });
  }

  /* ======================================================================
     MISC
     ====================================================================== */
  function initMisc() {
    $$('[data-year]').forEach((el) => { el.textContent = new Date().getFullYear(); });

    document.addEventListener('click', (e) => {
      const copy = e.target.closest('[data-copy]');
      if (copy) {
        const text = copy.dataset.copy;
        const done = () => window.toast('Disalin: ' + text, 'success', 2000);
        if (navigator.clipboard) navigator.clipboard.writeText(text).then(done).catch(done);
        else done();
      }
      const print = e.target.closest('[data-print]');
      if (print) { e.preventDefault(); window.print(); }
    });

    // Close open dropdowns on outside click
    document.addEventListener('click', (e) => {
      $$('[data-dropdown]').forEach((dd) => {
        if (!dd.contains(e.target)) dd.classList.remove('is-open');
      });
    });
    $$('[data-dropdown-toggle]').forEach((btn) => {
      btn.addEventListener('click', (e) => {
        e.stopPropagation();
        const dd = btn.closest('[data-dropdown]');
        const open = dd.classList.contains('is-open');
        $$('[data-dropdown]').forEach((o) => o.classList.remove('is-open'));
        dd.classList.toggle('is-open', !open);
        btn.setAttribute('aria-expanded', String(!open));
      });
    });
  }

  /* ======================================================================
     BOOT
     ====================================================================== */
  function boot() {
    syncTabs();
    initReveal();
    initMeters();
    initTables();
    initMisc();
    document.dispatchEvent(new CustomEvent('app:ready'));
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();

  window.L = { toast: window.toast, openModal: window.openModal, closeModal: window.closeModal, confirmAction: window.confirmAction, $: $, $$: $$ };
})();

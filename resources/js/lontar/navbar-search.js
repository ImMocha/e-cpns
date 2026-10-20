document.querySelectorAll('[data-navbar-search]').forEach((search) => {
  const toggle = search.querySelector('[data-navbar-search-toggle]');
  const field = search.querySelector('[data-navbar-search-field]');
  const input = search.querySelector('[data-navbar-search-input]');
  let open = false;

  const setOpen = (expanded, restoreFocus = false) => {
    open = expanded;
    search.classList.toggle('is-open', open);
    toggle.setAttribute('aria-expanded', String(open));
    toggle.setAttribute('aria-label', open ? 'Tutup pencarian' : 'Buka pencarian');
    field.setAttribute('aria-hidden', String(!open));
    input.disabled = !open;
    if (open) {
      requestAnimationFrame(() => { if (open) input.focus({ preventScroll: true }); });
    } else if (restoreFocus) toggle.focus({ preventScroll: true });
  };

  toggle.addEventListener('click', () => setOpen(!open, true));
  search.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && open) {
      event.preventDefault();
      event.stopPropagation();
      setOpen(false, true);
    }
  });
  search.addEventListener('focusout', () => {
    requestAnimationFrame(() => { if (open && !search.contains(document.activeElement)) setOpen(false); });
  });
  document.addEventListener('pointerdown', (event) => {
    if (open && !search.contains(event.target)) setOpen(false);
  });
});

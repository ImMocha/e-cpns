document.addEventListener('submit', (event) => {
  const form = event.target.closest('[data-content-confirm]');
  if (!form) return;
  const options = {
    title: form.dataset.confirmTitle,
    body: form.dataset.confirmBody,
    ok: form.dataset.confirmOk || 'Ya, lanjutkan',
    tone: 'danger',
    onOk: () => form.submit(),
  };
  if (window.L?.confirmAction) {
    event.preventDefault();
    window.L.confirmAction(options);
  } else if (!window.confirm(options.body)) {
    event.preventDefault();
  }
});

document.addEventListener('click', (event) => {
  document.querySelectorAll('.content-menu[open]').forEach((menu) => {
    if (!menu.contains(event.target)) menu.open = false;
  });
});

document.addEventListener('keydown', (event) => {
  if (event.key !== 'Escape') return;
  document.querySelectorAll('.content-menu[open]').forEach((menu) => {
    menu.open = false;
    if (menu.contains(document.activeElement)) menu.querySelector('summary').focus();
  });
});

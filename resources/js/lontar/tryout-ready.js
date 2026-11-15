const dialog = document.getElementById('tryout-ready');

if (typeof dialog?.showModal === 'function') {
  const confirm = dialog.querySelector('[data-tryout-ready-confirm]');
  const cancel = dialog.querySelector('[data-tryout-ready-cancel]');
  const name = dialog.querySelector('[data-tryout-ready-name]');
  const count = dialog.querySelector('[data-tryout-ready-count]');
  const duration = dialog.querySelector('[data-tryout-ready-duration]');
  const timer = dialog.querySelector('[data-tryout-ready-timer]');

  document.addEventListener('submit', event => {
    const form = event.target;
    if (!form.matches('[data-tryout-start]') || event.defaultPrevented) return;

    if (dialog.open && event.submitter === confirm && confirm.form === form) {
      confirm.disabled = true;
      cancel.disabled = true;
      return;
    }

    event.preventDefault();
    if (dialog.open) return;

    const minutes = Number(form.dataset.durationMinutes);
    name.textContent = form.dataset.tryoutStart;
    count.textContent = form.dataset.questionCount + ' soal';
    duration.textContent = minutes > 0 ? minutes + ' menit' : 'Tanpa batas waktu';
    timer.hidden = minutes <= 0;
    confirm.setAttribute('form', form.id);
    dialog.showModal();
  });

  cancel.addEventListener('click', () => dialog.close());
  dialog.addEventListener('cancel', event => {
    if (confirm.disabled) event.preventDefault();
  });
  dialog.addEventListener('close', () => {
    if (!dialog.open) confirm.removeAttribute('form');
  });
  window.addEventListener('pageshow', event => {
    if (!event.persisted) return;
    confirm.disabled = false;
    cancel.disabled = false;
    dialog.close();
  });
}

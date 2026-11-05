document.querySelectorAll('[data-exam-catalog-order]').forEach((list) => {
  const status = list.closest('section').querySelector('[data-exam-order-status]');
  const rows = () => [...list.querySelectorAll('[data-exam-order-item]')];
  let savedRows = rows();
  let busy = false;
  let drag = null;
  let frame;

  const announce = (message, error = false) => {
    status.textContent = message;
    status.classList.toggle('is-error', error);
  };
  const restore = () => savedRows.forEach(row => list.append(row));
  const save = async (handle) => {
    const orderedRows = rows();
    if (orderedRows.every((row, index) => row === savedRows[index])) return;
    busy = true;
    list.setAttribute('aria-busy', 'true');
    list.querySelectorAll('[data-exam-order-handle]').forEach(button => { button.disabled = true; });
    announce('Menyimpan urutan…');
    try {
      const response = await fetch(list.dataset.orderUrl, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({ exam_ids: orderedRows.map(row => Number(row.dataset.examId)) }),
        signal: AbortSignal.timeout(15000),
      });
      const payload = await response.json();
      if (!response.ok) throw new Error(Object.values(payload.errors || {}).flat()[0] || payload.message);
      savedRows = orderedRows;
      announce('Urutan tersimpan. Daftar peserta mengikuti urutan ini.');
    } catch (error) {
      restore();
      announce(error.message && error.name === 'Error' ? error.message : 'Urutan belum dapat disimpan. Muat ulang halaman lalu coba lagi.', true);
    } finally {
      busy = false;
      list.removeAttribute('aria-busy');
      list.querySelectorAll('[data-exam-order-handle]').forEach(button => { button.disabled = false; });
      handle.focus({ preventScroll: true });
    }
  };

  list.addEventListener('keydown', (event) => {
    const handle = event.target.closest('[data-exam-order-handle]');
    if (!handle || busy || drag || !['ArrowUp', 'ArrowDown', 'Home', 'End'].includes(event.key)) return;
    event.preventDefault();
    const row = handle.closest('[data-exam-order-item]');
    if (event.key === 'ArrowUp' && row.previousElementSibling) list.insertBefore(row, row.previousElementSibling);
    if (event.key === 'ArrowDown' && row.nextElementSibling) row.nextElementSibling.after(row);
    if (event.key === 'Home') list.prepend(row);
    if (event.key === 'End') list.append(row);
    save(handle);
  });

  const placeRow = () => {
    const bounds = list.getBoundingClientRect();
    if (drag.x < bounds.left - 40 || drag.x > bounds.right + 40) return;
    const target = rows().find(row => row !== drag.row && drag.y < row.getBoundingClientRect().top + row.getBoundingClientRect().height / 2);
    if (target && drag.row.nextElementSibling !== target) list.insertBefore(drag.row, target);
    else if (!target && list.lastElementChild !== drag.row) list.append(drag.row);
  };
  const autoScroll = () => {
    if (!drag?.ghost) return;
    const bounds = list.getBoundingClientRect();
    if (drag.x >= bounds.left - 40 && drag.x <= bounds.right + 40) {
      const speed = drag.y < 80 ? -14 : drag.y > window.innerHeight - 80 ? 14 : 0;
      if (speed) { window.scrollBy(0, speed); placeRow(); }
    }
    frame = requestAnimationFrame(autoScroll);
  };
  const finishDrag = (cancelled = false) => {
    if (!drag) return;
    const current = drag;
    drag = null;
    cancelAnimationFrame(frame);
    current.ghost?.remove();
    current.row.classList.remove('is-order-dragging');
    document.body.classList.remove('exam-catalog-dragging');
    if (cancelled) {
      restore();
      announce('Pemindahan dibatalkan.');
      current.handle.focus({ preventScroll: true });
    } else if (current.ghost) save(current.handle);
  };
  list.addEventListener('pointerdown', (event) => {
    const handle = event.target.closest('[data-exam-order-handle]');
    if (!handle || busy || drag || !event.isPrimary || event.button !== 0) return;
    event.preventDefault();
    handle.focus({ preventScroll: true });
    const row = handle.closest('[data-exam-order-item]');
    const bounds = row.getBoundingClientRect();
    drag = { row, handle, pointerId: event.pointerId, x: event.clientX, y: event.clientY,
      startX: event.clientX, startY: event.clientY, offsetX: event.clientX - bounds.left, offsetY: event.clientY - bounds.top, width: bounds.width };
  });
  window.addEventListener('pointermove', (event) => {
    if (!drag || drag.pointerId !== event.pointerId) return;
    drag.x = event.clientX;
    drag.y = event.clientY;
    if (!drag.ghost && Math.hypot(drag.x - drag.startX, drag.y - drag.startY) < 6) return;
    event.preventDefault();
    if (!drag.ghost) {
      drag.ghost = drag.row.cloneNode(true);
      drag.ghost.classList.add('exam-catalog-order-preview');
      drag.ghost.style.width = `${drag.width}px`;
      drag.ghost.setAttribute('aria-hidden', 'true');
      drag.ghost.inert = true;
      document.body.append(drag.ghost);
      drag.row.classList.add('is-order-dragging');
      document.body.classList.add('exam-catalog-dragging');
      frame = requestAnimationFrame(autoScroll);
    }
    drag.ghost.style.transform = `translate(${drag.x - drag.offsetX}px, ${drag.y - drag.offsetY}px)`;
    placeRow();
  }, { passive: false });
  window.addEventListener('pointerup', (event) => { if (drag?.pointerId === event.pointerId) finishDrag(); });
  window.addEventListener('pointercancel', () => finishDrag(true));
  window.addEventListener('blur', () => finishDrag(true));
  document.addEventListener('keydown', (event) => {
    if (drag && event.key === 'Escape') { event.preventDefault(); finishDrag(true); }
  });
  window.addEventListener('beforeunload', (event) => {
    if (busy) { event.preventDefault(); event.returnValue = ''; }
  });
});

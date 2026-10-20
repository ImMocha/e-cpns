export function createQuestionOrder(form, pool, onChange) {
  const panel = form.querySelector('[data-question-order]');
  const list = panel.querySelector('[data-question-order-list]');
  const template = panel.querySelector('[data-question-order-template]');
  const random = form.querySelector('input[type="checkbox"][name="random_questions"]');
  const count = form.querySelector('#exam-count');
  const status = panel.querySelector('[data-question-order-status]');
  let order = [];
  let signature = '';
  let drag = null;
  let frame = null;

  const announce = (text) => { status.textContent = text; };
  const focusItem = (id, action = 'handle') => {
    const item = [...list.children].find(row => row.dataset.questionId === id);
    const button = item?.querySelector(`[data-question-order-${action}]:not(:disabled)`)
      || item?.querySelector('[data-question-order-handle]');
    button?.focus();
  };
  const render = (questions) => {
    const nextSignature = JSON.stringify(order.map(id => [id, questions.get(id)?.title]));
    if (signature === nextSignature) return;
    signature = nextSignature;
    const fragment = document.createDocumentFragment();
    order.forEach((id, index) => {
      const row = template.content.firstElementChild.cloneNode(true);
      const title = questions.get(id).title;
      row.dataset.questionId = id;
      row.querySelector('input').value = id;
      row.querySelector('[data-question-order-number]').textContent = index + 1;
      row.querySelector('[data-question-order-title]').textContent = title;
      row.querySelector('[data-question-order-handle]').setAttribute('aria-label', `Geser soal ${index + 1}: ${title}`);
      for (const [direction, label, disabled] of [['up', 'Naikkan', index === 0], ['down', 'Turunkan', index === order.length - 1]]) {
        const button = row.querySelector(`[data-question-order-${direction}]`);
        button.disabled = disabled;
        button.setAttribute('aria-label', `${label} soal ${index + 1}: ${title}`);
      }
      fragment.append(row);
    });
    list.replaceChildren(fragment);
    window.LIcons?.hydrate(list);
  };
  const sync = () => {
    const questions = new Map();
    pool.querySelectorAll('[data-exam-pool-item]').forEach(row => {
      const checkbox = row.querySelector('input[type="checkbox"]');
      checkbox.removeAttribute('name');
      if (checkbox.checked) questions.set(row.dataset.questionId, { title: row.querySelector('strong').textContent });
    });
    order = order.filter(id => questions.has(id));
    for (const id of questions.keys()) {
      if (!order.includes(id)) order.push(id);
    }
    render(questions);
    panel.hidden = false;
    panel.querySelector('[data-question-order-count]').textContent = `${order.length} soal`;
    panel.querySelector('[data-question-order-empty]').hidden = order.length > 0;
    panel.querySelector('[data-question-order-random]').hidden = !random.checked || order.length === 0;
    panel.querySelector('[data-question-order-limit]').textContent = random.checked
      ? `${count.value || 0} soal terbit diambil secara acak dari kumpulan yang dipilih.`
      : `${count.value || 0} soal terbit pertama diambil sesuai urutan ini. Soal yang belum tersedia dilewati.`;
  };
  const changed = (id, action = 'handle') => {
    signature = '';
    sync();
    onChange();
    focusItem(id, action);
    const title = list.querySelector(`[data-question-id="${id}"] [data-question-order-title]`)?.textContent || 'Soal';
    announce(`${title} dipindahkan ke urutan ${order.indexOf(id) + 1}. Simpan sesi untuk menerapkan perubahan.`);
  };
  const move = (id, index, action) => {
    const previous = order.indexOf(id);
    const next = Math.max(0, Math.min(order.length - 1, index));
    if (previous === next) return;
    order.splice(previous, 1);
    order.splice(next, 0, id);
    changed(id, action);
  };
  list.addEventListener('click', (event) => {
    const button = event.target.closest('[data-question-order-up], [data-question-order-down]');
    if (!button) return;
    const id = button.closest('[data-question-order-item]').dataset.questionId;
    const up = button.hasAttribute('data-question-order-up');
    move(id, order.indexOf(id) + (up ? -1 : 1), up ? 'up' : 'down');
  });
  list.addEventListener('keydown', (event) => {
    if (!event.target.matches('[data-question-order-handle]') || !['ArrowUp', 'ArrowDown', 'Home', 'End'].includes(event.key)) return;
    event.preventDefault();
    const id = event.target.closest('[data-question-order-item]').dataset.questionId;
    const next = event.key === 'Home' ? 0 : event.key === 'End' ? order.length - 1 : order.indexOf(id) + (event.key === 'ArrowUp' ? -1 : 1);
    move(id, next, 'handle');
  });
  panel.querySelector('[data-question-order-use]').addEventListener('click', () => {
    random.checked = false;
    random.dispatchEvent(new Event('change', { bubbles: true }));
    announce('Pengacakan dinonaktifkan. Peserta mengikuti urutan soal yang disimpan.');
  });

  const placeDraggedRow = () => {
    if (!drag?.ghost) return;
    const bounds = list.getBoundingClientRect();
    if (drag.x < bounds.left - 40 || drag.x > bounds.right + 40 || drag.y < bounds.top - 50 || drag.y > bounds.bottom + 50) return;
    const target = [...list.children].find(row => row !== drag.row && drag.y < row.getBoundingClientRect().top + row.getBoundingClientRect().height / 2);
    if (target) {
      if (drag.row.nextElementSibling !== target) list.insertBefore(drag.row, target);
    } else if (list.lastElementChild !== drag.row) list.append(drag.row);
  };
  const autoScroll = () => {
    if (!drag?.ghost) return;
    const bounds = list.getBoundingClientRect();
    if (drag.x >= bounds.left - 40 && drag.x <= bounds.right + 40) {
      const speed = drag.y < bounds.top + 45 ? -12 : drag.y > bounds.bottom - 45 ? 12 : 0;
      if (speed) { list.scrollTop += speed; placeDraggedRow(); }
    }
    frame = requestAnimationFrame(autoScroll);
  };
  const finishDrag = (cancelled = false) => {
    if (!drag) return;
    const current = drag;
    drag = null;
    cancelAnimationFrame(frame);
    current.row.classList.remove('is-dragging');
    current.ghost?.remove();
    document.body.classList.remove('exam-order-dragging');
    if (!current.ghost) return;
    const next = [...list.children].map(row => row.dataset.questionId);
    if (!cancelled && next.some((id, index) => id !== order[index])) {
      order = next;
      changed(current.id);
    } else {
      signature = '';
      sync();
      focusItem(current.id);
      if (cancelled) announce('Pemindahan soal dibatalkan.');
    }
  };
  list.addEventListener('pointerdown', (event) => {
    const handle = event.target.closest('[data-question-order-handle]');
    if (!handle || !event.isPrimary || event.button !== 0 || order.length < 2) return;
    event.preventDefault();
    handle.focus({ preventScroll: true });
    const row = handle.closest('[data-question-order-item]');
    const bounds = row.getBoundingClientRect();
    drag = { row, id: row.dataset.questionId, pointerId: event.pointerId, x: event.clientX, y: event.clientY,
      startX: event.clientX, startY: event.clientY, offsetX: event.clientX - bounds.left, offsetY: event.clientY - bounds.top, width: bounds.width };
  });
  window.addEventListener('pointermove', (event) => {
    if (!drag || drag.pointerId !== event.pointerId) return;
    drag.x = event.clientX; drag.y = event.clientY;
    if (!drag.ghost && Math.hypot(drag.x - drag.startX, drag.y - drag.startY) < 6) return;
    event.preventDefault();
    if (!drag.ghost) {
      drag.ghost = drag.row.cloneNode(true);
      drag.ghost.classList.add('is-drag-preview');
      drag.ghost.style.width = `${drag.width}px`;
      drag.ghost.setAttribute('aria-hidden', 'true');
      drag.ghost.inert = true;
      drag.ghost.querySelectorAll('input').forEach(input => { input.disabled = true; });
      document.body.append(drag.ghost);
      drag.row.classList.add('is-dragging');
      document.body.classList.add('exam-order-dragging');
      frame = requestAnimationFrame(autoScroll);
    }
    drag.ghost.style.transform = `translate(${drag.x - drag.offsetX}px, ${drag.y - drag.offsetY}px)`;
    placeDraggedRow();
  }, { passive: false });
  window.addEventListener('pointerup', (event) => { if (drag?.pointerId === event.pointerId) finishDrag(); });
  window.addEventListener('pointercancel', () => finishDrag(true));
  window.addEventListener('blur', () => finishDrag(true));
  document.addEventListener('keydown', (event) => {
    if (drag && event.key === 'Escape') { event.preventDefault(); finishDrag(true); }
  });
  form.addEventListener('submit', () => { finishDrag(); sync(); });
  return { sync };
}

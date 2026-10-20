import { createQuestionOrder } from './exam-question-order.js';

document.querySelectorAll('[data-exam-form]').forEach((sessionForm) => {
  const dialog = document.querySelector('[data-exam-question-dialog]');
  if (!dialog) return;
  const editor = dialog.querySelector('[data-inline-question-form]');
  const pool = sessionForm.querySelector('[data-exam-question-pool]');
  const search = sessionForm.querySelector('[data-exam-question-search]');
  const sectionFilter = sessionForm.querySelector('[data-exam-question-section]');
  const selectedOnly = sessionForm.querySelector('[data-exam-selected-only]');
  const message = sessionForm.querySelector('[data-exam-question-message]');
  const errors = editor.querySelector('[data-inline-errors]');
  const discard = editor.querySelector('[data-inline-discard]');
  const shared = editor.querySelector('[data-inline-shared]');
  const createUrl = editor.action;
  let editingQuestion = null;
  let sessionDirty = false;
  let sessionSubmitting = false;
  let editorBusy = false;
  let loadSequence = 0;
  let previousOverflow = '';
  let opener = null;
  const sessionFingerprint = () => new URLSearchParams(new FormData(sessionForm)).toString();
  const initialFingerprint = sessionFingerprint();
  const trackSessionChanges = () => { sessionDirty = sessionFingerprint() !== initialFingerprint; };
  const rows = () => [...pool.querySelectorAll('[data-exam-pool-item]')];
  const questionOrder = createQuestionOrder(sessionForm, pool, trackSessionChanges);
  const notify = (text, isError = false) => {
    message.textContent = text;
    message.classList.toggle('is-error', isError);
    message.hidden = false;
  };
  const request = async (url, options = {}) => {
    const response = await fetch(url, { credentials: 'same-origin', ...options, headers: { Accept: 'application/json', ...options.headers } });
    let payload;
    try { payload = await response.json(); } catch { payload = {}; }
    if (!response.ok) {
      throw { errors: payload.errors, message: response.status === 419 || response.status === 401
        ? 'Sesi login berakhir. Simpan salinan perubahan sebelum masuk kembali.'
        : response.status >= 500 ? 'Soal belum berhasil disimpan. Silakan coba lagi.'
        : payload.message || 'Tindakan belum berhasil. Silakan coba lagi.' };
    }
    return payload;
  };
  const updatePool = () => {
    questionOrder.sync();
    const term = search.value.trim().toLocaleLowerCase('id');
    const selected = rows().filter(row => row.querySelector('input').checked);
    const ready = selected.filter(row => row.querySelector('.exam-pool-status.is-ready')).length;
    sessionForm.querySelector('[data-exam-selected]').textContent = selected.length + ' dipilih · ' + ready + ' terbit';
    rows().forEach(row => {
      row.hidden = Boolean((term && !row.textContent.toLocaleLowerCase('id').includes(term))
        || (sectionFilter.value && row.dataset.sectionId !== sectionFilter.value)
        || (selectedOnly.checked && !row.querySelector('input').checked));
    });
    sessionForm.querySelector('[data-exam-pool-empty]').hidden = rows().some(row => !row.hidden);
  };
  const closeEditor = (force = false) => {
    if (editorBusy) return;
    if (!force && editor.dataset.dirty === 'true') {
      discard.hidden = false;
      editor.querySelector('[data-inline-keep]').focus();
      return;
    }
    loadSequence++;
    editor.dispatchEvent(new CustomEvent('question-editor:saved'));
    editor.querySelector('[data-preview-video]').pause();
    dialog.close();
    document.body.style.overflow = previousOverflow;
    if (opener?.isConnected) opener.focus();
    else search.focus();
  };
  const openEditor = (question, button) => {
    editingQuestion = question?.id ? question : null;
    opener = button;
    errors.hidden = true;
    discard.hidden = true;
    shared.hidden = !question?.usage_count;
    if (question?.usage_count) {
      shared.textContent = 'Soal ini dipakai oleh ' + question.usage_count + ' sesi. Perubahan isi dan status berlaku pada semua sesi tersebut; riwayat pengerjaan tetap memakai soal sebelumnya.';
    }
    editor.action = question?.update_url || createUrl;
    editor.querySelector('[data-inline-question-title]').textContent = question?.id ? 'Edit Soal' : 'Tambah Soal';
    const defaultSection = sessionForm.querySelector('#exam-topic')?.selectedOptions[0]?.dataset.sectionId
      || sectionFilter.value || sessionForm.dataset.defaultSection;
    editor.dispatchEvent(new CustomEvent('question-editor:load', { detail: question || { section_id: defaultSection } }));
    editor.querySelector('[data-inline-save-hint]').textContent = question?.id
      ? 'Perubahan isi soal langsung disimpan. Pilihan soal sesi tetap diterapkan saat sesi disimpan.'
      : 'Soal baru otomatis dicentang. Simpan sesi untuk memasukkannya ke kumpulan soal.';
    previousOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    dialog.showModal();
    editor.querySelector('.question-editor-layout').scrollTop = 0;
    editor.querySelector('#question-title').focus();
  };

  sessionForm.querySelector('[data-add-question]').addEventListener('click', (event) => {
    loadSequence++;
    openEditor(null, event.currentTarget);
  });
  pool.addEventListener('click', async (event) => {
    const editButton = event.target.closest('[data-edit-question]');
    if (editButton) {
      const sequence = ++loadSequence;
      editButton.disabled = true;
      try {
        const payload = await request(editButton.dataset.editQuestion);
        if (sequence === loadSequence) openEditor(payload.question, editButton);
      } catch (error) { notify(error.message || 'Soal tidak dapat dimuat. Silakan coba lagi.', true); }
      finally { editButton.disabled = false; }
      return;
    }
    const archiveButton = event.target.closest('[data-archive-question]');
    if (!archiveButton) return;
    const row = archiveButton.closest('[data-exam-pool-item]');
    const archive = async () => {
      archiveButton.disabled = true;
      const data = new FormData();
      data.set('_token', sessionForm.querySelector('[name="_token"]').value);
      data.set('_method', 'DELETE');
      try {
        await request(archiveButton.dataset.archiveQuestion, { method: 'POST', body: data });
        row.remove();
        updatePool();
        trackSessionChanges();
        notify('Soal diarsipkan dari bank soal. Pastikan jumlah soal terbit sesi tetap mencukupi.');
      } catch (error) { archiveButton.disabled = false; notify(error.message || 'Soal tidak dapat diarsipkan.', true); }
    };
    window.L.confirmAction({ title: 'Arsipkan soal?',
      body: 'Soal disembunyikan dari bank dan tidak diambil untuk sesi baru. ' + archiveButton.dataset.usageCount
        + ' sesi memakai soal ini; tambahkan pengganti jika jumlah soal terbit berkurang. Riwayat pengerjaan tetap tersimpan.',
      ok: 'Ya, arsipkan', onOk: archive });
  });
  for (const field of [search, sectionFilter, selectedOnly]) {
    field.addEventListener('input', updatePool);
    field.addEventListener('change', updatePool);
  }
  sessionForm.addEventListener('change', (event) => {
    updatePool();
    if (![search, sectionFilter, selectedOnly].includes(event.target)) trackSessionChanges();
  });
  sessionForm.addEventListener('input', (event) => {
    if (event.target.matches('#exam-count')) questionOrder.sync();
    if (![search, sectionFilter, selectedOnly].includes(event.target)) trackSessionChanges();
  });
  sessionForm.addEventListener('submit', () => { sessionSubmitting = true; });
  window.addEventListener('beforeunload', (event) => {
    if (sessionDirty && !sessionSubmitting) { event.preventDefault(); event.returnValue = ''; }
  });
  editor.querySelectorAll('[data-inline-close]').forEach(button => button.addEventListener('click', () => closeEditor()));
  editor.querySelector('[data-inline-keep]').addEventListener('click', () => { discard.hidden = true; editor.querySelector('#question-title').focus(); });
  editor.querySelector('[data-inline-discard-confirm]').addEventListener('click', () => closeEditor(true));
  dialog.addEventListener('cancel', (event) => { event.preventDefault(); closeEditor(); });
  dialog.addEventListener('click', (event) => {
    if (event.target !== dialog) return;
    const rect = dialog.getBoundingClientRect();
    if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) closeEditor();
  });
  editor.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (editorBusy) return;
    editorBusy = true;
    errors.hidden = true;
    discard.hidden = true;
    const data = new FormData(editor);
    data.set('status', event.submitter?.value || editor.querySelector('[name="status"]').value);
    if (editingQuestion) data.set('_method', 'PUT');
    const controls = [...editor.querySelectorAll('button, input, select, textarea')].filter(control => !control.disabled);
    controls.forEach(control => { control.disabled = true; });
    const editable = [...editor.querySelectorAll('[contenteditable="true"]')];
    editable.forEach(control => { control.contentEditable = 'false'; });
    try {
      const payload = await request(editor.action, { method: 'POST', body: data });
      const oldRow = rows().find(row => row.dataset.questionId === String(payload.question.id));
      const isSelected = oldRow ? oldRow.querySelector('input').checked : true;
      const template = document.createElement('template');
      template.innerHTML = payload.html.trim();
      const row = template.content.firstElementChild;
      row.querySelector('input').checked = isSelected;
      if (oldRow) oldRow.replaceWith(row);
      else { pool.prepend(row); search.value = ''; sectionFilter.value = ''; }
      window.LIcons?.hydrate(row);
      updatePool();
      if (!oldRow) trackSessionChanges();
      editor.dispatchEvent(new CustomEvent('question-editor:saved'));
      notify(oldRow ? 'Perubahan soal berhasil disimpan.' : 'Soal baru berhasil disimpan dan dicentang. Simpan sesi untuk menerapkan pilihan soal.');
      editorBusy = false;
      closeEditor(true);
    } catch (error) {
      const messages = [...new Set(error.errors ? Object.values(error.errors).flat() : [error.message || 'Soal belum berhasil disimpan. Silakan coba lagi.'])];
      errors.replaceChildren(...messages.map(text => { const paragraph = document.createElement('p'); paragraph.textContent = text; return paragraph; }));
      errors.hidden = false;
      errors.tabIndex = -1;
      errors.focus();
      editor.dispatchEvent(new CustomEvent('question-editor:submit-failed'));
    } finally {
      editorBusy = false;
      controls.forEach(control => { control.disabled = false; });
      editable.forEach(control => { control.contentEditable = 'true'; });
    }
  });
  updatePool();
});

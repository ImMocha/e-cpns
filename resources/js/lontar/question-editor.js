import { sanitizeArticle } from './article-editor.js';

document.querySelectorAll('[data-exam-form]').forEach((form) => {
  const choices = [...form.querySelectorAll('[data-exam-pool-item]')];
  const update = () => { form.querySelector('[data-exam-selected]').textContent = choices.filter(row => row.querySelector('input').checked).length + ' soal dipilih sebagai bank sumber'; };
  form.addEventListener('change', update);
  form.querySelector('[data-exam-question-search]').addEventListener('input', (event) => {
    const term = event.target.value.trim().toLocaleLowerCase('id');
    choices.forEach(row => { row.hidden = !row.textContent.toLocaleLowerCase('id').includes(term); });
  });
  update();
});

document.querySelectorAll('[data-question-editor]').forEach((form) => {
  const list = form.querySelector('[data-option-list]');
  const previewOptions = form.querySelector('[data-preview-options]');
  const title = form.querySelector('#question-title');
  const section = form.querySelector('#question-section');
  const level = form.querySelector('#question-level');
  const image = form.querySelector('#question-image');
  const previewImage = form.querySelector('[data-preview-image]');
  const removeImage = form.querySelector('[data-remove-image]');
  const video = form.querySelector('#question-video');
  const previewVideo = form.querySelector('[data-preview-video]');
  const removeVideo = form.querySelector('[data-remove-video]');
  const videoError = form.querySelector('[data-video-error]');
  const addOption = form.querySelector('[data-add-option]');
  let imageUrl = null;
  let videoUrl = null;
  let dirty = false;
  let submitting = false;
  const editors = new Map();
  const rows = () => [...list.querySelectorAll('[data-option-row]')];
  const markDirty = () => {
    dirty = true;
    form.querySelector('[data-question-save-note]').textContent = 'Ada perubahan yang belum disimpan';
  };

  const syncPreview = () => {
    form.querySelector('[data-preview-title]').textContent = title.value.trim() || 'Judul soal';
    form.querySelector('[data-preview-field]').textContent = section.selectedOptions[0]?.value ? section.selectedOptions[0].textContent : 'Bidang';
    form.querySelector('[data-preview-level]').textContent = level.selectedOptions[0]?.textContent || '';
    for (const [name, item] of editors) {
      item.source.value = sanitizeArticle(item.content.innerHTML);
      const target = form.querySelector(name === 'body' ? '[data-preview-stem]' : '[data-preview-explanation]');
      target.innerHTML = item.source.value;
      if (!target.textContent.trim()) {
        const p = document.createElement('p');
        p.className = 'text-soft';
        p.textContent = name === 'body' ? 'Tulis pertanyaan untuk melihat preview.' : 'Tulis pembahasan untuk menjelaskan jawaban.';
        target.replaceChildren(p);
      }
    }
    previewOptions.replaceChildren();
    let selected = null;
    const currentRows = rows();
    currentRows.forEach((row, index) => {
      const letter = String.fromCharCode(65 + index);
      const radio = row.querySelector('input[type="radio"]');
      const textarea = row.querySelector('textarea');
      radio.value = index;
      radio.setAttribute('aria-label', 'Jadikan ' + letter + ' jawaban benar');
      textarea.setAttribute('aria-label', 'Pilihan ' + letter);
      row.querySelector('[data-option-letter]').textContent = letter;
      const remove = row.querySelector('[data-remove-option]');
      remove.hidden = false;
      remove.disabled = currentRows.length <= 2;
      remove.setAttribute('aria-label', 'Hapus pilihan ' + letter);
      const option = document.createElement('div');
      option.className = 'question-preview-option' + (radio.checked ? ' question-preview-option--correct' : '');
      const key = document.createElement('span');
      key.className = 'question-preview-letter';
      key.textContent = letter;
      const text = document.createElement('span');
      text.textContent = textarea.value.trim() || 'Pilihan ' + letter + ' belum diisi';
      option.append(key, text);
      previewOptions.append(option);
      if (radio.checked) selected = letter;
    });
    form.querySelector('[data-key-label]').textContent = selected ? 'Kunci ' + selected : 'Kunci belum dipilih';
    addOption.disabled = currentRows.length >= 10;
    const url = imageUrl || (removeImage?.checked ? '' : previewImage.dataset.existingImage);
    previewImage.hidden = !url;
    if (url) previewImage.src = url;
    else previewImage.removeAttribute('src');
    const videoSource = videoUrl || (removeVideo?.checked ? '' : previewVideo.dataset.existingVideo);
    previewVideo.hidden = !videoSource;
    if ((previewVideo.getAttribute('src') || '') !== (videoSource || '')) {
      previewVideo.pause();
      if (videoSource) previewVideo.src = videoSource;
      else previewVideo.removeAttribute('src');
      previewVideo.load();
    }
  };

  form.querySelectorAll('[data-question-rich-text]').forEach((root) => {
    const source = root.querySelector('[data-rich-source]');
    const content = root.querySelector('[data-rich-content]');
    const toolbar = root.querySelector('[data-rich-toolbar]');
    let selectionRange = null;
    content.innerHTML = sanitizeArticle(source.value);
    content.hidden = false;
    source.hidden = true;
    toolbar.hidden = false;
    editors.set(root.dataset.name, { source, content });
    const remember = () => {
      const selection = window.getSelection();
      if (selection?.rangeCount && content.contains(selection.getRangeAt(0).commonAncestorContainer)) {
        selectionRange = selection.getRangeAt(0).cloneRange();
      }
    };
    const execute = (command, value = null) => {
      content.focus();
      const selection = window.getSelection();
      if (selectionRange && content.contains(selectionRange.commonAncestorContainer)) {
        selection.removeAllRanges();
        selection.addRange(selectionRange);
      }
      document.execCommand('defaultParagraphSeparator', false, 'p');
      document.execCommand(command, false, value);
      remember();
      markDirty();
      syncPreview();
    };
    document.addEventListener('selectionchange', remember);
    toolbar.addEventListener('mousedown', (event) => event.preventDefault());
    toolbar.addEventListener('click', (event) => {
      const button = event.target.closest('[data-rich-command]');
      if (button) execute(button.dataset.richCommand);
    });
    content.addEventListener('input', remember);
    content.addEventListener('paste', (event) => {
      event.preventDefault();
      remember();
      const html = event.clipboardData.getData('text/html');
      execute(html ? 'insertHTML' : 'insertText', html ? sanitizeArticle(html) : event.clipboardData.getData('text/plain'));
    });
    content.addEventListener('drop', (event) => event.preventDefault());
  });

  addOption.hidden = false;
  addOption.addEventListener('click', () => {
    if (rows().length >= 10) return;
    list.append(form.querySelector('[data-option-template]').content.cloneNode(true));
    window.LIcons?.hydrate(list);
    markDirty();
    syncPreview();
    rows().at(-1).querySelector('textarea').focus();
  });
  list.addEventListener('click', (event) => {
    const remove = event.target.closest('[data-remove-option]');
    if (!remove || rows().length <= 2) return;
    remove.closest('[data-option-row]').remove();
    markDirty();
    syncPreview();
  });
  form.addEventListener('input', () => { markDirty(); syncPreview(); });
  form.addEventListener('change', () => { markDirty(); syncPreview(); });
  image.addEventListener('change', () => {
    if (imageUrl) URL.revokeObjectURL(imageUrl);
    imageUrl = null;
    const file = image.files[0];
    if (file && ['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) imageUrl = URL.createObjectURL(file);
    syncPreview();
  });
  video.addEventListener('change', () => {
    if (videoUrl) URL.revokeObjectURL(videoUrl);
    videoUrl = null;
    const file = video.files[0];
    const allowed = file && /\.(mp4|webm)$/i.test(file.name) && (!file.type || ['video/mp4', 'video/webm'].includes(file.type));
    const message = file && !allowed ? 'Video soal harus berupa file MP4 atau WebM.'
      : file && file.size > 50 * 1024 * 1024 ? 'Ukuran video soal maksimal 50 MB.' : '';
    video.setCustomValidity(message);
    videoError.textContent = message;
    videoError.hidden = !message;
    if (file && !message) videoUrl = URL.createObjectURL(file);
    syncPreview();
  });
  previewVideo.addEventListener('error', () => {
    if (!previewVideo.hasAttribute('src')) return;
    videoError.textContent = 'Video tidak dapat diputar. Periksa file dan codec video; disarankan MP4 H.264.';
    videoError.hidden = false;
  });
  previewVideo.addEventListener('loadedmetadata', () => {
    if (!video.validationMessage) videoError.hidden = true;
  });
  for (const selector of ['[data-preview-stem]', '[data-preview-explanation]']) {
    form.querySelector(selector).addEventListener('click', (event) => { if (event.target.closest('a')) event.preventDefault(); });
  }
  form.addEventListener('submit', () => { syncPreview(); submitting = true; });
  window.addEventListener('beforeunload', (event) => {
    if (dirty && !submitting) { event.preventDefault(); event.returnValue = ''; }
  });
  syncPreview();
});

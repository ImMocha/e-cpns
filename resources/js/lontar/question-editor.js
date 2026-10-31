import { sanitizeArticle } from './article-editor.js';

document.querySelectorAll('[data-question-exams]').forEach((panel) => {
  const search = panel.querySelector('[data-question-exam-search]');
  if (!search) return;
  const choices = [...panel.querySelectorAll('[data-question-exam-item]')];
  const updateCount = () => {
    panel.querySelector('[data-question-exam-count]').textContent = choices.filter(row => row.querySelector('input').checked).length + ' sesi dipilih';
  };
  search.addEventListener('input', () => {
    const term = search.value.trim().toLocaleLowerCase('id');
    choices.forEach(row => { row.hidden = !row.textContent.toLocaleLowerCase('id').includes(term); });
    panel.querySelectorAll('[data-question-exam-group]').forEach(group => {
      group.hidden = [...group.querySelectorAll('[data-question-exam-item]')].every(row => row.hidden);
    });
    panel.querySelector('[data-question-exam-empty]').hidden = choices.some(row => !row.hidden);
  });
  panel.addEventListener('change', updateCount);
  updateCount();
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
  const scoring = form.querySelector('[data-question-scoring]');
  const explanationImage = form.querySelector('[data-explanation-image]');
  const previewExplanationImage = form.querySelector('[data-preview-explanation-image]');
  const imagePreviews = new Map();
  let imageUrl = null;
  let videoUrl = null;
  let dirty = false;
  let submitting = false;
  const editors = new Map();
  const rows = () => [...list.querySelectorAll('[data-option-row]')];
  const markDirty = () => {
    dirty = true;
    form.dataset.dirty = 'true';
    form.querySelector('[data-question-save-note]').textContent = 'Ada perubahan yang belum disimpan';
  };

  const imageSource = (input, existingUrl, removed, error) => {
    const file = input.files[0];
    const message = file && !['image/jpeg', 'image/png', 'image/webp'].includes(file.type)
      ? 'Gunakan gambar JPG, PNG, atau WebP.' : file && file.size > 2 * 1024 * 1024 ? 'Ukuran gambar maksimal 2 MB.' : '';
    input.setCustomValidity(message);
    error.textContent = message;
    error.hidden = !message;
    const cached = imagePreviews.get(input);
    if (cached?.file !== file || message) {
      if (cached) URL.revokeObjectURL(cached.url);
      imagePreviews.delete(input);
      if (file && !message) imagePreviews.set(input, { file, url: URL.createObjectURL(file) });
    }
    return imagePreviews.get(input)?.url || (removed ? '' : existingUrl);
  };
  const showImage = (element, url) => {
    element.hidden = !url;
    if (url) element.src = url;
    else element.removeAttribute('src');
  };

  const syncPreview = () => {
    const weighted = scoring.value === 'weighted';
    scoring.querySelector('[value="weighted"]').disabled = section.selectedOptions[0]?.dataset.code !== 'TKP';
    form.querySelector('[data-scoring-hint]').textContent = weighted ? 'Setiap pilihan memiliki skor 1–5. Tidak ada jawaban benar atau salah.' : 'Isi 2–10 pilihan, lalu tandai satu jawaban yang benar.';
    form.querySelector('[data-preview-scoring-hint]').textContent = weighted ? 'Periksa pertanyaan dan skor tiap pilihan' : 'Periksa pertanyaan dan kunci jawaban';
    form.querySelector('[data-scoring-checklist]').textContent = weighted ? 'Skor 1–5 untuk setiap pilihan telah diperiksa.' : 'Kunci jawaban telah diperiksa.';
    form.querySelector('[data-preview-title]').textContent = title.value.trim() || 'Judul soal';
    form.querySelector('[data-preview-field]').textContent = section.selectedOptions[0]?.value ? section.selectedOptions[0].textContent : 'Bidang';
    form.querySelector('[data-preview-level]').textContent = level.selectedOptions[0]?.textContent || '';
    for (const [name, item] of editors) {
      item.source.value = sanitizeArticle(item.content.innerHTML);
      const target = form.querySelector(name === 'body' ? '[data-preview-stem]' : '[data-preview-explanation]');
      target.innerHTML = item.source.value;
      if (!target.textContent.trim() && !target.querySelector('img')) {
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
      const score = row.querySelector('[name="option_scores[]"]');
      radio.disabled = weighted;
      radio.hidden = weighted;
      score.disabled = !weighted;
      score.required = weighted;
      score.setAttribute('aria-label', 'Skor pilihan ' + letter);
      row.querySelector('[data-option-score]').hidden = !weighted;
      row.classList.toggle('is-weighted', weighted);
      const textarea = row.querySelector('textarea');
      const upload = row.querySelector('[data-option-image]');
      const removeOptionImage = row.querySelector('[data-remove-option-image]');
      upload.name = `option_images[${index}]`;
      upload.setAttribute('aria-label', 'Gambar pilihan ' + letter);
      removeOptionImage.name = `remove_option_images[${index}]`;
      removeOptionImage.setAttribute('aria-label', 'Hapus gambar pilihan ' + letter);
      row.querySelector('[data-option-image-label]').textContent = 'Gambar pilihan ' + letter + ' (opsional)';
      const optionImageUrl = imageSource(upload, row.querySelector('[data-option-content]').dataset.existingImage, removeOptionImage.checked, row.querySelector('[data-option-image-error]'));
      const thumbnail = row.querySelector('[data-option-image-preview]');
      thumbnail.alt = 'Gambar pilihan ' + letter;
      showImage(thumbnail, optionImageUrl);
      textarea.required = !optionImageUrl;
      radio.value = index;
      radio.setAttribute('aria-label', 'Jadikan ' + letter + ' jawaban benar');
      textarea.setAttribute('aria-label', 'Pilihan ' + letter);
      row.querySelector('[data-option-letter]').textContent = letter;
      const remove = row.querySelector('[data-remove-option]');
      remove.hidden = false;
      remove.disabled = currentRows.length <= 2;
      remove.setAttribute('aria-label', 'Hapus pilihan ' + letter);
      const option = document.createElement('div');
      option.className = 'question-preview-option' + ((weighted ? Number(score.value) === 5 : radio.checked) ? ' question-preview-option--correct' : '');
      const key = document.createElement('span');
      key.className = 'question-preview-letter';
      key.textContent = letter;
      const text = document.createElement('span');
      text.className = 'question-preview-option-content';
      text.textContent = textarea.value.trim() || (optionImageUrl ? '' : 'Pilihan ' + letter + ' belum diisi');
      if (optionImageUrl) {
        const img = document.createElement('img');
        img.className = 'question-option-image';
        img.src = optionImageUrl;
        img.alt = 'Gambar pilihan ' + letter;
        text.append(img);
      }
      option.append(key, text);
      if (weighted) {
        const badge = document.createElement('span');
        badge.className = 'badge badge--primary badge--flat';
        badge.textContent = 'Skor ' + (score.value || '—');
        option.append(badge);
      }
      previewOptions.append(option);
      if (radio.checked) selected = letter;
    });
    form.querySelector('[data-key-label]').textContent = weighted ? 'Skor per pilihan' : selected ? 'Kunci ' + selected : 'Kunci belum dipilih';
    addOption.disabled = currentRows.length >= 10;
    const explanationImageUrl = imageSource(explanationImage, previewExplanationImage.dataset.existingImage,
      form.querySelector('[data-remove-explanation-image]').checked, form.querySelector('[data-explanation-image-error]'));
    showImage(previewExplanationImage, explanationImageUrl);
    const explanationPreview = form.querySelector('[data-preview-explanation]');
    if (explanationImageUrl && !editors.get('explanation').content.textContent.trim()) explanationPreview.replaceChildren();
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
    const row = remove.closest('[data-option-row]');
    const input = row.querySelector('[data-option-image]');
    if (imagePreviews.has(input)) URL.revokeObjectURL(imagePreviews.get(input).url);
    imagePreviews.delete(input);
    row.remove();
    markDirty();
    syncPreview();
  });
  const updateEditor = (event) => {
    if (event.target.matches('[data-question-exam-search]')) return;
    markDirty();
    syncPreview();
  };
  form.addEventListener('input', updateEditor);
  form.addEventListener('change', updateEditor);
  section.addEventListener('change', () => {
    if (section.selectedOptions[0]?.dataset.code !== 'TKP' && scoring.value === 'weighted') {
      scoring.value = 'binary';
      syncPreview();
    }
  });
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
  form.addEventListener('question-editor:load', (event) => {
    const question = event.detail || {};
    form.reset();
    imagePreviews.forEach(({ url }) => URL.revokeObjectURL(url));
    imagePreviews.clear();
    if (imageUrl) URL.revokeObjectURL(imageUrl);
    if (videoUrl) URL.revokeObjectURL(videoUrl);
    imageUrl = null;
    videoUrl = null;
    title.value = question.title || '';
    section.value = question.section_id ? String(question.section_id) : '';
    level.value = question.difficulty || 'medium';
    scoring.value = question.scoring_type || 'binary';
    form.querySelector('[data-shuffle-options]').checked = question.shuffle_options !== false;
    form.querySelector('input[name="status"]').value = question.status || 'draft';
    const statusBadge = form.querySelector('[data-editor-status]');
    statusBadge.textContent = question.status === 'published' ? 'Terbit' : 'Draft';
    statusBadge.className = 'badge ' + (question.status === 'published' ? 'badge--mint' : 'badge--amber');
    for (const [name, editor] of editors) {
      editor.content.innerHTML = sanitizeArticle(question[name] || '');
      editor.source.value = sanitizeArticle(question[name] || '');
    }
    list.replaceChildren();
    (question.options || ['', '', '', '', '']).forEach((option, index) => {
      const fragment = form.querySelector('[data-option-template]').content.cloneNode(true);
      fragment.querySelector('textarea').value = option;
      fragment.querySelector('input').checked = question.correct_option === index;
      fragment.querySelector('[name="option_scores[]"]').value = question.option_scores?.[index] ?? '';
      fragment.querySelector('[data-option-image-source]').value = index;
      fragment.querySelector('[data-option-content]').dataset.existingImage = question.option_image_urls?.[index] || '';
      fragment.querySelector('[data-existing-option-image-control]').hidden = !question.option_image_urls?.[index];
      list.append(fragment);
    });
    previewImage.dataset.existingImage = question.image_url || '';
    previewVideo.dataset.existingVideo = question.video_url || '';
    previewExplanationImage.dataset.existingImage = question.explanation_image_url || '';
    form.querySelector('[data-existing-explanation-image-control]').hidden = !question.explanation_image_url;
    form.querySelector('[data-existing-image-control]')?.toggleAttribute('hidden', !question.image_url);
    form.querySelector('[data-existing-video-control]')?.toggleAttribute('hidden', !question.video_url);
    videoError.hidden = true;
    videoError.textContent = '';
    dirty = false;
    submitting = false;
    form.dataset.dirty = 'false';
    form.querySelector('[data-question-save-note]').textContent = 'Perubahan disimpan saat Anda menekan Simpan draft atau Terbitkan soal.';
    window.LIcons?.hydrate(list);
    syncPreview();
  });
  form.addEventListener('question-editor:saved', () => {
    dirty = false;
    submitting = false;
    form.dataset.dirty = 'false';
  });
  form.addEventListener('question-editor:submit-failed', () => { submitting = false; });
  form.addEventListener('submit', () => { syncPreview(); submitting = true; });
  window.addEventListener('beforeunload', (event) => {
    if (dirty && !submitting) { event.preventDefault(); event.returnValue = ''; }
  });
  syncPreview();
});

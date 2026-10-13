const allowedTags = new Set(['p', 'div', 'br', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'strong', 'b', 'em', 'i', 'u', 's', 'strike', 'span', 'font', 'ul', 'ol', 'li', 'blockquote', 'a', 'hr', 'pre', 'code', 'table', 'thead', 'tbody', 'tr', 'td', 'th']);
const droppedTags = new Set(['script', 'style', 'iframe', 'object', 'embed', 'svg', 'math', 'img', 'video', 'audio', 'form', 'input', 'textarea', 'button', 'noscript', 'template', 'link', 'meta']);
const colorPattern = /^(?:#[0-9a-f]{3}(?:[0-9a-f]{3})?|rgb\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*\)|black|white|red|blue|green|transparent)$/i;
const safeLink = (href) => /^(?:https?:\/\/|mailto:)[^\s\x00-\x1f\x7f]+$/i.test(href);

export function sanitizeArticle(html) {
  const input = document.createElement('template');
  input.innerHTML = html;
  const output = document.createElement('div');
  const walk = (parent, destination, depth = 0) => {
    if (depth > 60) return;
    for (const node of parent.childNodes) {
      if (node.nodeType === Node.TEXT_NODE) {
        destination.append(document.createTextNode(node.textContent));
        continue;
      }
      if (node.nodeType !== Node.ELEMENT_NODE) continue;
      const tag = node.tagName.toLowerCase();
      if (droppedTags.has(tag)) continue;
      if (!allowedTags.has(tag)) { walk(node, destination, depth + 1); continue; }
      const clean = document.createElement(tag === 'font' ? 'span' : tag);
      for (const declaration of (node.getAttribute('style') || '').split(';')) {
        const colon = declaration.indexOf(':');
        if (colon < 0) continue;
        const property = declaration.slice(0, colon).trim().toLowerCase();
        const value = declaration.slice(colon + 1).trim().toLowerCase();
        const permitted = {
          'text-align': /^(left|center|right|justify)$/,
          'font-weight': /^(bold|normal|400|700)$/,
          'font-style': /^(italic|normal)$/,
          'text-decoration': /^(underline|line-through|underline line-through|none)$/,
          'text-decoration-line': /^(underline|line-through|underline line-through|none)$/,
          'font-size': /^(12|14|16|18|24|32|40)px$/,
          'color': colorPattern,
          'background-color': colorPattern,
        }[property];
        if (permitted?.test(value)) clean.style.setProperty(property, value);
      }
      if (tag === 'font') {
        const color = node.getAttribute('color') || '';
        if (colorPattern.test(color)) clean.style.color = color;
        const size = { 1: 12, 2: 14, 3: 16, 4: 18, 5: 24, 6: 32, 7: 40 }[node.getAttribute('size')];
        if (size) clean.style.fontSize = `${size}px`;
      }
      if (tag === 'a') {
        const href = (node.getAttribute('href') || '').trim();
        if (safeLink(href)) { clean.setAttribute('href', href); clean.setAttribute('rel', 'noopener noreferrer'); }
      }
      if (tag === 'td' || tag === 'th') {
        for (const attribute of ['colspan', 'rowspan']) {
          const value = node.getAttribute(attribute) || '';
          if (/^\d+$/.test(value) && Number(value) >= 1 && Number(value) <= 10) clean.setAttribute(attribute, value);
        }
      }
      walk(node, clean, depth + 1);
      destination.append(clean);
    }
  };
  walk(input.content, output);
  return output.innerHTML;
}

document.querySelectorAll('[data-article-editor]').forEach((form) => {
  const editor = form.querySelector('[data-editor-content]');
  const source = form.querySelector('[data-editor-source]');
  const toolbar = form.querySelector('[data-editor-toolbar]');
  const preview = form.querySelector('[data-preview-body]');
  const title = form.querySelector('#article-title');
  const excerpt = form.querySelector('#article-excerpt');
  const section = form.querySelector('#article-section');
  const cover = form.querySelector('#article-cover');
  const coverPreview = form.querySelector('[data-preview-cover]');
  const removeCover = form.querySelector('[data-remove-cover]');
  const message = form.querySelector('[data-editor-message]');
  const dialog = document.querySelector('[data-link-dialog]');
  const linkInput = dialog.querySelector('#article-link-url');
  let range = null;
  let coverObjectUrl = null;
  let dirty = false;
  let submitting = false;

  editor.innerHTML = sanitizeArticle(source.value);
  editor.hidden = false;
  source.hidden = true;
  toolbar.hidden = false;

  const rememberSelection = () => {
    const selection = window.getSelection();
    if (selection.rangeCount && editor.contains(selection.getRangeAt(0).commonAncestorContainer)) range = selection.getRangeAt(0).cloneRange();
  };
  const restoreSelection = () => {
    editor.focus();
    const selection = window.getSelection();
    if (!range || !editor.contains(range.commonAncestorContainer)) {
      range = document.createRange(); range.selectNodeContents(editor); range.collapse(false);
    }
    selection.removeAllRanges(); selection.addRange(range);
  };
  const notify = (text) => { message.textContent = text; message.hidden = !text; };
  const plainText = () => preview.innerText.replace(/[\s\u00a0\u200b]+/g, ' ').trim();
  const sync = (changed = false) => {
    source.value = sanitizeArticle(editor.innerHTML);
    preview.innerHTML = source.value;
    const text = plainText();
    const count = text ? text.split(/\s+/u).length : 0;
    if (!text) { const placeholder = document.createElement('p'); placeholder.className = 'article-placeholder'; placeholder.textContent = 'Mulai menulis untuk melihat preview artikel di sini…'; preview.replaceChildren(placeholder); }
    form.querySelector('[data-word-count]').textContent = `${count} kata`;
    form.querySelector('[data-reading-time]').textContent = `${Math.max(1, Math.ceil(count / 200))} menit baca`;
    form.querySelector('[data-preview-title]').textContent = title.value.trim() || 'Judul artikel Anda';
    form.querySelector('[data-preview-excerpt]').textContent = excerpt.value.trim();
    form.querySelector('[data-preview-section]').textContent = section.selectedOptions[0]?.value ? section.selectedOptions[0].textContent : 'Bidang artikel';
    const coverUrl = coverObjectUrl || (removeCover?.checked ? '' : coverPreview.dataset.existingCover);
    coverPreview.hidden = !coverUrl;
    if (coverUrl) coverPreview.src = coverUrl; else coverPreview.removeAttribute('src');
    if (changed) { dirty = true; form.querySelector('[data-save-note]').textContent = 'Ada perubahan yang belum disimpan'; }
    return count;
  };
  const updateToolbar = () => {
    if (!editor.contains(window.getSelection()?.anchorNode)) return;
    for (const button of toolbar.querySelectorAll('[aria-pressed][data-command]')) {
      try { button.setAttribute('aria-pressed', String(document.queryCommandState(button.dataset.command))); } catch { /* Unsupported commands remain inactive. */ }
    }
    let block = window.getSelection().anchorNode;
    if (block?.nodeType === Node.TEXT_NODE) block = block.parentElement;
    while (block && block !== editor && !['P', 'H2', 'H3', 'H4', 'BLOCKQUOTE'].includes(block.tagName)) block = block.parentElement;
    toolbar.querySelector('[data-command-select="formatBlock"]').value = block?.tagName?.toLowerCase() === 'div' || block === editor ? 'p' : block?.tagName?.toLowerCase() || 'p';
    const fontSize = document.queryCommandValue('fontSize');
    if (/^[1-7]$/.test(fontSize)) toolbar.querySelector('[data-command-select="fontSize"]').value = fontSize;
  };
  const execute = (command, value = null) => {
    restoreSelection();
    document.execCommand('defaultParagraphSeparator', false, 'p');
    document.execCommand('styleWithCSS', false, command === 'hiliteColor');
    const applied = document.execCommand(command, false, value);
    notify(applied ? '' : 'Format ini belum diterapkan. Pilih teks atau letakkan kursor di dalam artikel.');
    rememberSelection(); sync(true); updateToolbar();
  };

  toolbar.addEventListener('mousedown', (event) => { if (event.target.closest('button')) event.preventDefault(); });
  toolbar.addEventListener('click', (event) => {
    const button = event.target.closest('button');
    if (!button) return;
    if (button.dataset.command) execute(button.dataset.command);
    if (button.hasAttribute('data-editor-table')) execute('insertHTML', '<table><tbody><tr><th>Kolom 1</th><th>Kolom 2</th></tr><tr><td>Isi</td><td>Isi</td></tr><tr><td>Isi</td><td>Isi</td></tr></tbody></table><p><br></p>');
    if (button.hasAttribute('data-editor-link')) {
      rememberSelection();
      if (!range || range.collapsed) { notify('Blok teks yang ingin diberi link terlebih dahulu.'); return; }
      linkInput.value = '';
      dialog.querySelector('[data-link-error]').textContent = '';
      dialog.showModal(); linkInput.focus();
    }
  });
  toolbar.querySelectorAll('[data-command-select]').forEach((select) => select.addEventListener('change', () => execute(select.dataset.commandSelect, select.value)));
  toolbar.querySelectorAll('[data-command-color]').forEach((input) => input.addEventListener('input', () => {
    input.closest('label').style.setProperty('--color', input.value);
    execute(input.dataset.commandColor, input.value);
  }));
  document.addEventListener('selectionchange', () => { rememberSelection(); updateToolbar(); });
  editor.addEventListener('input', () => { rememberSelection(); sync(true); updateToolbar(); });
  editor.addEventListener('paste', (event) => {
    event.preventDefault(); rememberSelection();
    const html = event.clipboardData.getData('text/html');
    if (html) execute('insertHTML', sanitizeArticle(html));
    else execute('insertText', event.clipboardData.getData('text/plain'));
  });
  editor.addEventListener('drop', (event) => { event.preventDefault(); notify('Tempel teks dengan Ctrl+V. Gambar dapat ditambahkan melalui cover artikel.'); });
  preview.addEventListener('click', (event) => { if (event.target.closest('a')) event.preventDefault(); });
  [title, excerpt, section, form.querySelector('#article-slug')].forEach((input) => input.addEventListener('input', () => sync(true)));
  removeCover?.addEventListener('change', () => sync(true));
  cover.addEventListener('change', () => {
    if (coverObjectUrl) URL.revokeObjectURL(coverObjectUrl);
    coverObjectUrl = null;
    const file = cover.files[0];
    if (file && (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 5 * 1024 * 1024)) {
      cover.value = ''; notify('Pilih cover JPG, PNG, atau WebP berukuran maksimal 5 MB.');
    } else {
      if (file) coverObjectUrl = URL.createObjectURL(file);
      notify('');
    }
    sync(true);
  });
  dialog.querySelector('[data-link-cancel]').addEventListener('click', () => { dialog.close(); restoreSelection(); });
  dialog.addEventListener('cancel', () => { restoreSelection(); });
  dialog.querySelector('[data-link-form]').addEventListener('submit', (event) => {
    event.preventDefault();
    const href = linkInput.value.trim();
    if (!safeLink(href)) { dialog.querySelector('[data-link-error]').textContent = 'Gunakan alamat https://, http://, atau mailto: yang valid.'; return; }
    dialog.close(); execute('createLink', href);
  });
  form.addEventListener('submit', (event) => {
    const wordCount = sync();
    if (!title.value.trim() || !section.value) {
      event.preventDefault(); notify('Lengkapi judul dan bidang artikel.'); (!title.value.trim() ? title : section).focus(); return;
    }
    if (event.submitter?.value === 'published' && wordCount === 0) {
      event.preventDefault(); notify('Isi artikel tidak boleh kosong saat diterbitkan.'); editor.focus(); return;
    }
    submitting = true;
  });
  window.addEventListener('beforeunload', (event) => { if (dirty && !submitting) { event.preventDefault(); event.returnValue = ''; } });
  sync();
});

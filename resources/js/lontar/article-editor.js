import { buildArticleToc, articleText } from './article-reader.js';
import { applyListFormat, setListStart, selectedListItems, indentListItems, outdentListItems, listTypes, listSuffixes } from './article-lists.js';

const allowedTags = new Set(['p', 'div', 'br', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'strong', 'b', 'em', 'i', 'u', 's', 'strike', 'sub', 'sup', 'mark', 'span', 'font', 'ul', 'ol', 'li', 'blockquote', 'a', 'hr', 'pre', 'code', 'table', 'caption', 'thead', 'tbody', 'tfoot', 'tr', 'td', 'th', 'figure', 'figcaption', 'img', 'details', 'summary']);
const droppedTags = new Set(['script', 'style', 'iframe', 'object', 'embed', 'svg', 'math', 'video', 'audio', 'form', 'input', 'textarea', 'button', 'noscript', 'template', 'link', 'meta']);
const colorPattern = /^(?:#[0-9a-f]{3}(?:[0-9a-f]{3})?|rgb\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*\)|black|white|red|blue|green|transparent)$/i;
const safeLink = (href) => /^(?:https?:\/\/|mailto:)[^\s\x00-\x1f\x7f]+$/i.test(href);
const safeWebUrl = (url) => {
  if (!/^https?:\/\//i.test(url) || /[\s\x00-\x1f\x7f\\]/.test(url)) return false;
  try { return ['http:', 'https:'].includes(new URL(url).protocol); } catch { return false; }
};
const safeImageUrl = (url) => safeWebUrl(url) || /^\/(?!\/)[a-z0-9/_-]+\.(?:jpe?g|png|webp)$/i.test(url);

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
      if (tag === 'img') {
        const src = (node.getAttribute('src') || '').trim();
        if (safeImageUrl(src)) {
          const image = document.createElement('img');
          image.setAttribute('src', src);
          image.setAttribute('alt', node.getAttribute('alt') || '');
          image.setAttribute('loading', 'lazy');
          destination.append(image);
        }
        continue;
      }
      const clean = document.createElement(tag === 'font' ? 'span' : tag);
      if (tag === 'div' && ['info', 'tip', 'warning'].includes(node.dataset.callout)) clean.dataset.callout = node.dataset.callout;
      if (tag === 'figure') {
        if (['left', 'center', 'right'].includes(node.dataset.align)) clean.dataset.align = node.dataset.align;
        if (['25', '50', '75', '100'].includes(node.dataset.width)) clean.dataset.width = node.dataset.width;
      }
      if (tag === 'ol' && /^\d{1,4}$/.test(node.getAttribute('start') || '') && Number(node.getAttribute('start')) > 0) clean.setAttribute('start', node.getAttribute('start'));
      if (tag === 'ol') {
        if (listTypes.includes(node.getAttribute('type'))) {
          clean.setAttribute('type', node.getAttribute('type'));
          clean.setAttribute('data-list-type', node.getAttribute('type'));
        }
        if (listSuffixes.includes(node.dataset.listSuffix)) clean.dataset.listSuffix = node.dataset.listSuffix;
      }
      if (tag === 'li' && /^[1-9]\d{0,3}$/.test(node.getAttribute('value') || '')) clean.setAttribute('value', node.getAttribute('value'));
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
        if (safeLink(href)) {
          clean.setAttribute('href', href); clean.setAttribute('rel', 'noopener noreferrer');
          if (node.getAttribute('target') === '_blank') clean.setAttribute('target', '_blank');
        }
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
  const coverFigure = form.querySelector('[data-preview-cover-figure]');
  const coverCredit = form.querySelector('[data-preview-cover-credit]');
  const coverSource = form.querySelector('#article-cover-source');
  const coverSourceUrl = form.querySelector('#article-cover-source-url');
  const removeCover = form.querySelector('[data-remove-cover]');
  const message = form.querySelector('[data-editor-message]');
  const dialog = document.querySelector('[data-link-dialog]');
  const linkInput = dialog.querySelector('#article-link-url');
  const imageDialog = document.querySelector('[data-image-dialog]');
  const imageForm = imageDialog.querySelector('[data-image-form]');
  const imageFile = imageDialog.querySelector('#article-inline-image');
  const imageAlt = imageDialog.querySelector('#article-image-alt');
  const imageSource = imageDialog.querySelector('#article-image-source');
  const imageSourceUrl = imageDialog.querySelector('#article-image-source-url');
  const imageCaption = imageDialog.querySelector('#article-image-caption');
  const imageWidth = imageDialog.querySelector('#article-image-width');
  const imageAlign = imageDialog.querySelector('#article-image-align');
  const tableDialog = document.querySelector('[data-table-dialog]');
  const publishAt = form.querySelector('#article-publish-at');
  const imageError = imageDialog.querySelector('[data-image-error]');
  const imageSubmit = imageDialog.querySelector('[data-image-submit]');
  const imageRemove = imageDialog.querySelector('[data-image-remove]');
  let editingImage = null;
  let uploadingImage = false;
  let range = null;
  let coverObjectUrl = null;
  let dirty = false;
  let submitting = false;
  let htmlMode = false;
  let draftTimer;
  const history = [sanitizeArticle(source.value)];
  let historyIndex = 0;
  let restoringHistory = false;

  editor.innerHTML = sanitizeArticle(source.value);
  editor.hidden = false;
  source.hidden = true;
  toolbar.hidden = false;
  form.querySelector('[data-editor-modes]').hidden = false;
  form.querySelector('[data-preview-controls]').hidden = false;

  const prepareFigures = () => editor.querySelectorAll('figure').forEach((figure) => {
    if (!figure.querySelector('img')) {
      if (!figure.textContent.trim()) figure.remove();
      return;
    }
    figure.removeAttribute('contenteditable');
    figure.tabIndex = 0;
    figure.setAttribute('role', 'button');
    figure.setAttribute('aria-label', 'Ubah gambar dan sumber');
  });

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
  const sync = (changed = false) => {
    prepareFigures();
    const body = sanitizeArticle(htmlMode ? source.value : editor.innerHTML);
    if (!htmlMode) source.value = body;
    preview.innerHTML = body;
    buildArticleToc(preview, form.querySelector('[data-preview-toc]'), 'preview');
    const text = articleText(body);
    const count = text ? text.split(/\s+/u).length : 0;
    if (!text && !preview.querySelector('img')) { const placeholder = document.createElement('p'); placeholder.className = 'article-placeholder'; placeholder.textContent = 'Mulai menulis untuk melihat preview artikel di sini…'; preview.replaceChildren(placeholder); }
    form.querySelector('[data-word-count]').textContent = `${count} kata`;
    form.querySelector('[data-character-count]').textContent = `${Array.from(text).length} karakter`;
    form.querySelector('[data-reading-time]').textContent = `${Math.max(1, Math.ceil(count / 200))} menit baca`;
    form.querySelector('[data-preview-title]').textContent = title.value.trim() || 'Judul artikel Anda';
    form.querySelector('[data-preview-excerpt]').textContent = excerpt.value.trim();
    form.querySelector('[data-preview-section]').textContent = section.selectedOptions[0]?.value ? section.selectedOptions[0].textContent : 'Bidang artikel';
    const coverUrl = coverObjectUrl || (removeCover?.checked ? '' : coverPreview.dataset.existingCover);
    coverPreview.hidden = !coverUrl;
    coverFigure.hidden = !coverUrl;
    if (coverUrl) coverPreview.src = coverUrl; else coverPreview.removeAttribute('src');
    const creditText = coverSource.value.trim() || coverSourceUrl.value.trim();
    coverCredit.replaceChildren();
    coverCredit.hidden = !creditText;
    if (creditText) {
      coverCredit.append('Sumber: ');
      if (safeWebUrl(coverSourceUrl.value.trim())) {
        const link = document.createElement('a');
        link.href = coverSourceUrl.value.trim();
        link.textContent = creditText;
        link.rel = 'noopener noreferrer';
        coverCredit.append(link);
      } else coverCredit.append(creditText);
    }
    const scheduled = publishAt.value ? new Date(`${publishAt.value}+08:00`) : null;
    form.querySelector('[data-publish-note]').textContent = scheduled && scheduled > new Date() ? `Akan terbit ${scheduled.toLocaleString('id-ID', { timeZone: 'Asia/Makassar', dateStyle: 'long', timeStyle: 'short' })} WITA.` : '';
    if (changed) {
      if (!restoringHistory && body !== history[historyIndex]) {
        history.splice(historyIndex + 1);
        history.push(body);
        if (history.length > 100) history.shift();
        historyIndex = history.length - 1;
      }
      dirty = true;
      form.querySelector('[data-save-note]').textContent = 'Ada perubahan yang belum disimpan';
      clearTimeout(draftTimer);
      draftTimer = setTimeout(saveLocalDraft, 600);
    }
    return count;
  };
  const updateToolbar = () => {
    if (htmlMode || !editor.contains(window.getSelection()?.anchorNode)) return;
    for (const button of toolbar.querySelectorAll('[aria-pressed][data-command]')) {
      try { button.setAttribute('aria-pressed', String(document.queryCommandState(button.dataset.command))); } catch { /* Unsupported commands remain inactive. */ }
    }
    let block = window.getSelection().anchorNode;
    if (block?.nodeType === Node.TEXT_NODE) block = block.parentElement;
    while (block && block !== editor && !['P', 'H2', 'H3', 'H4', 'BLOCKQUOTE'].includes(block.tagName)) block = block.parentElement;
    toolbar.querySelector('[data-command-select="formatBlock"]').value = block?.tagName?.toLowerCase() === 'div' || block === editor ? 'p' : block?.tagName?.toLowerCase() || 'p';
    const fontSize = document.queryCommandValue('fontSize');
    if (/^[1-7]$/.test(fontSize)) toolbar.querySelector('[data-command-select="fontSize"]').value = fontSize;
    const list = selectedListItems(editor, range)[0]?.parentElement;
    const ordered = list?.tagName === 'OL';
    toolbar.querySelector('[data-list-format]').value = ordered ? `${list.getAttribute('type') || '1'}:${list.dataset.listSuffix || 'period'}` : '';
    toolbar.querySelector('[data-list-start]').disabled = !ordered;
    toolbar.querySelector('[data-list-start]').value = ordered ? list.getAttribute('start') || '1' : '1';
    toolbar.querySelector('[data-list-restart]').disabled = !ordered;
  };
  const changeListLevel = direction => {
    restoreSelection();
    const collapsed = range.collapsed;
    const bookmark = { start: range.startContainer, startOffset: range.startOffset, end: range.endContainer, endOffset: range.endOffset };
    const items = selectedListItems(editor, range);
    const changed = direction === 'indent' ? indentListItems(items) : outdentListItems(items);
    if (!changed) {
      notify(direction === 'indent' ? 'Pilih item setelah item pertama pada tingkat yang sama, lalu tambah tingkat.' : 'Pilih item pada subtingkat untuk menaikkannya ke tingkat induk.');
      return false;
    }
    range = document.createRange();
    if (items.some(item => item.contains(bookmark.start)) && items.some(item => item.contains(bookmark.end))) {
      range.setStart(bookmark.start, bookmark.startOffset); range.setEnd(bookmark.end, bookmark.endOffset);
    } else {
      range.setStart(items[0], 0);
      if (collapsed) range.collapse(true); else range.setEnd(items.at(-1), items.at(-1).childNodes.length);
    }
    notify(''); sync(true); restoreSelection(); updateToolbar();
    return true;
  };
  const execute = (command, value = null) => {
    if (command === 'undo' || command === 'redo') {
      const nextIndex = historyIndex + (command === 'undo' ? -1 : 1);
      if (nextIndex < 0 || nextIndex >= history.length) return;
      historyIndex = nextIndex;
      editor.innerHTML = history[historyIndex]; source.value = history[historyIndex];
      range = null; restoringHistory = true; sync(true); restoringHistory = false;
      restoreSelection(); updateToolbar(); return;
    }
    restoreSelection();
    document.execCommand('defaultParagraphSeparator', false, 'p');
    document.execCommand('styleWithCSS', false, command === 'hiliteColor');
    const applied = document.execCommand(command, false, value);
    notify(applied ? '' : 'Format ini belum diterapkan. Pilih teks atau letakkan kursor di dalam artikel.');
    rememberSelection(); sync(true); updateToolbar();
  };
  const replaceElement = (element, html) => {
    const replacement = document.createElement('template'); replacement.innerHTML = html;
    const first = replacement.content.firstChild;
    element.replaceWith(replacement.content);
    range = document.createRange(); range.selectNodeContents(first); range.collapse(false);
    sync(true); restoreSelection(); updateToolbar();
  };
  const insertBlock = html => {
    restoreSelection();
    const template = document.createElement('template'); template.innerHTML = sanitizeArticle(html);
    const last = template.content.lastChild;
    if (!range.collapsed) range.deleteContents();
    let block = range.startContainer;
    while (block.parentNode && block.parentNode !== editor && block !== editor) block = block.parentNode;
    if (block === editor) range.insertNode(template.content);
    else block.after(template.content);
    range = document.createRange(); range.selectNodeContents(last); range.collapse(false);
    sync(true); restoreSelection(); updateToolbar();
  };

  const openImageDialog = (image = null) => {
    rememberSelection();
    editingImage = image;
    imageForm.reset();
    imageError.textContent = '';
    imageAlt.value = image?.getAttribute('alt') || '';
    const figure = image?.closest('figure');
    const captions = Array.from(figure?.querySelectorAll('figcaption > p, figcaption:not(:has(p))') || []);
    const credit = captions.find(caption => /^Sumber:\s*/i.test(caption.textContent));
    imageSource.value = credit?.textContent.replace(/^Sumber:\s*/i, '').trim() || '';
    imageSourceUrl.value = credit?.querySelector('a')?.getAttribute('href') || '';
    imageCaption.value = captions.filter(caption => caption !== credit).map(caption => caption.textContent).join(' ');
    imageWidth.value = figure?.dataset.width || '100';
    imageAlign.value = figure?.dataset.align || 'center';
    imageRemove.hidden = !image;
    imageSubmit.textContent = image ? 'Simpan perubahan' : 'Sisipkan gambar';
    imageDialog.querySelector('#article-image-title').textContent = image ? 'Ubah gambar dan sumber' : 'Sisipkan gambar';
    imageDialog.showModal();
    (image ? imageSource : imageFile).focus();
  };
  toolbar.addEventListener('mousedown', (event) => { if (event.target.closest('button')) event.preventDefault(); });
  toolbar.addEventListener('click', (event) => {
    const button = event.target.closest('button');
    if (!button) return;
    if (button.dataset.command) execute(button.dataset.command);
    if (button.dataset.listLevel) changeListLevel(button.dataset.listLevel);
    if (button.hasAttribute('data-list-restart')) {
      restoreSelection();
      if (setListStart(selectedListItems(editor, range)[0]?.parentElement, 1)) { sync(true); updateToolbar(); }
    }
    if (button.hasAttribute('data-list-example')) insertBlock('<ol type="1" data-list-suffix="period"><li>UTAMA<ol type="1" data-list-suffix="paren"><li>Sub 1</li><li>Sub 2</li></ol></li><li>UTAMA<ol type="a" data-list-suffix="paren"><li>Sub a</li><li>Sub b</li></ol></li></ol><p><br></p>');
    if (button.hasAttribute('data-editor-table')) openTableDialog();
    if (button.hasAttribute('data-editor-image')) openImageDialog();
    if (button.hasAttribute('data-editor-link')) {
      rememberSelection();
      if (!range || range.collapsed) { notify('Blok teks yang ingin diberi link terlebih dahulu.'); return; }
      let node = range.commonAncestorContainer;
      if (node.nodeType === Node.TEXT_NODE) node = node.parentElement;
      const link = node.closest('a');
      linkInput.value = link?.getAttribute('href') || '';
      dialog.querySelector('[data-link-new-tab]').checked = link?.getAttribute('target') === '_blank';
      dialog.querySelector('[data-link-error]').textContent = '';
      dialog.showModal(); linkInput.focus();
    }
  });
  toolbar.querySelectorAll('[data-command-select]').forEach((select) => select.addEventListener('change', () => execute(select.dataset.commandSelect, select.value)));
  toolbar.querySelector('[data-list-format]').addEventListener('change', event => {
    const [type, suffix] = event.target.value.split(':');
    if (!listTypes.includes(type) || !listSuffixes.includes(suffix)) return;
    restoreSelection();
    let list = selectedListItems(editor, range)[0]?.parentElement;
    if (list?.tagName !== 'OL') {
      document.execCommand('insertOrderedList', false);
      rememberSelection(); list = selectedListItems(editor, range)[0]?.parentElement;
    }
    if (applyListFormat(list, type, suffix)) { notify(''); sync(true); updateToolbar(); }
    else notify('Letakkan kursor di dalam item daftar untuk mengatur format numbering.');
  });
  toolbar.querySelector('[data-list-start]').addEventListener('change', event => {
    const value = event.target.value;
    restoreSelection();
    if (!setListStart(selectedListItems(editor, range)[0]?.parentElement, value)) {
      notify('Nomor awal harus berupa bilangan bulat 1–9999.'); updateToolbar(); return;
    }
    notify(''); sync(true); updateToolbar();
  });
  toolbar.querySelector('[data-list-start]').addEventListener('keydown', event => {
    if (event.key === 'Enter') {
      event.preventDefault(); event.target.dispatchEvent(new Event('change', { bubbles: true }));
    }
  });
  toolbar.querySelectorAll('[data-command-color]').forEach((input) => input.addEventListener('input', () => {
    input.closest('label').style.setProperty('--color', input.value);
    execute(input.dataset.commandColor, input.value);
  }));
  document.addEventListener('selectionchange', () => { rememberSelection(); updateToolbar(); });
  editor.addEventListener('input', () => { rememberSelection(); sync(true); updateToolbar(); });
  editor.addEventListener('click', (event) => {
    const image = event.target.closest('img') || event.target.closest('figure')?.querySelector('img');
    if (image) { event.preventDefault(); openImageDialog(image); }
  });
  editor.addEventListener('keydown', (event) => {
    if (event.key === 'Tab') {
      rememberSelection();
      if (selectedListItems(editor, range).length && changeListLevel(event.shiftKey ? 'outdent' : 'indent')) event.preventDefault();
    }
    if (event.key === 'Enter' && !event.shiftKey && !event.ctrlKey && !event.metaKey) {
      rememberSelection();
      const item = selectedListItems(editor, range)[0];
      if (range?.collapsed && item && !item.textContent.trim() && !item.querySelector('img, ol, ul') && item.parentElement.parentElement.tagName === 'LI') {
        if (changeListLevel('outdent')) event.preventDefault();
      }
    }
    if (event.key === 'Enter' && (event.ctrlKey || event.metaKey)) {
      event.preventDefault();
      const block = window.getSelection()?.anchorNode?.parentElement?.closest('pre, details, [data-callout], table');
      if (block && editor.contains(block)) {
        insertBlock('<p><br></p>');
      }
    }
    if (['Enter', ' '].includes(event.key) && event.target.matches('figure[role="button"]')) {
      event.preventDefault(); openImageDialog(event.target.querySelector('img'));
    }
  });
  editor.addEventListener('paste', (event) => {
    event.preventDefault(); rememberSelection();
    const html = event.clipboardData.getData('text/html');
    if (html) execute('insertHTML', sanitizeArticle(html));
    else execute('insertText', event.clipboardData.getData('text/plain'));
  });
  editor.addEventListener('drop', (event) => { event.preventDefault(); notify('Gunakan tombol Sisipkan gambar dan sumber pada toolbar untuk menambahkan gambar.'); });
  [title, excerpt, section, coverSource, coverSourceUrl, publishAt, form.querySelector('#article-slug')].forEach((input) => input.addEventListener('input', () => sync(true)));
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
    const link = document.createElement('a');
    link.href = href;
    link.rel = 'noopener noreferrer';
    if (dialog.querySelector('[data-link-new-tab]').checked) link.target = '_blank';
    const fragment = range.cloneContents();
    fragment.querySelectorAll('a').forEach(anchor => anchor.replaceWith(...anchor.childNodes));
    link.append(fragment);
    dialog.close(); execute('insertHTML', sanitizeArticle(link.outerHTML));
  });
  imageDialog.querySelector('[data-image-cancel]').addEventListener('click', () => { imageDialog.close(); restoreSelection(); });
  imageDialog.addEventListener('cancel', (event) => {
    if (uploadingImage) event.preventDefault(); else restoreSelection();
  });
  imageRemove.addEventListener('click', () => {
    imageDialog.close();
    if (editingImage && editor.contains(editingImage)) replaceElement(editingImage.closest('figure') || editingImage, '<p><br></p>');
  });
  imageForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (uploadingImage) return;
    imageError.textContent = '';
    const file = imageFile.files[0];
    const sourceUrl = imageSourceUrl.value.trim();
    if (!file && !editingImage) { imageError.textContent = 'Pilih gambar terlebih dahulu.'; imageFile.focus(); return; }
    if (file && (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 5 * 1024 * 1024)) {
      imageError.textContent = 'Pilih gambar JPG, PNG, atau WebP berukuran maksimal 5 MB.'; return;
    }
    if (sourceUrl && !safeWebUrl(sourceUrl)) { imageError.textContent = 'Tautan sumber harus berupa alamat http:// atau https:// yang valid.'; imageSourceUrl.focus(); return; }
    const submitLabel = imageSubmit.textContent;
    uploadingImage = true;
    imageForm.querySelectorAll('input, select, button').forEach((input) => { input.disabled = true; });
    imageSubmit.textContent = 'Menyimpan gambar…';
    try {
      let imageUrl = editingImage?.getAttribute('src');
      if (file) {
        const data = new FormData();
        data.append('image', file);
        const response = await fetch(form.dataset.imageUploadUrl, {
          method: 'POST', credentials: 'same-origin', body: data,
          headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value },
          signal: AbortSignal.timeout(60000),
        });
        const result = await response.json().catch(() => null);
        if (!response.ok) {
          const errorText = response.status === 419 ? 'Sesi telah berakhir. Salin isi artikel sebelum memuat ulang halaman.' : response.status === 413 ? 'Gambar terlalu besar untuk server. Pilih file yang lebih kecil.' : response.status === 429 ? 'Terlalu banyak unggahan. Tunggu sebentar lalu coba lagi.' : result?.errors?.image?.[0] || 'Gambar gagal diunggah. Silakan coba lagi.';
          throw new Error(errorText);
        }
        imageUrl = result?.url;
      }
      if (!imageUrl || !safeImageUrl(imageUrl)) throw new Error('Alamat gambar tidak valid. Silakan unggah ulang.');
      const figure = document.createElement('figure');
      figure.dataset.width = imageWidth.value;
      figure.dataset.align = imageAlign.value;
      const image = document.createElement('img');
      image.setAttribute('src', imageUrl);
      image.setAttribute('alt', imageAlt.value.trim());
      figure.append(image);
      const caption = document.createElement('figcaption');
      if (imageCaption.value.trim()) {
        const description = document.createElement('p');
        description.textContent = imageCaption.value.trim(); caption.append(description);
      }
      const creditText = imageSource.value.trim() || sourceUrl;
      if (creditText) {
        const credit = document.createElement('p');
        credit.append('Sumber: ');
        if (sourceUrl) {
          const link = document.createElement('a');
          link.setAttribute('href', sourceUrl); link.textContent = creditText; credit.append(link);
        } else credit.append(creditText);
        caption.append(credit);
      }
      if (caption.childNodes.length) figure.append(caption);
      const replacing = !!editingImage;
      imageDialog.close();
      if (replacing) replaceElement(editingImage.closest('figure') || editingImage, figure.outerHTML);
      else insertBlock(figure.outerHTML + '<p><br></p>');
    } catch (error) {
      imageError.textContent = ['TimeoutError', 'TypeError'].includes(error.name) ? 'Unggahan belum berhasil. Periksa koneksi lalu coba lagi.' : error.message;
    } finally {
      uploadingImage = false;
      imageForm.querySelectorAll('input, select, button').forEach((input) => { input.disabled = false; });
      imageSubmit.textContent = submitLabel;
    }
  });
  let selectedCell = null;
  let selectedTable = null;
  const openTableDialog = () => {
    rememberSelection();
    let node = range?.startContainer;
    if (node?.nodeType === Node.TEXT_NODE) node = node.parentElement;
    selectedCell = node?.closest?.('td, th');
    selectedTable = node?.closest?.('table');
    tableDialog.querySelector('[data-table-create]').hidden = !!selectedTable;
    tableDialog.querySelector('[data-table-actions]').hidden = !selectedTable;
    tableDialog.querySelector('[data-table-submit]').hidden = !!selectedTable;
    tableDialog.querySelector('[data-table-error]').textContent = '';
    tableDialog.querySelectorAll('[data-table-create] input').forEach(input => { input.disabled = !!selectedTable; });
    tableDialog.showModal();
  };
  tableDialog.querySelector('[data-table-cancel]').addEventListener('click', () => { tableDialog.close(); restoreSelection(); });
  tableDialog.querySelector('[data-table-form]').addEventListener('submit', event => {
    event.preventDefault();
    const rows = Number(tableDialog.querySelector('#article-table-rows').value);
    const columns = Number(tableDialog.querySelector('#article-table-cols').value);
    if (!Number.isInteger(rows) || !Number.isInteger(columns) || rows < 1 || rows > 30 || columns < 1 || columns > 10) {
      tableDialog.querySelector('[data-table-error]').textContent = 'Gunakan 1–30 baris dan 1–10 kolom.'; return;
    }
    const table = document.createElement('table');
    const captionText = tableDialog.querySelector('#article-table-caption').value.trim();
    if (captionText) table.createCaption().textContent = captionText;
    const body = table.createTBody();
    for (let rowIndex = 0; rowIndex < rows; rowIndex++) {
      const row = body.insertRow();
      for (let column = 0; column < columns; column++) {
        const header = rowIndex === 0 && tableDialog.querySelector('[data-table-header]').checked;
        const cell = document.createElement(header ? 'th' : 'td');
        cell.textContent = header ? `Kolom ${column + 1}` : 'Isi'; row.append(cell);
      }
    }
    tableDialog.close(); insertBlock(table.outerHTML + '<p><br></p>');
  });
  tableDialog.querySelectorAll('[data-table-action]').forEach(button => button.addEventListener('click', () => {
    if (!selectedTable || !editor.contains(selectedTable)) { tableDialog.close(); return; }
    const action = button.dataset.tableAction;
    const error = tableDialog.querySelector('[data-table-error]');
    if (action !== 'remove-table' && selectedTable.querySelector('[colspan]:not([colspan="1"]), [rowspan]:not([rowspan="1"])')) {
      error.textContent = 'Tabel dengan sel gabungan dapat diedit melalui mode HTML. Isi sel tetap dapat diubah di editor.'; return;
    }
    const table = selectedTable.cloneNode(true);
    const rowIndex = selectedCell?.parentElement.rowIndex ?? table.rows.length - 1;
    const columnIndex = selectedCell?.cellIndex ?? 0;
    if (action === 'row') {
      if (table.rows.length >= 30) { error.textContent = 'Maksimal 30 baris per tabel.'; return; }
      const row = document.createElement('tr');
      for (let index = 0; index < table.rows[rowIndex].cells.length; index++) row.insertCell().textContent = 'Isi';
      const current = table.rows[rowIndex];
      if (current.parentElement.tagName === 'THEAD') {
        (table.tBodies[0] || table.createTBody()).prepend(row);
      } else current.after(row);
    }
    if (action === 'column') {
      if (Array.from(table.rows).some(row => row.cells.length >= 10)) { error.textContent = 'Maksimal 10 kolom per tabel.'; return; }
      for (const row of table.rows) {
        const current = row.cells[Math.min(columnIndex, row.cells.length - 1)];
        const cell = document.createElement(current.tagName.toLowerCase()); cell.textContent = current.tagName === 'TH' ? 'Kolom baru' : 'Isi'; current.after(cell);
      }
    }
    if (action === 'remove-row') table.deleteRow(rowIndex);
    if (action === 'remove-column') {
      for (const row of table.rows) { if (row.cells[columnIndex]) row.deleteCell(columnIndex); }
    }
    tableDialog.close();
    replaceElement(selectedTable, action === 'remove-table' || !table.rows.length || !table.rows[0].cells.length ? '<p><br></p>' : table.outerHTML);
  }));

  form.querySelectorAll('[data-editor-mode]').forEach(button => button.addEventListener('click', () => {
    const nextHtmlMode = button.dataset.editorMode === 'html';
    if (htmlMode === nextHtmlMode) return;
    if (nextHtmlMode) source.value = sanitizeArticle(editor.innerHTML);
    else { editor.innerHTML = sanitizeArticle(source.value); range = null; }
    htmlMode = nextHtmlMode;
    source.hidden = !htmlMode; editor.hidden = htmlMode; toolbar.hidden = htmlMode;
    form.querySelectorAll('[data-editor-mode]').forEach(control => control.setAttribute('aria-pressed', String(control === button)));
    sync(); (htmlMode ? source : editor).focus();
  }));
  source.addEventListener('input', () => sync(true));
  toolbar.querySelector('[data-insert-block]').addEventListener('change', event => {
    const type = event.target.value;
    if (!type) return;
    const labels = { info: 'Informasi', tip: 'Tips belajar', warning: 'Perhatian' };
    const selection = range && !range.collapsed ? range.toString() : '';
    const text = document.createElement('span'); text.textContent = selection || (type === 'code' ? 'Tulis kode di sini' : 'Tulis penjelasan di sini.');
    const html = type === 'code' ? `<pre><code>${text.innerHTML}</code></pre>` : type === 'faq' ? `<details><summary>Tulis pertanyaan di sini</summary><p>${text.innerHTML}</p></details>` : `<div data-callout="${type}"><p><strong>${labels[type]}</strong></p><p>${text.innerHTML}</p></div>`;
    insertBlock(html + '<p><br></p>'); event.target.value = '';
  });
  const focusButton = toolbar.querySelector('[data-editor-fullscreen]');
  const exitFocus = () => { form.classList.remove('article-workspace--focus'); focusButton.setAttribute('aria-pressed', 'false'); };
  focusButton.addEventListener('click', () => {
    const active = form.classList.toggle('article-workspace--focus');
    focusButton.setAttribute('aria-pressed', String(active));
  });
  form.addEventListener('keydown', event => {
    if (!htmlMode && (editor.contains(event.target) || toolbar.contains(event.target)) && (event.ctrlKey || event.metaKey) && ['z', 'y'].includes(event.key.toLowerCase())) {
      event.preventDefault(); execute(event.key.toLowerCase() === 'y' || event.shiftKey ? 'redo' : 'undo');
    }
    if (event.key === 'Escape') exitFocus();
    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's') {
      event.preventDefault(); form.requestSubmit(form.querySelector('button[type="submit"][value="draft"]'));
    }
  });
  form.querySelectorAll('[data-preview-size]').forEach(button => button.addEventListener('click', () => {
    form.querySelector('.article-preview-scroll').classList.toggle('article-preview-scroll--mobile', button.dataset.previewSize === 'mobile');
    form.querySelectorAll('[data-preview-size]').forEach(control => control.setAttribute('aria-pressed', String(control === button)));
  }));

  const draftKey = form.dataset.draftKey;
  const draftFields = ['title', 'slug', 'section_id', 'excerpt', 'cover_source', 'cover_source_url', 'publish_at'];
  const recovery = form.querySelector('[data-draft-recovery]');
  let pendingDraft = null;
  const saveLocalDraft = () => {
    if (!dirty || !recovery.hidden) return;
    const fields = Object.fromEntries(draftFields.map(name => [name, form.elements.namedItem(name).value]));
    fields.body = sanitizeArticle(htmlMode ? source.value : editor.innerHTML);
    fields.remove_cover = removeCover?.checked || false;
    try {
      localStorage.setItem(draftKey, JSON.stringify({ fields, version: form.dataset.version, savedAt: Date.now() }));
      form.querySelector('[data-save-note]').textContent = 'Draft lokal tersimpan di browser ini · Belum disimpan ke server · File cover baru perlu dipilih ulang saat dipulihkan';
    } catch { form.querySelector('[data-save-note]').textContent = 'Penyimpanan lokal tidak tersedia. Simpan draft ke server agar perubahan tidak hilang.'; }
  };
  try {
    if (form.dataset.saved === 'true') {
      localStorage.removeItem(draftKey);
      const submittedKey = sessionStorage.getItem('article-draft-submitting');
      if (submittedKey === draftKey.replace(/:[^:]+$/, ':new')) localStorage.removeItem(submittedKey);
      sessionStorage.removeItem('article-draft-submitting');
    } else {
      pendingDraft = JSON.parse(localStorage.getItem(draftKey) || 'null');
      if (pendingDraft?.fields && typeof pendingDraft.fields.body === 'string' && Number.isFinite(pendingDraft.savedAt)) {
        recovery.hidden = false;
        form.querySelector('[data-draft-description]').textContent = `Ditemukan draft lokal dari ${new Date(pendingDraft.savedAt).toLocaleString('id-ID')}. ${pendingDraft.version !== form.dataset.version ? 'Artikel di server telah berubah; pemulihan akan mengganti isi form saat ini.' : 'Pulihkan untuk melanjutkan perubahan yang belum tersimpan.'} File cover baru perlu dipilih ulang.`;
      }
    }
  } catch { /* Invalid or unavailable storage must not prevent editing. */ }
  form.querySelector('[data-draft-restore]').addEventListener('click', () => {
    if (!pendingDraft) return;
    draftFields.forEach(name => { if (typeof pendingDraft.fields[name] === 'string') form.elements.namedItem(name).value = pendingDraft.fields[name]; });
    editor.innerHTML = sanitizeArticle(pendingDraft.fields.body);
    source.value = sanitizeArticle(pendingDraft.fields.body);
    if (removeCover) removeCover.checked = pendingDraft.fields.remove_cover === true;
    recovery.hidden = true; range = null; sync(true);
    notify('Draft lokal dipulihkan. Periksa preview lalu simpan draft atau terbitkan.');
  });
  form.querySelector('[data-draft-discard]').addEventListener('click', () => {
    try { localStorage.removeItem(draftKey); } catch { /* Storage is optional. */ }
    pendingDraft = null; recovery.hidden = true;
    if (dirty) saveLocalDraft();
  });
  window.addEventListener('pagehide', () => { if (!submitting) saveLocalDraft(); });
  form.addEventListener('submit', (event) => {
    if (uploadingImage) { event.preventDefault(); notify('Tunggu sampai unggahan gambar selesai.'); return; }
    const wordCount = sync();
    const status = event.submitter?.value || form.querySelector('input[name="status"]').value;
    if (!title.value.trim() || !section.value) {
      event.preventDefault(); notify('Lengkapi judul dan bidang artikel.'); (!title.value.trim() ? title : section).focus(); return;
    }
    if (coverSourceUrl.value.trim() && !safeWebUrl(coverSourceUrl.value.trim())) {
      event.preventDefault(); notify('Tautan sumber cover harus berupa alamat http:// atau https:// yang valid.'); coverSourceUrl.focus(); return;
    }
    if (publishAt.value && !publishAt.validity.valid) {
      event.preventDefault(); notify('Isi jadwal terbit dengan tanggal dan waktu yang valid.'); publishAt.focus(); return;
    }
    if (status === 'published' && wordCount === 0) {
      event.preventDefault(); notify('Isi artikel tidak boleh kosong saat diterbitkan.'); (htmlMode ? source : editor).focus(); return;
    }
    source.value = sanitizeArticle(htmlMode ? source.value : editor.innerHTML);
    saveLocalDraft();
    try { sessionStorage.setItem('article-draft-submitting', draftKey); } catch { /* Storage is optional. */ }
    submitting = true;
  });
  window.addEventListener('beforeunload', (event) => { if (dirty && !submitting) { event.preventDefault(); event.returnValue = ''; } });
  sync();
});

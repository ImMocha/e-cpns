import { sanitizeArticle } from './article-editor.js';

const mimeTypes = new Map([['image/jpeg', 'jpg'], ['image/png', 'png'], ['image/webp', 'webp']]);
const maxBytes = 2 * 1024 * 1024;
const placeholder = (id) => '/question-explanation-images/' + id + '.png';
const imageId = (src) => src.match(/^\/question-explanation-images\/([a-f0-9]{32})\.png$/)?.[1];
const isSupportedImage = (file) => file && mimeTypes.has(file.type) && file.size > 0 && file.size <= maxBytes;
const supportsTransparency = (file) => isSupportedImage(file) && ['image/png', 'image/webp'].includes(file.type);

function embeddedFile(src) {
  const match = src.match(/^data:(image\/(?:jpeg|png|webp));base64,/i);
  if (!match || src.length - match[0].length > Math.ceil(maxBytes / 3) * 4) return null;
  const type = match[1].toLowerCase();
  try {
    const bytes = Uint8Array.from(atob(src.slice(match[0].length)), char => char.charCodeAt(0));
    return new File([bytes], 'pembahasan.' + mimeTypes.get(type), { type });
  } catch {
    return null;
  }
}

function preferredPasteImage(clipboardFile, embeddedImage) {
  if (supportsTransparency(clipboardFile)) return clipboardFile;
  if (supportsTransparency(embeddedImage)) return embeddedImage;
  return isSupportedImage(clipboardFile) ? clipboardFile : embeddedImage || clipboardFile;
}

export function setupExplanationImages(root, content, execute, remember) {
  const picker = root.querySelector('[data-rich-image-picker]');
  const feedback = root.querySelector('[data-rich-image-error]');
  const maxImages = Number(root.dataset.maxInlineImages);
  const images = new Map();
  const idsByUrl = new Map();
  const notify = (message = '') => {
    feedback.textContent = message;
    feedback.hidden = !message;
  };
  const idForElement = (img) => idsByUrl.get(img.src);
  const activeIds = () => new Set([...content.querySelectorAll('img')].map(idForElement).filter(Boolean));
  const reset = (urls) => {
    images.forEach(({ url, file }) => { if (file) URL.revokeObjectURL(url); });
    images.clear();
    idsByUrl.clear();
    for (const [id, url] of Object.entries(urls || {})) {
      images.set(id, { url });
      idsByUrl.set(url, id);
    }
    picker.value = '';
    notify();
  };
  const serialize = (html) => sanitizeArticle(html, (src, safeSrc) => {
    const id = idsByUrl.get(src);
    return id ? placeholder(id) : imageId(src) ? null : safeSrc;
  });
  const render = (html) => sanitizeArticle(html, (src, safeSrc) => {
    const id = imageId(src);
    return id ? images.get(id)?.url : safeSrc;
  });
  const pendingFiles = () => [...activeIds()].filter(id => images.get(id).file);

  const insert = (files, html = '', plain = '') => {
    const active = activeIds();
    const problems = new Set();
    const register = (file) => {
      if (!isSupportedImage(file)) {
        problems.add('Gunakan gambar JPG, PNG, atau WebP maksimal 2 MB.');
        return null;
      }
      if (active.size >= maxImages) {
        problems.add('Maksimal ' + maxImages + ' gambar sisipan per pembahasan.');
        return null;
      }
      const id = Array.from(crypto.getRandomValues(new Uint8Array(16)), value => value.toString(16).padStart(2, '0')).join('');
      const url = URL.createObjectURL(file);
      images.set(id, { url, file });
      idsByUrl.set(url, id);
      active.add(id);
      return url;
    };
    const fragment = document.createElement('template');
    fragment.innerHTML = html ? sanitizeArticle(html, (src, safeSrc) => {
      if (idsByUrl.has(src)) {
        files.shift();
        return src;
      }
      if (imageId(src)) return null;
      if (src.startsWith('data:')) {
        return register(preferredPasteImage(files.shift(), embeddedFile(src)));
      }
      if (files.length) return register(files.shift());
      if (!safeSrc) problems.add('Gambar tidak tersedia di clipboard. Gunakan tombol + Gambar.');
      return safeSrc;
    }) : '';
    if (!html && plain) fragment.content.append(plain);
    for (const file of files) {
      const url = register(file);
      if (!url) continue;
      const img = document.createElement('img');
      img.src = url;
      img.alt = 'Gambar pembahasan';
      fragment.content.append(img);
    }
    if (!html && fragment.content.querySelector('img')) fragment.content.append(document.createElement('br'));
    if (fragment.content.hasChildNodes()) {
      if (!html && !fragment.content.querySelector('img')) execute('insertText', plain);
      else execute('insertHTML', fragment.innerHTML);
    }
    notify([...problems].join(' '));
  };

  root.querySelector('[data-rich-choose-images]').addEventListener('click', () => {
    remember();
    picker.click();
  });
  picker.addEventListener('change', () => {
    if (picker.files.length) insert([...picker.files]);
    picker.value = '';
  });
  content.addEventListener('click', (event) => {
    if (event.target.closest('a')) event.preventDefault();
    if (!event.target.matches('img')) return;
    const range = document.createRange();
    range.selectNode(event.target);
    const selection = window.getSelection();
    selection.removeAllRanges();
    selection.addRange(range);
    remember();
  });
  reset(JSON.parse(root.dataset.inlineImageUrls));

  return {
    alignSelected(range, alignment) {
      if (!range || range.startContainer !== range.endContainer || range.endOffset !== range.startOffset + 1) return false;
      const selected = range.startContainer.childNodes[range.startOffset];
      if (!selected?.matches?.('img')) return false;
      const replacement = selected.cloneNode();
      replacement.dataset.align = alignment;
      execute('insertHTML', replacement.outerHTML);
      return true;
    },
    pendingFiles,
    render,
    reset,
    serialize,
    paste(clipboard) {
      const files = [...clipboard.files].filter(file => file.type.startsWith('image/'));
      insert(files, clipboard.getData('text/html'), clipboard.getData('text/plain'));
    },
    appendUploads(data) {
      for (const id of pendingFiles()) data.set('explanation_inline_images[' + id + ']', images.get(id).file);
    },
  };
}

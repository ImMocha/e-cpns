export function articleText(html) {
  const container = document.createElement('template');
  container.innerHTML = html.replace(/<\/(?:p|div|h[1-6]|li|td|th|tr|blockquote|figure|figcaption|pre|details|summary|caption)>|<br\s*\/?\s*>/gi, ' ');
  return container.content.textContent.replace(/[\s\u00a0\u200b]+/gu, ' ').trim();
}

export function buildArticleToc(body, navigation, prefix = 'article') {
  navigation.replaceChildren();
  const headings = Array.from(body.querySelectorAll('h2, h3, h4')).filter(heading => heading.textContent.trim());
  navigation.hidden = headings.length < 2;
  if (navigation.hidden) return;
  const title = document.createElement('strong');
  title.textContent = 'Daftar isi';
  const list = document.createElement('ol');
  headings.forEach((heading, index) => {
    heading.id = `${prefix}-heading-${index + 1}`;
    const item = document.createElement('li');
    item.dataset.level = heading.tagName.toLowerCase();
    const link = document.createElement('a');
    link.href = `#${heading.id}`;
    link.textContent = heading.textContent.trim();
    link.addEventListener('click', event => {
      event.preventDefault();
      heading.closest('details')?.setAttribute('open', '');
      heading.scrollIntoView({ behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth', block: 'start' });
      heading.setAttribute('tabindex', '-1'); heading.focus({ preventScroll: true });
    });
    item.append(link); list.append(item);
  });
  navigation.append(title, list);
}

export function readingProgress(top, height, viewportHeight) {
  return Math.round(Math.max(0, Math.min(1, -top / Math.max(1, height - viewportHeight))) * 100);
}

document.querySelectorAll('[data-article-reader]').forEach(page => {
  const article = page.querySelector('.article-reader');
  const body = article.querySelector('[data-article-body]');
  buildArticleToc(body, page.querySelector('[data-article-toc]'));
  page.querySelector('[data-reader-controls]').hidden = false;
  const size = page.querySelector('[data-reader-size]');
  const applySize = value => {
    if (!['16', '18', '20'].includes(value)) return;
    size.value = value; article.style.setProperty('--article-font-size', `${value}px`);
  };
  try { applySize(localStorage.getItem('article-reading-size')); } catch { /* Storage is optional. */ }
  size.addEventListener('change', () => {
    applySize(size.value);
    try { localStorage.setItem('article-reading-size', size.value); } catch { /* Reading still works without storage. */ }
  });
  page.querySelector('[data-article-print]').addEventListener('click', () => {
    const closed = Array.from(body.querySelectorAll('details:not([open])'));
    closed.forEach(details => { details.open = true; });
    window.addEventListener('afterprint', () => closed.forEach(details => { details.open = false; }), { once: true });
    window.print();
  });
  const progress = page.querySelector('[data-reading-progress]');
  progress.hidden = false;
  let queued = false;
  const updateProgress = () => {
    const bounds = article.getBoundingClientRect();
    progress.value = bounds.height <= window.innerHeight && bounds.top <= window.innerHeight ? 100 : readingProgress(bounds.top, bounds.height, window.innerHeight);
    queued = false;
  };
  const scheduleProgress = () => {
    if (queued) return;
    queued = true; requestAnimationFrame(updateProgress);
  };
  window.addEventListener('scroll', scheduleProgress, { passive: true });
  window.addEventListener('resize', scheduleProgress);
  new ResizeObserver(scheduleProgress).observe(article);
  updateProgress();
  body.querySelectorAll('pre').forEach(pre => {
    const wrapper = document.createElement('div');
    wrapper.className = 'article-code-block';
    const button = document.createElement('button');
    button.type = 'button'; button.className = 'article-code-copy'; button.textContent = 'Salin kode';
    const status = document.createElement('span');
    status.className = 'sr-only'; status.setAttribute('role', 'status');
    button.addEventListener('click', async () => {
      try {
        await navigator.clipboard.writeText(pre.textContent);
        status.textContent = 'Kode tersalin.'; button.textContent = 'Tersalin';
      } catch { status.textContent = 'Pilih teks kode dan salin secara manual.'; button.textContent = 'Salin manual'; }
      setTimeout(() => { button.textContent = 'Salin kode'; }, 2500);
    });
    pre.before(wrapper); wrapper.append(button, status, pre);
  });
});

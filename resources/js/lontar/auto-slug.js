const settings = {
  exams: { limit: 80, fallback: 'sesi' }, articles: { limit: 180, fallback: 'artikel' },
  materials: { limit: 200, fallback: 'materi' }, sections: { limit: 140, fallback: 'bidang' },
};

export function slugFromTitle(value, resource) {
  if (!value.trim()) return '';
  const { limit, fallback } = settings[resource];
  const slug = value.normalize('NFKD').replace(/[\u0300-\u036f]/g, '').toLowerCase()
    .replace(/&/g, '-and-').replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
  return (slug || fallback).slice(0, limit).replace(/-+$/g, '');
}

export function bindAutoSlug(input, title, { fetcher = fetch, schedule = setTimeout, cancel = clearTimeout, status = null } = {}) {
  if (input.readOnly) return;
  let followsTitle = !input.value;
  let updating = false;
  let sequence = 0;
  let timer;
  let controller;
  const describe = text => { if (status) status.textContent = text; };
  const setValue = value => {
    if (input.value === value) return;
    updating = true;
    input.value = value;
    input.dispatchEvent(new Event('input', { bubbles: true }));
    updating = false;
  };
  const preview = () => {
    const current = ++sequence;
    cancel(timer);
    controller?.abort();
    if (!input.value) { describe('Alamat akan terisi saat judul diisi.'); return; }
    const value = input.value;
    const source = followsTitle ? title.value : value;
    describe('Memeriksa ketersediaan alamat…');
    timer = schedule(async () => {
      controller = new AbortController();
      const url = new URL(input.dataset.slugUrl);
      url.searchParams.set('resource', input.dataset.autoSlug);
      url.searchParams.set('value', source);
      if (input.dataset.slugId) url.searchParams.set('ignore_id', input.dataset.slugId);
      try {
        const response = await fetcher(url.toString(), { credentials: 'same-origin', signal: controller.signal, headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error('preview-unavailable');
        const payload = await response.json();
        if (current !== sequence || input.value !== value) return;
        setValue(payload.slug);
        describe('Alamat tersedia. Keunikan diperiksa lagi saat disimpan.');
      } catch (error) {
        if (current === sequence && error.name !== 'AbortError') describe('Alamat akan dipastikan unik saat disimpan.');
      }
    }, 300);
  };
  title.addEventListener('input', () => {
    if (!followsTitle) return;
    setValue(slugFromTitle(title.value, input.dataset.autoSlug));
    preview();
  });
  input.addEventListener('input', () => {
    if (updating) return;
    followsTitle = !input.value.trim();
    if (followsTitle) setValue(slugFromTitle(title.value, input.dataset.autoSlug));
    preview();
  });
  if (followsTitle && title.value.trim()) {
    setValue(slugFromTitle(title.value, input.dataset.autoSlug));
    preview();
  }
}

if (typeof document !== 'undefined') {
  document.querySelectorAll('[data-auto-slug]').forEach(input => {
    const form = input.closest('form');
    const title = form?.querySelector('[name="title"], [name="name"]');
    if (!title || input.readOnly) return;
    const status = document.createElement('p');
    status.className = 'field__hint';
    status.setAttribute('role', 'status');
    input.closest('.field').append(status);
    bindAutoSlug(input, title, { status });
  });
}

export function matchesPackageContent(item, filters = {}) {
  const words = (filters.search || '').trim().toLocaleLowerCase('id').split(/\s+/).filter(Boolean);
  const text = (item.search || '').toLocaleLowerCase('id');
  return (!filters.topic || String(item.topic) === String(filters.topic))
    && words.every(word => text.includes(word));
}

export function selectPackageContent(items, selectedIds, action, filters = {}) {
  const selected = new Set([...selectedIds].map(String));
  for (const item of items) {
    if (item.disabled) continue;
    const id = String(item.id);
    if (action === 'clear') {
      selected.delete(id);
      continue;
    }
    if (!item.available) continue;
    if (action === 'all'
      || (action === 'premium' && item.access === 'premium')
      || (action === 'quiz' && item.kind === 'quiz')
      || (action === 'tryout' && item.kind === 'tryout')
      || (action === 'filtered' && matchesPackageContent(item, filters))) {
      selected.add(id);
    }
  }
  return selected;
}

export function initPackagePickers(scope) {
  scope.querySelectorAll('[data-package-picker]').forEach(picker => {
    const rows = [...picker.querySelectorAll('[data-package-item]')];
    const search = picker.querySelector('[data-package-search]');
    const topic = picker.querySelector('[data-package-topic]');
    const status = picker.querySelector('[data-package-count]');
    const empty = picker.querySelector('[data-package-empty]');
    const filters = () => ({ search: search.value, topic: topic?.value || '' });
    const items = () => rows.map(row => {
      const input = row.querySelector('input[type="checkbox"]');
      return {
        id: input.value, kind: row.dataset.kind, access: row.dataset.access,
        topic: row.dataset.topic || '', search: row.dataset.search,
        available: row.dataset.available === '1', disabled: input.matches(':disabled'),
      };
    });
    const update = () => {
      const content = items();
      let shown = 0;
      let selected = 0;
      let unavailable = 0;
      rows.forEach((row, index) => {
        row.hidden = !matchesPackageContent(content[index], filters());
        if (!row.hidden) shown++;
        if (row.querySelector('input').checked) {
          selected++;
          if (!content[index].available) unavailable++;
        }
      });
      status.textContent = `${selected} dipilih · ${shown} ditampilkan` + (unavailable ? ` · ${unavailable} belum siap` : '');
      empty.hidden = shown !== 0;
      picker.querySelector('[data-package-action="filtered"]').disabled = !content.some(item => item.available && !item.disabled && matchesPackageContent(item, filters()));
    };
    picker.addEventListener('click', event => {
      const button = event.target.closest('[data-package-action]');
      if (!button || !picker.contains(button) || button.matches(':disabled')) return;
      const selected = rows.filter(row => row.querySelector('input').checked).map(row => row.querySelector('input').value);
      const next = selectPackageContent(items(), selected, button.dataset.packageAction, filters());
      rows.forEach(row => {
        const input = row.querySelector('input');
        if (!input.matches(':disabled')) input.checked = next.has(input.value);
      });
      picker.dispatchEvent(new Event('change', { bubbles: true }));
    });
    picker.addEventListener('change', update);
    search.addEventListener('input', update);
    picker.querySelector('[data-package-tools]').hidden = false;
    update();
  });
}

if (typeof document !== 'undefined') initPackagePickers(document);

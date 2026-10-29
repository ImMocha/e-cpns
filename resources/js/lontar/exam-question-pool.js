export function synchronizeQuestionSelection(rows, id, checked) {
  for (const row of rows) {
    if (row.dataset.questionId === id) row.querySelector('input[type="checkbox"]').checked = checked;
  }
}

export function questionSourceState(sources, filters) {
  const term = filters.search.trim().toLocaleLowerCase('id');
  const selected = new Map();
  const groups = sources.map(source => {
    const matchesSource = term && source.title.toLocaleLowerCase('id').includes(term);
    const questions = source.questions.map(question => {
      if (question.checked) selected.set(question.id, question);
      return { ...question, visible: (!term || matchesSource || question.text.toLocaleLowerCase('id').includes(term))
        && (!filters.section || question.section === filters.section)
        && (!filters.selectedOnly || question.checked) };
    });
    return { questions, visible: questions.some(question => question.visible),
      count: questions.filter(question => question.visible).length,
      selected: questions.filter(question => question.checked).length };
  });
  return { groups, selected: selected.size, ready: [...selected.values()].filter(question => question.ready).length };
}

export function createQuestionPool(form, pool) {
  const search = form.querySelector('[data-exam-question-search]');
  const section = form.querySelector('[data-exam-question-section]');
  const selectedOnly = form.querySelector('[data-exam-selected-only]');
  const groups = () => [...pool.querySelectorAll('[data-exam-source]')];
  const rows = () => [...pool.querySelectorAll('[data-exam-pool-item]')];
  const previousOpen = new Map();
  let previousFilters = '';

  pool.addEventListener('change', event => {
    if (!event.target.matches('input[type="checkbox"]')) return;
    const row = event.target.closest('[data-exam-pool-item]');
    if (row) synchronizeQuestionSelection(rows(), row.dataset.questionId, event.target.checked);
  });
  form.querySelector('[data-exam-sources-expand]').addEventListener('click', () => {
    groups().filter(group => !group.hidden).forEach(group => { group.open = true; });
  });
  form.querySelector('[data-exam-sources-collapse]').addEventListener('click', () => {
    groups().forEach(group => { group.open = false; });
  });

  const update = () => {
    const sources = groups();
    const filters = { search: search.value, section: section.value, selectedOnly: selectedOnly.checked };
    const filtering = Boolean(filters.search.trim() || filters.section || filters.selectedOnly);
    const filterKey = filtering ? JSON.stringify(filters) : '';
    if (filterKey && !previousFilters) sources.forEach(source => { previousOpen.set(source, source.open); });
    const sourceRows = sources.map(source => [...source.querySelectorAll('[data-exam-pool-item]')]);
    const state = questionSourceState(sources.map((source, index) => ({
      title: source.dataset.sourceTitle,
      questions: sourceRows[index].map(row => ({ id: row.dataset.questionId, text: row.textContent,
        section: row.dataset.sectionId, checked: row.querySelector('input[type="checkbox"]').checked,
        ready: Boolean(row.querySelector('.exam-pool-status.is-ready')) })),
    })), filters);

    sources.forEach((source, index) => {
      const group = state.groups[index];
      sourceRows[index].forEach((row, rowIndex) => { row.hidden = !group.questions[rowIndex].visible; });
      source.hidden = !group.visible;
      source.querySelector('[data-source-count]').textContent = filtering
        ? `${group.count} dari ${group.questions.length} soal` : `${group.questions.length} soal`;
      const counter = source.querySelector('[data-source-selected]');
      counter.textContent = `${group.selected} dipilih`;
      counter.hidden = group.selected === 0;
      if (filterKey && filterKey !== previousFilters && group.visible) source.open = true;
      if (!filterKey && previousFilters) source.open = previousOpen.get(source) ?? false;
    });
    if (!filterKey) previousOpen.clear();
    previousFilters = filterKey;
    form.querySelector('[data-exam-selected]').textContent = `${state.selected} dipilih · ${state.ready} terbit`;
    form.querySelector('[data-exam-pool-empty]').hidden = state.groups.some(group => group.visible);
  };

  const replace = (id, html) => {
    const previous = rows().filter(row => row.dataset.questionId === String(id));
    const selected = previous.length ? previous.some(row => row.querySelector('input[type="checkbox"]').checked) : true;
    const template = document.createElement('template');
    template.innerHTML = html.trim();
    template.content.querySelector('input[type="checkbox"]').checked = selected;
    if (previous.length) {
      previous.forEach(row => {
        const replacement = template.content.firstElementChild.cloneNode(true);
        row.replaceWith(replacement);
        window.LIcons?.hydrate(replacement);
      });
    } else {
      const group = pool.querySelector('[data-exam-source="unassigned"]');
      const row = template.content.firstElementChild;
      group.querySelector('[data-exam-source-items]').prepend(row);
      group.open = true;
      previousOpen.set(group, true);
      search.value = '';
      section.value = '';
      window.LIcons?.hydrate(row);
    }
    return previous.length > 0;
  };

  const remove = id => { rows().filter(row => row.dataset.questionId === id).forEach(row => row.remove()); };
  return { update, replace, remove };
}

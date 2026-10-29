import test from 'node:test';
import assert from 'node:assert/strict';
import { createQuestionPool, questionSourceState, synchronizeQuestionSelection } from '../resources/js/lontar/exam-question-pool.js';

const filters = { search: '', section: '', selectedOnly: false };
const shared = { id: '1', text: 'Nasionalisme TWK', section: 'twk', checked: true, ready: true };
const sources = [
  { title: 'Tryout A', questions: [shared, { id: '2', text: 'Hitungan TIU', section: 'tiu', checked: false, ready: true }] },
  { title: 'Latihan Topik B', questions: [shared, { id: '3', text: 'Soal Draft TWK', section: 'twk', checked: true, ready: false }] },
  { title: 'Belum masuk sesi', questions: [] },
];

test('shared questions count once across sources and unavailable selections do not count as published', () => {
  const state = questionSourceState(sources, filters);
  assert.equal(state.selected, 2);
  assert.equal(state.ready, 1);
  assert.deepEqual(state.groups.map(group => group.count), [2, 2, 0]);
  assert.deepEqual(state.groups.map(group => group.visible), [true, true, false]);
});

test('searching a source title shows that session questions and still respects the section filter', () => {
  const state = questionSourceState(sources, { ...filters, search: '  TRYOUT a ', section: 'tiu' });
  assert.deepEqual(state.groups.map(group => group.count), [1, 0, 0]);
  assert.equal(state.groups[0].questions[1].visible, true);
  assert.equal(state.selected, 2);
});

test('question search finds shared copies in each matching source without showing unrelated questions', () => {
  const state = questionSourceState(sources, { ...filters, search: 'nasionalisme' });
  assert.deepEqual(state.groups.map(group => group.count), [1, 1, 0]);
  assert.equal(state.selected, 2);
});

test('selected-only and section filters combine with title search', () => {
  const state = questionSourceState(sources, { search: 'Tryout A', section: 'tiu', selectedOnly: true });
  assert.equal(state.groups.some(group => group.visible), false);
  assert.equal(state.selected, 2);
});

function row(question) {
  const checkbox = { checked: question.checked };
  return { dataset: { questionId: question.id, sectionId: question.section }, textContent: question.text, hidden: false,
    querySelector: selector => selector === 'input[type="checkbox"]' ? checkbox : (question.ready ? {} : null),
    cloneNode: () => row({ ...question, checked: checkbox.checked }) };
}

function attachRow(items, item) {
  item.remove = () => { items.splice(items.indexOf(item), 1); };
  item.replaceWith = replacement => { items.splice(items.indexOf(item), 1, replacement); attachRow(items, replacement); };
  return item;
}

test('selecting or clearing a shared question synchronizes every copy and leaves other selections intact', () => {
  const rows = sources.flatMap(source => source.questions.map(row));
  synchronizeQuestionSelection(rows, '1', false);
  assert.deepEqual(rows.map(item => item.querySelector('input[type="checkbox"]').checked), [false, false, false, true]);
  synchronizeQuestionSelection(rows, '1', true);
  assert.deepEqual(rows.map(item => item.querySelector('input[type="checkbox"]').checked), [true, false, true, true]);
});

function setup() {
  const groups = sources.map(source => {
    const items = source.questions.map(row);
    const count = {};
    const selected = {};
    items.forEach(item => attachRow(items, item));
    return { dataset: { sourceTitle: source.title }, open: false, hidden: false,
      items, count, selected, querySelectorAll: () => items,
      querySelector: selector => selector === '[data-source-count]' ? count : selector === '[data-source-selected]' ? selected
        : { prepend: item => { items.unshift(attachRow(items, item)); } } };
  });
  const listeners = {};
  const expand = { addEventListener: (event, handler) => { listeners.expand = handler; } };
  const collapse = { addEventListener: (event, handler) => { listeners.collapse = handler; } };
  const search = { value: '' };
  const section = { value: '' };
  const selectedOnly = { checked: false };
  const selected = {};
  const empty = {};
  const elements = { '[data-exam-question-search]': search, '[data-exam-question-section]': section,
    '[data-exam-selected-only]': selectedOnly, '[data-exam-selected]': selected, '[data-exam-pool-empty]': empty,
    '[data-exam-sources-expand]': expand, '[data-exam-sources-collapse]': collapse };
  const pool = { querySelectorAll: selector => selector === '[data-exam-source]' ? groups : groups.flatMap(group => group.items),
    querySelector: () => groups[2],
    addEventListener: (event, handler) => { listeners.change = handler; } };
  const manager = createQuestionPool({ querySelector: selector => elements[selector] }, pool);
  return { manager, groups, search, section, selectedOnly, selected, empty, listeners };
}

test('search opens matching dropdowns and clearing it restores the previous expanded state', () => {
  const view = setup();
  view.groups[1].open = true;
  view.manager.update();
  assert.equal(view.groups[0].open, false);
  view.search.value = 'Tryout A';
  view.manager.update();
  assert.equal(view.groups[0].open, true);
  assert.equal(view.groups[1].hidden, true);
  assert.equal(view.groups[0].count.textContent, '2 dari 2 soal');
  view.search.value = '';
  view.manager.update();
  assert.equal(view.groups[0].open, false);
  assert.equal(view.groups[1].open, true);
  assert.equal(view.groups[1].hidden, false);
  assert.equal(view.selected.textContent, '2 dipilih · 1 terbit');
});

test('opening and closing all respects hidden sources and no-results state is announced', () => {
  const view = setup();
  view.manager.update();
  view.listeners.expand();
  assert.deepEqual(view.groups.map(group => group.open), [true, true, false]);
  view.listeners.collapse();
  assert.deepEqual(view.groups.map(group => group.open), [false, false, false]);
  view.search.value = 'tidak ditemukan';
  view.manager.update();
  assert.equal(view.empty.hidden, false);
  assert.equal(view.groups.every(group => group.hidden), true);
});

test('selection change inside one dropdown updates its sibling before refreshing the counters', () => {
  const view = setup();
  const target = { checked: false, matches: () => true, closest: () => view.groups[0].items[0] };
  view.listeners.change({ target });
  view.manager.update();
  assert.equal(view.groups[1].items[0].querySelector('input[type="checkbox"]').checked, false);
  assert.equal(view.selected.textContent, '1 dipilih · 0 terbit');
  assert.equal(view.groups[0].selected.hidden, true);
});

function withQuestionTemplate(run) {
  const originalDocument = globalThis.document;
  const originalWindow = globalThis.window;
  globalThis.window = {};
  globalThis.document = { createElement: () => {
    const template = { content: { firstElementChild: null, querySelector: selector => template.content.firstElementChild.querySelector(selector) } };
    Object.defineProperty(template, 'innerHTML', { set: html => { template.content.firstElementChild = row(JSON.parse(html)); } });
    return template;
  } };
  try { run(); } finally { globalThis.document = originalDocument; globalThis.window = originalWindow; }
}

test('editing and archiving shared questions update all dropdown copies and preserve selection', () => {
  withQuestionTemplate(() => {
    const view = setup();
    assert.equal(view.manager.replace('1', JSON.stringify({ ...shared, text: 'Soal revisi', checked: false, ready: false })), true);
    view.manager.update();
    assert.equal(view.groups[0].items[0].textContent, 'Soal revisi');
    assert.equal(view.groups[1].items[0].textContent, 'Soal revisi');
    assert.equal(view.selected.textContent, '2 dipilih · 0 terbit');
    view.manager.remove('1');
    view.manager.update();
    assert.equal(view.groups[0].items.length, 1);
    assert.equal(view.groups[1].items.length, 1);
    assert.equal(view.selected.textContent, '1 dipilih · 0 terbit');
  });
});

test('new questions enter the unassigned source, stay selected, and remain open after clearing a search', () => {
  withQuestionTemplate(() => {
    const view = setup();
    view.search.value = 'Tryout A';
    view.manager.update();
    assert.equal(view.manager.replace('4', JSON.stringify({ id: '4', text: 'Soal baru', section: 'tiu', checked: false, ready: true })), false);
    view.manager.update();
    assert.equal(view.groups[2].hidden, false);
    assert.equal(view.groups[2].open, true);
    assert.equal(view.groups[2].items[0].querySelector('input[type="checkbox"]').checked, true);
    assert.equal(view.selected.textContent, '3 dipilih · 2 terbit');
    assert.equal(view.search.value, '');
  });
});

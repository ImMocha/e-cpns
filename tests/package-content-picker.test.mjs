import test from 'node:test';
import assert from 'node:assert/strict';
import { matchesPackageContent, selectPackageContent, initPackageTiers } from '../resources/js/lontar/package-content-picker.js';

const items = [
  { id: 1, topic: 10, search: 'Pancasila TWK Topik Nasionalisme', access: 'free', kind: 'pdf', available: true },
  { id: 2, topic: 10, search: 'Nilai Pancasila TWK', access: 'premium', kind: 'video', available: true },
  { id: 3, topic: 20, search: 'Kemampuan Numerik TIU', access: 'premium', kind: 'pdf', available: true },
  { id: 4, topic: 10, search: 'Materi Pancasila Draft', access: 'premium', kind: 'pdf', available: false },
  { id: 5, topic: '', search: 'Latihan TIU', access: 'free', kind: 'quiz', available: true },
  { id: 6, topic: '', search: 'Simulasi SKD', access: 'premium', kind: 'tryout', available: true },
];

test('all adds available content without discarding earlier selections', () => {
  const existing = new Set(['4']);
  assert.deepEqual([...selectPackageContent(items, existing, 'all')].sort(), ['1', '2', '3', '4', '5', '6']);
  assert.deepEqual([...existing], ['4']);
  assert.deepEqual([...selectPackageContent(items, [], 'all')], ['1', '2', '3', '5', '6']);
});

test('filter matches words without case sensitivity and limits selection to the chosen topic', () => {
  assert.equal(matchesPackageContent(items[0], { search: '  PANCASILA   twk ', topic: '10' }), true);
  assert.equal(matchesPackageContent(items[0], { search: 'Pancasila', topic: '20' }), false);
  assert.deepEqual([...selectPackageContent(items, ['3'], 'filtered', { search: 'Pancasila', topic: '10' })], ['3', '1', '2']);
});

test('selecting successive topics accumulates materials and does not select unavailable ones', () => {
  const first = selectPackageContent(items, [], 'filtered', { topic: '10' });
  assert.deepEqual([...first], ['1', '2']);
  assert.deepEqual([...selectPackageContent(items, first, 'filtered', { topic: '20' })], ['1', '2', '3']);
});

test('premium adds only available premium content', () => {
  assert.deepEqual([...selectPackageContent(items, [], 'premium')], ['2', '3', '6']);
});

test('session shortcuts distinguish quizzes from tryouts', () => {
  assert.deepEqual([...selectPackageContent(items, [], 'quiz')], ['5']);
  assert.deepEqual([...selectPackageContent(items, [], 'tryout')], ['6']);
});

test('practice shortcut selects standalone practice without mixing topic quizzes or tryouts', () => {
  const content = [...items,
    { id: 7, kind: 'practice', available: true },
    { id: 8, kind: 'practice', available: false },
  ];
  assert.deepEqual([...selectPackageContent(content, [], 'practice')], ['7']);
  assert.deepEqual([...selectPackageContent(content, [], 'quiz')], ['5']);
});

test('empty filter results preserve the current selection', () => {
  assert.deepEqual([...selectPackageContent(items, ['1'], 'filtered', { search: 'tidak ditemukan' })], ['1']);
});

test('clear removes selections including unavailable content within the current picker', () => {
  assert.deepEqual([...selectPackageContent(items.slice(0, 4), ['1', '4', '6'], 'clear')], ['6']);
});

test('archived or otherwise disabled controls cannot be changed by bulk actions', () => {
  const disabled = items.map(item => ({ ...item, disabled: true }));
  assert.deepEqual([...selectPackageContent(disabled, ['1'], 'all')], ['1']);
  assert.deepEqual([...selectPackageContent(disabled, ['1'], 'clear')], ['1']);
});


test('tier initialization keeps saved limits editable and changes only apply defaults on tier selection', () => {
  const listeners = {};
  const select = { value: 'regular', addEventListener: (event, callback) => { listeners[event] = callback; } };
  const elements = {
    tryout_quota: { value: '8', disabled: false },
    tryout_tokens: { value: '4', disabled: false },
    leaderboard_access: { value: 'national', disabled: false },
  };
  const formListeners = {};
  const form = { elements, querySelector: () => select, querySelectorAll: () => [],
    addEventListener: (event, callback) => { formListeners[event] = callback; } };
  initPackageTiers({ querySelectorAll: () => [form] });
  assert.deepEqual(Object.values(elements).map(field => field.value), ['8', '4', 'national']);
  assert.ok(Object.values(elements).every(field => !field.disabled));
  formListeners.change({ target: { closest: () => true } });
  assert.equal(elements.tryout_quota.value, '8');
  select.value = 'bundling';
  listeners.change();
  assert.deepEqual(Object.values(elements).map(field => field.value), [3, 3, 'local']);
  assert.ok(Object.values(elements).every(field => !field.disabled));
});

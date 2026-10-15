import test from 'node:test';
import assert from 'node:assert/strict';
import { findHelpGuide, searchHelpGuides } from '../resources/js/lontar/help-content.js';

test('admin module routes resolve to the correct contextual instructions', () => {
  for (const [route, expected] of [
    ['admin.dashboard', 'admin-start'],
    ['admin.materials.index', 'admin-materials'],
    ['admin.materials.edit', 'admin-materials'],
    ['admin.materials.quizzes.create', 'admin-quizzes'],
    ['admin.materials.quizzes.edit', 'admin-quizzes'],
    ['admin.topics.create', 'admin-topics'],
    ['admin.sections.edit', 'admin-sections'],
    ['admin.questions.create', 'admin-questions'],
    ['admin.exams.edit', 'admin-exams'],
    ['admin.articles.create', 'admin-articles'],
    ['admin.users.edit', 'admin-users'],
    ['admin.packages.edit', 'admin-packages'],
    ['admin.transactions.index', 'admin-transactions'],
    ['admin.leaderboard-simulation.index', 'admin-leaderboard'],
    ['admin.vouchers.index', 'admin-vouchers'],
  ]) assert.equal(findHelpGuide(route, 'admin').id, expected, route);
});

test('member catalogue, active exam, and results have distinct instructions', () => {
  for (const [route, expected] of [
    ['dashboard', 'member-dashboard'], ['materials.show', 'member-materials'],
    ['question-bank.show', 'member-question-bank'], ['quizzes.index', 'member-quizzes'],
    ['tryouts.index', 'member-tryouts'], ['quizzes.attempt', 'member-attempt'],
    ['tryouts.attempt', 'member-attempt'], ['quizzes.result', 'member-result'],
    ['tryouts.result', 'member-result'], ['history.index', 'member-history'],
    ['statistics.index', 'member-statistics'], ['leaderboard.index', 'member-leaderboard'],
    ['packages.index', 'member-packages'], ['checkout', 'member-checkout'],
    ['transactions.show', 'member-transactions'], ['certificates.show', 'member-certificates'],
    ['profile.edit', 'member-profile'], ['articles.show', 'member-articles'],
  ]) assert.equal(findHelpGuide(route, 'member').id, expected, route);
});

test('guide resolution stays within the current audience even for unknown or foreign routes', () => {
  assert.equal(findHelpGuide('admin.users.edit', 'member').id, 'member-dashboard');
  assert.equal(findHelpGuide('unknown.route', 'admin').id, 'admin-start');
  assert.equal(findHelpGuide('', 'member').id, 'member-dashboard');
});

test('search finds detailed content and field explanations with multiple case-insensitive words', () => {
  assert.ok(searchHelpGuides('  KUOTA   kosong  ', 'admin').some(item => item.id === 'admin-packages'));
  assert.ok(searchHelpGuides('passing grade sertifikat', 'admin').some(item => item.id === 'admin-exams'));
  assert.ok(searchHelpGuides('label A–E', 'admin').some(item => item.id === 'admin-questions'));
  assert.ok(searchHelpGuides('timer berjalan', 'member').some(item => item.id === 'member-attempt'));
  assert.ok(searchHelpGuides('voucher demonstrasi', 'admin').some(item => item.id === 'admin-vouchers'));
});

test('search does not leak admin instructions into the member library and handles no results', () => {
  assert.ok(searchHelpGuides('', 'member').every(item => item.audience === 'member'));
  assert.deepEqual(searchHelpGuides('admin-users', 'member'), []);
  assert.deepEqual(searchHelpGuides('kata-yang-tidak-ada-982732', 'admin'), []);
});

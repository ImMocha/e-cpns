import test from 'node:test';
import assert from 'node:assert/strict';

globalThis.document = { querySelectorAll: () => [] };
const { readingProgress } = await import('../resources/js/lontar/article-reader.js');

test('reading progress starts at zero before the article and reaches halfway after half of its scrollable height', () => {
  assert.equal(readingProgress(300, 2000, 800), 0);
  assert.equal(readingProgress(0, 2000, 800), 0);
  assert.equal(readingProgress(-600, 2000, 800), 50);
});

test('reading progress stops at 100 when scrolling beyond the article', () => {
  assert.equal(readingProgress(-1200, 2000, 800), 100);
  assert.equal(readingProgress(-2000, 2000, 800), 100);
});

test('short articles and equal viewport height do not produce invalid progress', () => {
  assert.equal(readingProgress(100, 300, 800), 0);
  assert.equal(readingProgress(-1, 800, 800), 100);
  assert.equal(readingProgress(0, 0, 0), 0);
});

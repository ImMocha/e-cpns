import test from 'node:test';
import assert from 'node:assert/strict';
import { mathSegments, parseQuestionMath } from '../resources/js/lontar/question-math.js';

test('detects inline and display formulas while preserving surrounding text', () => {
  const text = String.raw`Harga $5. Hitung \(\frac{1}{24}\) atau \[x^2\] lalu $$2+3$$.`;
  const { segments, remaining } = mathSegments(text);
  assert.deepEqual(segments.map(({ before, source, display }) => [before, source, display]), [
    ['Harga $5. Hitung ', String.raw`\frac{1}{24}`, false],
    [' atau ', 'x^2', true],
    [' lalu ', '2+3', true],
  ]);
  assert.equal(remaining, '.');
});

test('keeps escaped delimiters and unfinished formulas as text', () => {
  const text = String.raw`\\(literal\) lalu \[\frac{1}{2}`;
  assert.deepEqual(mathSegments(text), { segments: [], remaining: text });
});

test('converts stacked fractions and the comparison from the arithmetic example', () => {
  const formula = parseQuestionMath(String.raw`P=\frac{5}{6}+\frac{3}{8}-\frac{7}{12}\quad\text{dan}\quad Q=\frac{15}{24}`);
  assert.deepEqual(formula.filter(Array.isArray).filter(([tag]) => tag === 'mfrac'), [
    ['mfrac', ['mrow', ['mn', '5']], ['mrow', ['mn', '6']]],
    ['mfrac', ['mrow', ['mn', '3']], ['mrow', ['mn', '8']]],
    ['mfrac', ['mrow', ['mn', '7']], ['mrow', ['mn', '12']]],
    ['mfrac', ['mrow', ['mn', '15']], ['mrow', ['mn', '24']]],
  ]);
  assert.deepEqual(formula.find(node => Array.isArray(node) && node[1] === 'dan'), ['mtext', 'dan']);
});

test('converts grouped scripts, roots, and arithmetic symbols', () => {
  assert.deepEqual(parseQuestionMath(String.raw`x_{1}^{-2}\leq\sqrt[3]{8}`), [
    'mrow',
    ['msubsup', ['mi', 'x'], ['mrow', ['mn', '1']], ['mrow', ['mo', '−'], ['mn', '2']]],
    ['mo', '≤'],
    ['mroot', ['mrow', ['mn', '8']], ['mrow', ['mn', '3']]],
  ]);
});

test('falls back safely for incomplete, unsupported, or excessive expressions', () => {
  for (const formula of [String.raw`\frac{1}{`, String.raw`\href{javascript:alert(1)}{x}`, 'x^23', 'x_1_2', '{}', '{'.repeat(26) + '1' + '}'.repeat(26), 'x'.repeat(1501)]) {
    assert.equal(parseQuestionMath(formula), null);
  }
});

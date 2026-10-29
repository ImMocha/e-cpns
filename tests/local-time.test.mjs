import test from 'node:test';
import assert from 'node:assert/strict';
import { detectTimeZone, formatLocalTime, localizeTimes, resolveTimeZone } from '../resources/js/lontar/local-time.js';

test('unknown, missing, and foreign zones fall back to WIB', () => {
  for (const zone of [undefined, '', 'Invalid/Zone', 'Europe/London', 'UTC', '__proto__']) {
    assert.equal(formatLocalTime('2026-10-25T17:05:00Z', 'd M Y H:i', zone), '26 Okt 2026 00:05 WIB');
  }
});

test('Indonesian device zones show the same instant with the correct regional label', () => {
  const instant = '2026-10-26T00:15:00Z';
  for (const [zone, expected] of [
    ['Asia/Jakarta', '07:15 WIB'], ['Asia/Pontianak', '07:15 WIB'],
    ['Asia/Makassar', '08:15 WITA'], ['Asia/Ujung_Pandang', '08:15 WITA'],
    ['Asia/Jayapura', '09:15 WIT'],
  ]) {
    assert.equal(formatLocalTime(instant, 'H:i', zone), expected);
  }
  assert.deepEqual(resolveTimeZone('Asia/Ujung_Pandang'), ['Asia/Makassar', 'WITA']);
});

test('regional conversion also updates dates across midnight and year boundaries', () => {
  const instant = '2026-12-31T16:30:00Z';
  assert.equal(formatLocalTime(instant, 'd/m/Y H:i', 'Asia/Jakarta'), '31/12/2026 23:30 WIB');
  assert.equal(formatLocalTime(instant, 'd/m/Y H:i', 'Asia/Makassar'), '01/01/2027 00:30 WITA');
  assert.equal(formatLocalTime(instant, 'l, d F Y', 'Asia/Jayapura'), 'Jumat, 01 Januari 2027');
});

test('invalid timestamps preserve the existing fallback text', () => {
  for (const value of ['invalid', '', null, undefined]) {
    assert.equal(formatLocalTime(value, 'H:i', 'Asia/Jakarta'), null);
  }
  const element = {
    dataset: { timeFormat: 'H:i' }, textContent: 'Belum pernah',
    getAttribute: () => 'invalid',
  };
  localizeTimes({ querySelectorAll: () => [element] }, 'Asia/Makassar');
  assert.equal(element.textContent, 'Belum pernah');
});

test('unavailable browser detection keeps the WIB default', context => {
  context.mock.method(Intl, 'DateTimeFormat', () => { throw new Error('Unavailable'); });
  assert.equal(detectTimeZone(), undefined);
  assert.deepEqual(resolveTimeZone(detectTimeZone()), ['Asia/Jakarta', 'WIB']);
});

test('timestamps inside dynamically inserted content are localized', () => {
  const element = {
    dataset: { timeFormat: 'd/m/Y H:i' }, textContent: '26/10/2026 07:15 WIB',
    getAttribute: () => '2026-10-26T07:15:00+07:00',
    matches: () => true, querySelectorAll: () => [],
  };
  localizeTimes(element, 'Asia/Jayapura');
  assert.equal(element.textContent, '26/10/2026 09:15 WIT');
});

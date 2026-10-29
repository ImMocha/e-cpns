import test from 'node:test';
import assert from 'node:assert/strict';
import { bindReaderFullscreen } from '../resources/js/lontar/reader-fullscreen.js';

function reader() {
  const classes = () => ({ toggle() {} });
  const doc = new EventTarget();
  doc.fullscreenElement = null;
  const root = { ownerDocument: doc, classList: classes() };
  const button = new EventTarget();
  button.classList = classes();
  button.attributes = {};
  button.setAttribute = (name, value) => { button.attributes[name] = value; };
  button.querySelector = () => null;
  root.requestFullscreen = async () => {
    doc.fullscreenElement = root;
    doc.dispatchEvent(new Event('fullscreenchange'));
  };
  doc.exitFullscreen = async () => {
    doc.fullscreenElement = null;
    doc.dispatchEvent(new Event('fullscreenchange'));
  };
  return { root, button, doc };
}

test('the control requests native fullscreen on the reader and exits on its next click', async () => {
  const { root, button, doc } = reader();
  const toggle = bindReaderFullscreen(root, button);
  await toggle();
  assert.equal(doc.fullscreenElement, root);
  assert.equal(button.attributes['aria-pressed'], 'true');
  assert.equal(button.attributes['aria-label'], 'Keluar layar penuh');
  await toggle();
  assert.equal(doc.fullscreenElement, null);
  assert.equal(button.attributes['aria-pressed'], 'false');
});

test('leaving fullscreen through the browser resets the control and triggers layout recalculation', async () => {
  const { root, button, doc } = reader();
  const changes = [];
  const toggle = bindReaderFullscreen(root, button, { onChange: active => changes.push(active) });
  await toggle();
  await doc.exitFullscreen();
  assert.equal(button.attributes['aria-label'], 'Layar penuh');
  assert.equal(changes.at(-1), false);
});

test('denied or unsupported fullscreen reports failure and keeps the control inactive', async () => {
  for (const supported of [true, false]) {
    const { root, button, doc } = reader();
    root.requestFullscreen = supported ? async () => { throw new Error('Denied'); } : undefined;
    const errors = [];
    const toggle = bindReaderFullscreen(root, button, { onError: message => errors.push(message) });
    await toggle();
    assert.equal(errors.length, 1);
    assert.equal(doc.fullscreenElement, null);
    assert.equal(button.attributes['aria-pressed'], 'false');
  }
});

test('prefixed Safari fullscreen uses the same enter and exit controls', async () => {
  const { root, button, doc } = reader();
  root.webkitRequestFullscreen = root.requestFullscreen;
  doc.webkitExitFullscreen = doc.exitFullscreen;
  delete root.requestFullscreen;
  delete doc.exitFullscreen;
  const toggle = bindReaderFullscreen(root, button);
  await toggle();
  assert.equal(doc.fullscreenElement, root);
  await toggle();
  assert.equal(doc.fullscreenElement, null);
});

import test from 'node:test';
import assert from 'node:assert/strict';
import { applyListFormat, setListStart, selectedListItems, indentListItems, outdentListItems } from '../resources/js/lontar/article-lists.js';

class Element {
  constructor(tagName, text = '') {
    this.tagName = tagName.toUpperCase(); this.nodeName = this.tagName; this.nodeType = 1;
    this.text = text; this.children = []; this.attributes = {}; this.parentElement = null;
    this.ownerDocument = { createElement: tag => new Element(tag) };
  }
  get childNodes() { return this.children; }
  get lastElementChild() { return this.children.at(-1); }
  get previousElementSibling() { return this.parentElement?.children[this.parentElement.children.indexOf(this) - 1]; }
  get nextElementSibling() { return this.parentElement?.children[this.parentElement.children.indexOf(this) + 1]; }
  getAttribute(name) { return this.attributes[name] ?? null; }
  setAttribute(name, value) { this.attributes[name] = value; }
  removeAttribute(name) { delete this.attributes[name]; }
  append(...nodes) { nodes.forEach(node => { node.remove(); this.children.push(node); node.parentElement = this; }); }
  after(...nodes) {
    const parent = this.parentElement;
    nodes.forEach(node => node.remove());
    parent.children.splice(parent.children.indexOf(this) + 1, 0, ...nodes);
    nodes.forEach(node => { node.parentElement = parent; });
  }
  remove() {
    if (this.parentElement) this.parentElement.children.splice(this.parentElement.children.indexOf(this), 1);
    this.parentElement = null;
  }
  cloneNode() { const node = new Element(this.tagName); node.attributes = { ...this.attributes }; return node; }
  contains(node) { return this === node || this.children.some(child => child.contains(node)); }
  closest(tag) { return this.tagName.toLowerCase() === tag ? this : this.parentElement?.closest(tag); }
}

const list = (labels, type = '1', suffix = 'period', tag = 'ol') => {
  const root = new Element(tag);
  labels.forEach(label => root.append(new Element('li', label)));
  if (tag === 'ol') applyListFormat(root, type, suffix);
  return root;
};
const labels = node => node.children.map(child => child.text);

test('each nested group keeps its own numeric or alphabetic parentheses format', () => {
  const root = list(['UTAMA', 'sub 1', 'sub 2', 'UTAMA', 'sub a', 'sub b']);
  const [first, sub1, sub2, second, subA, subB] = root.children;
  assert.equal(indentListItems([sub1, sub2]), true);
  assert.equal(indentListItems([subA, subB]), true);
  assert.equal(applyListFormat(second.lastElementChild, 'a', 'paren'), true);
  assert.deepEqual(labels(root), ['UTAMA', 'UTAMA']);
  assert.deepEqual(labels(first.lastElementChild), ['sub 1', 'sub 2']);
  assert.deepEqual(first.lastElementChild.attributes, { type: '1', 'data-list-type': '1', 'data-list-suffix': 'paren' });
  assert.deepEqual(labels(second.lastElementChild), ['sub a', 'sub b']);
  assert.deepEqual(second.lastElementChild.attributes, { type: 'a', 'data-list-type': 'a', 'data-list-suffix': 'paren' });
  assert.deepEqual(root.attributes, { type: '1', 'data-list-type': '1', 'data-list-suffix': 'period' });
});

test('indenting into an existing sublist keeps its format and existing items', () => {
  const root = list(['Parent', 'Next']);
  const nested = list(['Existing'], 'i', 'paren');
  root.children[0].append(nested);
  assert.equal(indentListItems([root.children[1]]), true);
  assert.deepEqual(labels(nested), ['Existing', 'Next']);
  assert.equal(nested.getAttribute('type'), 'i');
  assert.equal(indentListItems([root.children[0]]), false);
});

test('outdenting a middle item preserves trailing items beneath the moved item', () => {
  const root = list(['Parent', 'Other']);
  const nested = list(['Before', 'Moved', 'After'], 'a', 'paren');
  root.children[0].append(nested);
  const moved = nested.children[1];
  assert.equal(outdentListItems([moved]), true);
  assert.deepEqual(labels(root), ['Parent', 'Moved', 'Other']);
  assert.deepEqual(labels(nested), ['Before']);
  assert.deepEqual(labels(moved.lastElementChild), ['After']);
  assert.equal(moved.lastElementChild.getAttribute('type'), 'a');
});

test('outdenting all children removes the empty sublist and keeps sibling order', () => {
  const root = list(['Parent', 'Other']);
  const nested = list(['One', 'Two']);
  root.children[0].append(nested);
  assert.equal(outdentListItems([...nested.children]), true);
  assert.deepEqual(labels(root), ['Parent', 'One', 'Two', 'Other']);
  assert.equal(root.children[0].children.length, 0);
  assert.equal(outdentListItems([root.children[0]]), false);
});

test('bullet lists use the same level controls without becoming numbered lists', () => {
  const root = list(['One', 'Two'], null, null, 'ul');
  const second = root.children[1];
  assert.equal(indentListItems([second]), true);
  assert.equal(root.children[0].lastElementChild.tagName, 'UL');
  assert.equal(outdentListItems([second]), true);
  assert.deepEqual(labels(root), ['One', 'Two']);
});

test('numbering supports five types and validates start values before changing existing settings', () => {
  const root = list(['Item']);
  for (const type of ['1', 'a', 'A', 'i', 'I']) {
    assert.equal(applyListFormat(root, type, 'paren'), true);
    assert.equal(root.getAttribute('type'), type);
  }
  assert.equal(setListStart(root, 27), true);
  for (const value of [0, -1, 1.5, 10000, '', 'abc']) assert.equal(setListStart(root, value), false);
  assert.equal(root.getAttribute('start'), '27');
  assert.equal(setListStart(root, 1), true);
  assert.equal(applyListFormat(root, 'unsafe', 'paren'), false);
  assert.equal(applyListFormat(root, '1', 'unsafe'), false);
  assert.equal(applyListFormat(new Element('ul'), 'a', 'period'), false);
});

test('selection resolves only contiguous items at the same level inside the editor', () => {
  const editor = new Element('div');
  const root = list(['One', 'Two', 'Three']); editor.append(root);
  const [one, two] = root.children;
  const range = { commonAncestorContainer: root, startContainer: one, startOffset: 0, endContainer: { nodeType: 3, parentElement: two }, endOffset: 3, collapsed: false };
  assert.deepEqual(selectedListItems(editor, range), [one, two]);
  const nested = list(['Nested']); one.append(nested);
  assert.deepEqual(selectedListItems(editor, { ...range, startContainer: nested.children[0] }), []);
  assert.deepEqual(selectedListItems(editor, { ...range, commonAncestorContainer: new Element('div') }), []);
  assert.deepEqual(selectedListItems(editor, { ...range, startContainer: root, startOffset: 1, endContainer: root, endOffset: 3 }), root.children.slice(1));
  assert.deepEqual(selectedListItems(editor, { ...range, endContainer: two, endOffset: 0 }), [one]);
});

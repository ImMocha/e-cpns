export const listTypes = ['1', 'a', 'A', 'i', 'I'];
export const listSuffixes = ['period', 'paren'];

export function applyListFormat(list, type, suffix) {
  if (list?.tagName !== 'OL' || !listTypes.includes(type) || !listSuffixes.includes(suffix)) return false;
  list.setAttribute('type', type);
  list.setAttribute('data-list-type', type);
  list.setAttribute('data-list-suffix', suffix);
  return true;
}

export function setListStart(list, value) {
  if (list?.tagName !== 'OL' || !/^[1-9]\d{0,3}$/.test(String(value))) return false;
  list.setAttribute('start', String(value));
  return true;
}

export function selectedListItems(editor, range) {
  if (!range || !editor.contains(range.commonAncestorContainer)) return [];
  const itemAt = (node, offset, end = false) => {
    if (['OL', 'UL'].includes(node.nodeName)) node = node.childNodes[Math.max(0, offset - (end ? 1 : 0))] || node;
    return (node.nodeType === 3 ? node.parentElement : node).closest?.('li');
  };
  const first = itemAt(range.startContainer, range.startOffset);
  let last = range.collapsed ? first : itemAt(range.endContainer, range.endOffset, true);
  if (!range.collapsed && range.endOffset === 0 && last && !['OL', 'UL'].includes(range.endContainer.nodeName)) {
    let boundary = range.endContainer;
    while (boundary !== last && boundary.parentElement?.childNodes[0] === boundary) boundary = boundary.parentElement;
    if (boundary === last) last = last.previousElementSibling;
  }
  if (!first || !last || first.parentElement !== last.parentElement || !editor.contains(first)) return [];
  const siblings = Array.from(first.parentElement.children);
  return siblings.slice(siblings.indexOf(first), siblings.indexOf(last) + 1).filter(item => item.tagName === 'LI');
}

export function indentListItems(items) {
  if (!items.length) return false;
  const list = items[0].parentElement;
  const previous = items[0].previousElementSibling;
  if (!previous || previous.tagName !== 'LI' || items.some(item => item.parentElement !== list)) return false;
  let nested = previous.lastElementChild;
  if (nested?.tagName !== list.tagName) {
    nested = list.ownerDocument.createElement(list.tagName.toLowerCase());
    if (list.tagName === 'OL') applyListFormat(nested, '1', 'paren');
    previous.append(nested);
  }
  nested.append(...items);
  return true;
}

export function outdentListItems(items) {
  if (!items.length) return false;
  const list = items[0].parentElement;
  const parent = list.parentElement;
  if (parent?.tagName !== 'LI' || !['OL', 'UL'].includes(parent.parentElement?.tagName) || items.some(item => item.parentElement !== list)) return false;
  const following = [];
  for (let next = items.at(-1).nextElementSibling; next; next = next.nextElementSibling) following.push(next);
  if (following.length) {
    const tail = list.cloneNode(false);
    tail.removeAttribute('start');
    tail.append(...following);
    items.at(-1).append(tail);
  }
  parent.after(...items);
  if (!list.children.length) list.remove();
  return true;
}

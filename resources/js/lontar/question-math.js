const namespace = 'http://www.w3.org/1998/Math/MathML';
const operators = new Map(Object.entries({
  times: '×', cdot: '⋅', div: '÷', pm: '±', mp: '∓',
  le: '≤', leq: '≤', ge: '≥', geq: '≥', ne: '≠', neq: '≠',
  approx: '≈', infty: '∞', ldots: '…', cdots: '⋯', colon: ':',
  lbrace: '{', rbrace: '}', vert: '|',
}));
const symbols = new Map(Object.entries({
  alpha: 'α', beta: 'β', gamma: 'γ', delta: 'δ', theta: 'θ',
  lambda: 'λ', mu: 'μ', pi: 'π', sigma: 'σ', phi: 'φ', omega: 'ω',
}));
const spacing = new Map([[',', '\u2009'], [';', '\u2002'], [' ', ' '], ['quad', '\u2003'], ['qquad', '\u2003\u2003']]);
const delimiters = new Map([['\\(', '\\)'], ['\\[', '\\]'], ['$$', '$$']]);

export function mathSegments(text) {
  const segments = [];
  let start = 0;
  let opening;
  for (const match of text.matchAll(/\\\\|\\\$|\\[()[\]]|\$\$/g)) {
    const marker = match[0];
    if (opening && marker === delimiters.get(opening[0])) {
      const end = match.index + marker.length;
      segments.push({
        before: text.slice(start, opening.index),
        source: text.slice(opening.index + opening[0].length, match.index),
        original: text.slice(opening.index, end),
        display: opening[0] !== '\\(',
      });
      start = end;
      opening = undefined;
    } else if (delimiters.has(marker)) {
      opening = match;
    }
  }
  return { segments, remaining: text.slice(start) };
}

export function parseQuestionMath(source) {
  if (!source.trim() || source.length > 1500) return null;
  const tokens = source.match(/\\[a-z]+|\\.|[0-9]+(?:[.,][0-9]+)?|\s|./giu);
  let index = 0;

  function peek() {
    while (tokens[index] && /^\s$/u.test(tokens[index])) index++;
    return tokens[index];
  }

  function expect(symbol) {
    if (peek() !== symbol) throw new SyntaxError('Rumus belum lengkap');
    index++;
  }

  function group(depth, open, close) {
    expect(open);
    const result = sequence(depth, close);
    expect(close);
    return result;
  }

  function atom(depth) {
    if (depth > 24) throw new SyntaxError('Rumus terlalu dalam');
    const token = peek();
    if (token === '{') return group(depth + 1, '{', '}');
    if (!token) throw new SyntaxError('Rumus belum lengkap');
    index++;

    if (/^\d/u.test(token)) return ['mn', token];
    if (/^\p{L}$/u.test(token)) return ['mi', token];
    if (/^[+=<>,.:;!?()[\]|/%−×÷±≤≥≠≈∞°-]$/u.test(token)) return ['mo', token === '-' ? '−' : token];
    if (!token.startsWith('\\')) throw new SyntaxError('Simbol tidak didukung');

    const command = token.slice(1);
    if (['frac', 'dfrac', 'tfrac'].includes(command)) {
      const fraction = ['mfrac', group(depth + 1, '{', '}'), group(depth + 1, '{', '}')];
      return command === 'frac' ? fraction : ['mstyle', String(command === 'dfrac'), fraction];
    }
    if (command === 'sqrt') {
      const degree = peek() === '[' ? group(depth + 1, '[', ']') : null;
      const radicand = group(depth + 1, '{', '}');
      return degree ? ['mroot', radicand, degree] : ['msqrt', radicand];
    }
    if (command === 'left' || command === 'right') {
      const fence = peek();
      if (!fence || !/^(?:[()[\]|.]|\\[{}|])$/u.test(fence)) throw new SyntaxError('Pembatas rumus tidak didukung');
      index++;
      return fence === '.' ? ['mtext', ''] : ['mo', fence.replace(/^\\/u, '')];
    }
    if (command === 'text') {
      expect('{');
      let text = '';
      while (tokens[index] && tokens[index] !== '}') {
        const character = tokens[index++];
        if (character === '{' || /^\\[a-z]+$/i.test(character)) throw new SyntaxError('Teks rumus tidak didukung');
        text += character.startsWith('\\') ? character.slice(1) : character;
      }
      expect('}');
      return ['mtext', text];
    }
    if (operators.has(command)) return ['mo', operators.get(command)];
    if (symbols.has(command)) return ['mi', symbols.get(command)];
    if (spacing.has(command)) return ['mtext', spacing.get(command)];
    if (/^[{}%_&]$/u.test(command)) return ['mo', command];
    throw new SyntaxError('Perintah LaTeX tidak didukung');
  }

  function sequence(depth, close) {
    const row = ['mrow'];
    while (peek() && peek() !== (close ?? '}')) {
      const base = atom(depth);
      let sub;
      let sup;
      while (peek() === '_' || peek() === '^') {
        const marker = tokens[index++];
        if ((marker === '_' && sub) || (marker === '^' && sup)) throw new SyntaxError('Indeks atau pangkat berulang');
        if (/^\d.+$/u.test(peek() ?? '')) throw new SyntaxError('Gunakan kurung kurawal untuk indeks atau pangkat lebih dari satu digit');
        const argument = atom(depth + 1);
        if (marker === '_') sub = argument;
        else sup = argument;
      }
      if (sub && sup) row.push(['msubsup', base, sub, sup]);
      else if (sub) row.push(['msub', base, sub]);
      else if (sup) row.push(['msup', base, sup]);
      else row.push(base);
    }
    if (row.length === 1) throw new SyntaxError('Rumus kosong');
    return row;
  }

  try {
    const result = sequence(0);
    return index === tokens.length ? result : null;
  } catch (error) {
    if (!(error instanceof SyntaxError)) throw error;
    return null;
  }
}

function createMathNode([tag, ...children], document) {
  const node = document.createElementNS(namespace, tag);
  if (tag === 'mstyle') node.setAttribute('displaystyle', children.shift());
  for (const child of children) node.append(typeof child === 'string' ? child : createMathNode(child, document));
  return node;
}

export function renderQuestionMath(root, { preview = false } = {}) {
  const document = root.ownerDocument;
  const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
    acceptNode: node => node.parentElement.closest('math, pre, code, .question-math-invalid')
      ? NodeFilter.FILTER_REJECT : NodeFilter.FILTER_ACCEPT,
  });
  const nodes = [];
  while (walker.nextNode()) nodes.push(walker.currentNode);
  for (const node of nodes) {
    const { segments, remaining } = mathSegments(node.data);
    if (!segments.length) continue;
    const content = document.createDocumentFragment();
    for (const { before, source, original, display } of segments) {
      content.append(before);
      const parsed = parseQuestionMath(source);
      if (!parsed && !preview) {
        content.append(original);
        continue;
      }
      const wrapper = document.createElement('span');
      if (parsed) {
        wrapper.className = display ? 'question-math question-math--block' : 'question-math';
        const math = createMathNode(['math', parsed], document);
        if (display) math.setAttribute('display', 'block');
        wrapper.append(math);
      } else {
        wrapper.className = 'question-math-invalid';
        wrapper.title = 'Periksa penulisan rumus atau gunakan perintah LaTeX yang didukung.';
        wrapper.textContent = original;
      }
      content.append(wrapper);
    }
    content.append(remaining);
    node.replaceWith(content);
  }
}

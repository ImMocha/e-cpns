import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const source = readFileSync(new URL('../resources/js/lontar/charts.js', import.meta.url), 'utf8');

function chart(values = [0, 0, 0, 0, 0, 17], useObserver = true) {
  const createNode = tagName => ({
    tagName, attributes: {}, children: [], dataset: {}, style: {},
    setAttribute(name, value) { this.attributes[name] = String(value); },
    appendChild(child) { this.children.push(child); return child; },
  });
  const host = {
    dataset: { chart: JSON.stringify({ type: 'bar', labels: ['May 26', 'Jun 26', 'Jul 26', 'Aug 26', 'Sep 26', 'Oct 26'], values, max: 20, height: 250 }) },
    clientWidth: 720, clientHeight: 500,
    replaceChildren(svg) { this.firstElementChild = svg; },
  };
  let notifyResize;
  const listeners = {};
  const context = {
    document: { readyState: 'complete', documentElement: {}, querySelectorAll: () => [host], createElementNS: (_, name) => createNode(name) },
    window: { matchMedia: () => ({ matches: true }), addEventListener: (name, handler) => { listeners[name] = handler; } },
    getComputedStyle: () => ({ getPropertyValue: () => '' }),
    ...(useObserver ? { ResizeObserver: class {
      constructor(callback) { notifyResize = callback; }
      observe() {}
    } } : {}),
  };
  vm.runInNewContext(source, context);

  return {
    host,
    resize(width, height) {
      host.clientWidth = width;
      host.clientHeight = height;
      if (useObserver) {
        notifyResize([{ contentRect: { width, height } }]);
      } else {
        listeners.resize();
      }
    },
  };
}

function descendants(node, tagName) {
  return node.children.flatMap(child => [
    ...(child.tagName === tagName ? [child] : []),
    ...descendants(child, tagName),
  ]);
}

test('zero months keep their labels without drawing false bars', () => {
  const { host } = chart();
  const svg = host.firstElementChild;

  assert.equal(descendants(svg, 'rect').length, 1);
  assert.equal(descendants(svg, 'rect')[0].attributes.height, '372.3');
  assert.equal(descendants(svg, 'text').filter(node => node.textContent === '0').length, 6);
  assert.equal(descendants(svg, 'text').filter(node => node.textContent === 'Oct 26').length, 1);

  const emptyChart = chart([0, 0, 0, 0, 0, 0]);
  assert.equal(descendants(emptyChart.host.firstElementChild, 'rect').length, 0);
});

test('charts use the available height and redraw when only the height changes', () => {
  const { host, resize } = chart();
  assert.equal(host.firstElementChild.attributes.viewBox, '0 0 720 500');

  resize(720, 300);

  assert.equal(host.firstElementChild.attributes.viewBox, '0 0 720 300');
  assert.equal(descendants(host.firstElementChild, 'rect')[0].attributes.height, '202.3');

  resize(320, 250);
  assert.equal(host.firstElementChild.attributes.viewBox, '0 0 320 250');
  assert.equal(descendants(host.firstElementChild, 'text').filter(node => /26$/.test(node.textContent)).length, 6);
});

test('window resize also updates chart height without ResizeObserver', () => {
  const { host, resize } = chart([0, 0, 0, 0, 0, 17], false);

  resize(720, 300);

  assert.equal(host.firstElementChild.attributes.viewBox, '0 0 720 300');
});

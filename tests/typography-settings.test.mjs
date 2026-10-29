import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';

const script = readFileSync(new URL('../resources/js/lontar/typography-settings.js', import.meta.url), 'utf8');

function setup() {
    const select = (value, families) => {
        const element = new EventTarget();
        element.value = value;
        Object.defineProperty(element, 'selectedOptions', {
            get: () => [{ dataset: { family: families[element.value], category: element.value === 'lora' ? 'Serif' : 'Sans serif' } }],
        });
        return element;
    };
    const heading = select('poppins', { poppins: 'Poppins', outfit: 'Outfit', lora: 'Lora' });
    const body = select('inter', { inter: 'Inter', 'plus-jakarta-sans': 'Plus Jakarta Sans' });
    const sample = new EventTarget();
    sample.value = 'Belajar CPNS 2026';
    const reset = new EventTarget();
    const status = {};
    const scopes = Object.fromEntries(['landing', 'member', 'admin'].map(scope => {
        const input = new EventTarget();
        input.checked = scope === 'landing';
        return [scope, input];
    }));
    const previews = Object.keys(scopes).map(scope => {
        const properties = {};
        const elements = { '[data-font-status]': {}, '[data-font-preview-text]': {} };
        return {
            dataset: { fontPreview: scope }, properties, elements,
            style: { setProperty: (key, value) => { properties[key] = value; } },
            querySelector: selector => elements[selector],
        };
    });
    const links = [];
    const elements = {
        '[name="heading_font"]': heading, '[name="body_font"]': body,
        '[data-font-sample]': sample, '[data-font-loading]': status, '[data-font-reset]': reset,
        ...Object.fromEntries(Object.entries(scopes).map(([key, value]) => [`[data-font-scope="${key}"]`, value])),
    };
    const settings = {
        querySelector: selector => elements[selector],
        querySelectorAll: selector => ({
            '[data-font-preview]': previews,
            '[data-font-select], [data-font-scope]': [heading, body, ...Object.values(scopes)],
            '[data-font-preview-text]': previews.map(preview => preview.elements['[data-font-preview-text]']),
        })[selector],
    };
    const document = {
        querySelector: () => settings,
        createElement: () => ({ remove() { links.splice(links.indexOf(this), 1); } }),
        head: { appendChild: link => links.push(link) },
        fonts: { load: async () => [] },
    };
    runInNewContext(script, { document });
    return { heading, body, sample, reset, status, scopes, previews, links };
}

test('preview changes fonts only for enabled areas and loads the selected families', async () => {
    const { heading, scopes, previews, links, status } = setup();
    assert.equal(previews[0].properties['--font-display'], '"Poppins", ui-sans-serif, system-ui, sans-serif');
    assert.equal(previews[1].properties['--font-display'], '"Outfit", ui-sans-serif, system-ui, sans-serif');
    assert.equal(links[0].href, 'https://fonts.bunny.net/css?family=poppins:400,700|inter:400,700&display=swap');
    await links[0].onload();
    assert.match(status.textContent, /Preview siap/);
    scopes.member.checked = true;
    scopes.member.dispatchEvent(new Event('change'));
    assert.equal(previews[1].properties['--font-body'], '"Inter", ui-sans-serif, system-ui, sans-serif');
    assert.equal(links.length, 1);
    heading.value = 'lora';
    heading.dispatchEvent(new Event('change'));
    assert.equal(previews[0].properties['--font-display'], '"Lora", Georgia, serif');
});

test('custom sample text remains plain text and reset restores both default fonts', () => {
    const { sample, reset, heading, body, previews } = setup();
    sample.value = '<img src=x onerror=alert(1)>';
    sample.dispatchEvent(new Event('input'));
    assert.equal(previews[0].elements['[data-font-preview-text]'].textContent, '<img src=x onerror=alert(1)>');
    reset.dispatchEvent(new Event('click'));
    assert.equal(heading.value, 'outfit');
    assert.equal(body.value, 'plus-jakarta-sans');
    assert.equal(previews[0].properties['--font-display'], '"Outfit", ui-sans-serif, system-ui, sans-serif');
});

test('failed font requests show a useful message and can be retried', () => {
    const { links, status, heading } = setup();
    links[0].onerror();
    assert.match(status.textContent, /Periksa koneksi internet/);
    assert.equal(links.length, 0);
    heading.dispatchEvent(new Event('change'));
    assert.equal(links.length, 1);
});

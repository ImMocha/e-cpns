import test from 'node:test';
import assert from 'node:assert/strict';
import { initPackageCarousel } from '../resources/js/lontar/package-carousel.js';

function carousel(count = 3, reducedMotion = false) {
    const cards = Array.from({ length: count }, (_, index) => ({
        offsetLeft: 32 + index * 324,
        offsetHeight: 500 - index * 100,
        classList: { toggle(name, active) { this[name] = active; } },
    }));
    const track = new EventTarget();
    Object.assign(track, {
        children: cards, clientWidth: 308, scrollLeft: 0, style: {},
        classList: { add() {}, remove() {} }, closest: () => null, setPointerCapture() {},
    });
    track.scrollTo = options => {
        track.lastScroll = options;
        track.scrollLeft = options.left;
        track.dispatchEvent(new Event('scroll'));
    };
    const controls = { hidden: true };
    const previous = new EventTarget();
    const next = new EventTarget();
    const position = {};
    const elements = { track, controls, previous, next, position };
    const root = {
        ownerDocument: { defaultView: { matchMedia: () => ({ matches: reducedMotion }), setTimeout } },
        querySelector: selector => elements[selector.replace('[data-carousel-', '').replace(']', '')],
    };
    initPackageCarousel(root);
    return { ...elements, cards };
}

test('arrows show the next package and stop at either boundary', () => {
    const { track, next, previous, position, controls, cards } = carousel();
    assert.equal(controls.hidden, false);
    assert.equal(previous.disabled, true);
    next.dispatchEvent(new Event('click'));
    assert.equal(track.scrollLeft, 324);
    assert.equal(position.textContent, '2 dari 3 paket');
    assert.deepEqual(cards.map(card => card.inert), [true, false, true]);
    assert.deepEqual(cards.map(card => card.classList['is-active']), [false, true, false]);
    next.dispatchEvent(new Event('click'));
    assert.equal(next.disabled, true);
    next.dispatchEvent(new Event('click'));
    assert.equal(track.scrollLeft, 648);
    previous.dispatchEvent(new Event('click'));
    previous.dispatchEvent(new Event('click'));
    previous.dispatchEvent(new Event('click'));
    assert.equal(track.scrollLeft, 0);
});

test('native swipe scrolling updates the active package and keyboard navigation respects reduced motion', () => {
    const { track, cards, position } = carousel(3, true);
    track.scrollLeft = 640;
    track.dispatchEvent(new Event('scroll'));
    assert.equal(position.textContent, '3 dari 3 paket');
    assert.deepEqual(cards.map(card => card.inert), [true, true, false]);
    const event = new Event('keydown', { cancelable: true });
    event.key = 'Home';
    track.dispatchEvent(event);
    assert.equal(event.defaultPrevented, true);
    assert.deepEqual(track.lastScroll, { left: 0, behavior: 'instant' });
});

test('a single package stays visible without unnecessary carousel controls', () => {
    const { controls, next, previous, cards, position } = carousel(1);
    assert.equal(controls.hidden, true);
    assert.equal(previous.disabled, true);
    assert.equal(next.disabled, true);
    assert.equal(cards[0].inert, false);
    assert.equal(position.textContent, '1 dari 1 paket');
});

test('mouse dragging moves the track and snaps to the nearest package on release', () => {
    const { track, position } = carousel();
    const pointer = (type, clientX) => {
        const event = new Event(type, { cancelable: true });
        Object.assign(event, { pointerType: 'mouse', button: 0, pointerId: 1, clientX, clientY: 50 });
        track.dispatchEvent(event);
    };
    pointer('pointerdown', 300);
    pointer('pointermove', 70);
    pointer('pointerup', 70);
    assert.equal(track.scrollLeft, 324);
    assert.equal(position.textContent, '2 dari 3 paket');
});

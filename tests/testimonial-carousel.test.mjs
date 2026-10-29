import test from 'node:test';
import assert from 'node:assert/strict';
import { initTestimonialCarousel } from '../resources/js/lontar/testimonial-carousel.js';

function carousel(count = 6, reducedMotion = false) {
    const track = new EventTarget();
    Object.assign(track, {
        children: Array.from({ length: count }, (_, index) => ({ offsetLeft: 48 + index * 400 })),
        clientWidth: 1248, scrollWidth: count > 3 ? 2448 : 1248, scrollLeft: 0,
        classList: { add() {}, remove() {} },
        setPointerCapture() {}, hasPointerCapture: () => false,
    });
    track.scrollTo = options => {
        track.lastScroll = options;
        track.scrollLeft = options.left;
        track.dispatchEvent(new Event('scroll'));
    };
    const elements = { track, previous: new EventTarget(), next: new EventTarget(), controls: {}, position: {} };
    initTestimonialCarousel({
        ownerDocument: { defaultView: { matchMedia: () => ({ matches: reducedMotion }) } },
        querySelector: selector => elements[selector.replace('[data-testimonial-', '').replace(']', '')],
    });
    return elements;
}

test('arrows advance one card and stop when the last three cards are visible', () => {
    const { track, next, previous, controls } = carousel();
    assert.equal(controls.hidden, false);
    assert.equal(previous.disabled, true);
    for (let index = 0; index < 4; index++) next.dispatchEvent(new Event('click'));
    assert.equal(track.scrollLeft, 1200);
    assert.equal(next.disabled, true);
    previous.dispatchEvent(new Event('click'));
    assert.equal(track.scrollLeft, 800);
});

test('mouse drag snaps to the nearest card on release', () => {
    const { track } = carousel();
    for (const [type, clientX] of [['pointerdown', 500], ['pointermove', 150], ['pointerup', 150]]) {
        const event = new Event(type, { cancelable: true });
        Object.assign(event, { pointerType: 'mouse', button: 0, pointerId: 1, clientX, clientY: 50 });
        track.dispatchEvent(event);
    }
    assert.equal(track.scrollLeft, 400);
});

test('native swipe updates controls and keyboard respects reduced motion', () => {
    const { track, previous, position } = carousel(6, true);
    track.scrollLeft = 400;
    track.dispatchEvent(new Event('scroll'));
    assert.equal(previous.disabled, false);
    assert.equal(position.textContent, '2 / 6');
    const event = new Event('keydown', { cancelable: true });
    event.key = 'Home';
    track.dispatchEvent(event);
    assert.deepEqual(track.lastScroll, { left: 0, behavior: 'instant' });
});

test('controls are hidden when all cards fit', () => {
    const { controls } = carousel(3);
    assert.equal(controls.hidden, true);
});

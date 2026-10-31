import test from 'node:test';
import assert from 'node:assert/strict';
import { initTestimonialCarousel } from '../resources/js/lontar/testimonial-carousel.js';

function carousel(count = 6, reducedMotion = false, layout = {}) {
    const { width = 1248, padding = 48, cardWidth = 352, step = 400 } = layout;
    const track = new EventTarget();
    Object.assign(track, {
        children: Array.from({ length: count }, (_, index) => ({ offsetLeft: padding + index * step, offsetWidth: cardWidth })),
        clientWidth: width, scrollWidth: Math.max(width, padding * 2 + count * cardWidth + Math.max(0, count - 1) * (step - cardWidth)), scrollLeft: 0,
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
    const { track, next, previous, controls, position } = carousel();
    assert.equal(controls.hidden, false);
    assert.equal(previous.disabled, true);
    assert.equal(position.textContent, '1–3 / 6');
    for (let index = 0; index < 4; index++) next.dispatchEvent(new Event('click'));
    assert.equal(track.scrollLeft, 1200);
    assert.equal(next.disabled, true);
    assert.equal(position.textContent, '4–6 / 6');
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
    assert.equal(position.textContent, '2–4 / 6');
    const event = new Event('keydown', { cancelable: true });
    event.key = 'Home';
    track.dispatchEvent(event);
    assert.deepEqual(track.lastScroll, { left: 0, behavior: 'instant' });
});

test('controls are hidden when all cards fit', () => {
    const { controls } = carousel(3);
    assert.equal(controls.hidden, true);
});

test('mobile navigation reaches the sixth card without duplicate stops at the end', () => {
    const { track, next, previous, position } = carousel(6, true, { width: 400, padding: 16, cardWidth: 300, step: 324 });
    assert.equal(position.textContent, '1 / 6');
    for (let index = 0; index < 5; index++) next.dispatchEvent(new Event('click'));
    assert.equal(track.scrollLeft, 1552);
    assert.equal(position.textContent, '6 / 6');
    assert.equal(next.disabled, true);
    previous.dispatchEvent(new Event('click'));
    assert.equal(track.scrollLeft, 1296);
    assert.equal(position.textContent, '5 / 6');
    assert.equal(next.disabled, false);
});

test('tablet shows the last two testimonials before disabling the next arrow', () => {
    const { next, position } = carousel(6, false, { width: 848 });
    assert.equal(position.textContent, '1–2 / 6');
    for (let index = 0; index < 4; index++) next.dispatchEvent(new Event('click'));
    assert.equal(position.textContent, '5–6 / 6');
    assert.equal(next.disabled, true);
});

test('rapid arrow clicks continue toward the next card while smooth scrolling is in progress', () => {
    const { track, next, previous } = carousel();
    track.scrollTo = options => { track.lastScroll = options; };
    next.dispatchEvent(new Event('click'));
    next.dispatchEvent(new Event('click'));
    assert.deepEqual(track.lastScroll, { left: 800, behavior: 'smooth' });
    previous.dispatchEvent(new Event('click'));
    assert.equal(track.lastScroll.left, 400);
});

test('End and Home navigate between the reachable ends and update the visible range', () => {
    const { track, next, previous, position } = carousel();
    const end = new Event('keydown', { cancelable: true });
    end.key = 'End';
    track.dispatchEvent(end);
    assert.equal(position.textContent, '4–6 / 6');
    assert.equal(next.disabled, true);
    const home = new Event('keydown', { cancelable: true });
    home.key = 'Home';
    track.dispatchEvent(home);
    assert.equal(position.textContent, '1–3 / 6');
    assert.equal(previous.disabled, true);
});

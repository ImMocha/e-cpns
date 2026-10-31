export function initTestimonialCarousel(root) {
    const track = root.querySelector('[data-testimonial-track]');
    const cards = [...track.children];
    if (!cards.length) return;
    const previous = root.querySelector('[data-testimonial-previous]');
    const next = root.querySelector('[data-testimonial-next]');
    const position = root.querySelector('[data-testimonial-position]');
    const controls = root.querySelector('[data-testimonial-controls]');
    const view = root.ownerDocument.defaultView;
    let drag = null;
    let destination = null;
    const maximum = () => Math.max(0, track.scrollWidth - track.clientWidth);
    const stops = () => [...new Set(cards.map(card => Math.max(0, Math.min(maximum(), card.offsetLeft - cards[0].offsetLeft))))];
    const current = () => stops().reduce((nearest, offset, index, offsets) =>
        Math.abs(offset - track.scrollLeft) < Math.abs(offsets[nearest] - track.scrollLeft) ? index : nearest, 0);
    const render = () => {
        if (destination !== null && Math.abs(track.scrollLeft - stops()[destination]) <= 1) destination = null;
        previous.disabled = track.scrollLeft <= 1;
        next.disabled = track.scrollLeft >= maximum() - 1;
        controls.hidden = maximum() <= 1;
        const visible = cards.map((card, index) => ({ card, index })).filter(({ card }) =>
            card.offsetLeft >= track.scrollLeft - 1 && card.offsetLeft + card.offsetWidth <= track.scrollLeft + track.clientWidth + 1);
        const nearestCard = cards.reduce((nearest, card, index) =>
            Math.abs(card.offsetLeft + card.offsetWidth / 2 - track.scrollLeft - track.clientWidth / 2)
                < Math.abs(cards[nearest].offsetLeft + cards[nearest].offsetWidth / 2 - track.scrollLeft - track.clientWidth / 2) ? index : nearest, 0);
        const first = (visible[0]?.index ?? nearestCard) + 1;
        const last = (visible.at(-1)?.index ?? nearestCard) + 1;
        position.textContent = `${first === last ? first : `${first}–${last}`} / ${cards.length}`;
    };
    const goTo = index => {
        const offsets = stops();
        destination = Math.max(0, Math.min(offsets.length - 1, index));
        track.scrollTo({
            left: offsets[destination],
            behavior: view.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth',
        });
    };
    previous.addEventListener('click', () => goTo((destination ?? current()) - 1));
    next.addEventListener('click', () => goTo((destination ?? current()) + 1));
    track.addEventListener('scroll', render, { passive: true });
    track.addEventListener('wheel', () => { destination = null; }, { passive: true });
    track.addEventListener('keydown', event => {
        if (event.target !== track) return;
        const index = destination ?? current();
        const destinations = { ArrowLeft: index - 1, ArrowRight: index + 1, Home: 0, End: stops().length - 1 };
        if (!(event.key in destinations)) return;
        event.preventDefault();
        goTo(destinations[event.key]);
    });
    track.addEventListener('pointerdown', event => {
        destination = null;
        if (event.pointerType !== 'mouse' || event.button !== 0 || maximum() <= 1) return;
        drag = { x: event.clientX, y: event.clientY, start: track.scrollLeft, active: false, id: event.pointerId };
    });
    track.addEventListener('pointermove', event => {
        if (!drag) return;
        const distance = event.clientX - drag.x;
        if (!drag.active) {
            if (Math.abs(distance) < 6 || Math.abs(distance) < Math.abs(event.clientY - drag.y)) return;
            drag.active = true;
            track.setPointerCapture(drag.id);
            track.classList.add('is-dragging');
        }
        event.preventDefault();
        track.scrollLeft = drag.start - distance;
    });
    const release = () => {
        if (!drag) return;
        const { active, id } = drag;
        drag = null;
        if (track.hasPointerCapture(id)) track.releasePointerCapture(id);
        track.classList.remove('is-dragging');
        if (active) goTo(current());
    };
    track.addEventListener('pointerup', release);
    track.addEventListener('pointercancel', release);
    track.addEventListener('lostpointercapture', release);
    track.addEventListener('pointerleave', () => { if (drag && !drag.active) release(); });
    if (typeof ResizeObserver !== 'undefined') new ResizeObserver(() => { destination = null; render(); }).observe(track);
    render();
}

if (typeof document !== 'undefined') {
    document.querySelectorAll('[data-testimonial-carousel]').forEach(initTestimonialCarousel);
}

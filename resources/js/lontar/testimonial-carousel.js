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
    const step = () => cards.length > 1 ? cards[1].offsetLeft - cards[0].offsetLeft : track.clientWidth;
    const maximum = () => Math.max(0, track.scrollWidth - track.clientWidth);
    const current = () => Math.round(track.scrollLeft / step());
    const render = () => {
        previous.disabled = track.scrollLeft <= 1;
        next.disabled = track.scrollLeft >= maximum() - 1;
        controls.hidden = maximum() <= 1;
        position.textContent = `${Math.min(cards.length, current() + 1)} / ${cards.length}`;
    };
    const goTo = index => track.scrollTo({
        left: Math.max(0, Math.min(maximum(), index * step())),
        behavior: view.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth',
    });
    previous.addEventListener('click', () => goTo(current() - 1));
    next.addEventListener('click', () => goTo(current() + 1));
    track.addEventListener('scroll', render, { passive: true });
    track.addEventListener('keydown', event => {
        if (event.target !== track) return;
        const destinations = { ArrowLeft: current() - 1, ArrowRight: current() + 1, Home: 0, End: cards.length - 1 };
        if (!(event.key in destinations)) return;
        event.preventDefault();
        goTo(destinations[event.key]);
    });
    track.addEventListener('pointerdown', event => {
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
    if (typeof ResizeObserver !== 'undefined') new ResizeObserver(render).observe(track);
    render();
}

if (typeof document !== 'undefined') {
    document.querySelectorAll('[data-testimonial-carousel]').forEach(initTestimonialCarousel);
}

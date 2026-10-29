export function initPackageCarousel(root) {
    const track = root.querySelector('[data-carousel-track]');
    const cards = [...track.children];
    const controls = root.querySelector('[data-carousel-controls]');
    const previous = root.querySelector('[data-carousel-previous]');
    const next = root.querySelector('[data-carousel-next]');
    const position = root.querySelector('[data-carousel-position]');
    const view = root.ownerDocument.defaultView;
    let current = 0;
    let drag = null;
    let suppressClick = false;
    let lastWidth = track.clientWidth;

    if (!cards.length) return;

    const offset = index => cards[index].offsetLeft - cards[0].offsetLeft;
    const nearest = () => cards.reduce((best, card, index) =>
        Math.abs(offset(index) - track.scrollLeft) < Math.abs(offset(best) - track.scrollLeft) ? index : best, 0);
    const resize = () => {
        if (track.clientWidth !== lastWidth) {
            lastWidth = track.clientWidth;
            track.scrollTo({ left: offset(current), behavior: 'instant' });
        }
    };
    const render = () => {
        current = nearest();
        previous.disabled = current === 0;
        next.disabled = current === cards.length - 1;
        position.textContent = `${current + 1} dari ${cards.length} paket`;
        cards.forEach((card, index) => {
            card.inert = index !== current;
            card.classList.toggle('is-active', index === current);
        });
        resize();
    };
    const goTo = index => {
        const bounded = Math.max(0, Math.min(cards.length - 1, index));
        track.scrollTo({
            left: offset(bounded),
            behavior: view.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth',
        });
    };

    controls.hidden = cards.length < 2;
    previous.addEventListener('click', () => goTo(current - 1));
    next.addEventListener('click', () => goTo(current + 1));
    track.addEventListener('scroll', render, { passive: true });
    track.addEventListener('keydown', event => {
        if (event.target !== track) return;
        const destinations = { ArrowLeft: current - 1, ArrowRight: current + 1, Home: 0, End: cards.length - 1 };
        if (!(event.key in destinations)) return;
        event.preventDefault();
        goTo(destinations[event.key]);
    });

    track.addEventListener('pointerdown', event => {
        if (event.pointerType !== 'mouse' || event.button !== 0 || cards.length < 2) return;
        if (event.target.closest('a, button, input, textarea, select, summary, .package-content__scroll')) return;
        suppressClick = false;
        drag = { x: event.clientX, y: event.clientY, scroll: track.scrollLeft, active: false };
    });
    track.addEventListener('pointermove', event => {
        if (!drag) return;
        const distance = event.clientX - drag.x;
        if (!drag.active) {
            if (Math.abs(distance) < 6 || Math.abs(distance) < Math.abs(event.clientY - drag.y)) return;
            drag.active = true;
            track.setPointerCapture(event.pointerId);
            track.classList.add('is-dragging');
        }
        event.preventDefault();
        track.scrollLeft = drag.scroll - distance;
    });
    const release = () => {
        if (!drag) return;
        const moved = drag.active;
        drag = null;
        track.classList.remove('is-dragging');
        if (moved) {
            suppressClick = true;
            view.setTimeout(() => { suppressClick = false; }, 0);
            goTo(nearest());
        }
    };
    track.addEventListener('pointerup', release);
    track.addEventListener('pointercancel', release);
    track.addEventListener('lostpointercapture', release);
    track.addEventListener('click', event => {
        if (!suppressClick) return;
        suppressClick = false;
        event.preventDefault();
        event.stopPropagation();
    }, true);

    if (typeof ResizeObserver !== 'undefined') {
        const observer = new ResizeObserver(resize);
        observer.observe(track);
        cards.forEach(card => observer.observe(card));
    }
    render();
}

if (typeof document !== 'undefined') {
    document.querySelectorAll('[data-package-carousel]').forEach(initPackageCarousel);
}

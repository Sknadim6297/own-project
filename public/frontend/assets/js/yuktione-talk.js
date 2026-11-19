(() => {
    const section = document.querySelector('[data-talk-experts]');
    if (!section) return;

    const viewport = section.querySelector('[data-talk-track]');
    const track = viewport?.querySelector('.yk-talk__track');
    const controls = section.querySelector('[data-talk-controls]');
    const previousButton = section.querySelector('[data-talk-prev]');
    const nextButton = section.querySelector('[data-talk-next]');
    if (!viewport || !track || !controls || !previousButton || !nextButton) return;

    // Favourite hearts: visual toggle only (nothing is stored yet).
    // Synced across the looping clones by expert id.
    section.addEventListener('click', (event) => {
        const target = event.target;
        if (!(target instanceof Element)) return;
        const heart = target.closest('[data-talk-fav]');
        if (!heart || !section.contains(heart)) return;

        event.preventDefault();
        const pressed = heart.getAttribute('aria-pressed') !== 'true';
        const id = heart.getAttribute('data-expert-id');
        section.querySelectorAll('[data-talk-fav]').forEach((button) => {
            if (button.getAttribute('data-expert-id') !== id) return;
            button.setAttribute('aria-pressed', pressed ? 'true' : 'false');
            const name = button.getAttribute('aria-label') || '';
            button.setAttribute(
                'aria-label',
                pressed ? name.replace(/^Save /, 'Remove ').replace(/ to favourites$/, ' from favourites')
                    : name.replace(/^Remove /, 'Save ').replace(/ from favourites$/, ' to favourites')
            );
        });
        heart.classList.remove('is-pop');
        void heart.offsetWidth;
        heart.classList.add('is-pop');
    });

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const slides = () => [...track.querySelectorAll('.yk-talk__slide:not([data-talk-clone])')];
    const speed = 36; // px per second, left
    const resumeDelay = 1500; // ms to wait after a manual scroll before auto-advance continues

    let halfWidth = 0;
    let offset = 0; // our own float position (scrollLeft can round to whole pixels)
    let frame = null;
    let lastTs = 0;
    let paused = false;
    let jumping = false;
    let resumeAt = 0;
    let dragState = null;
    let suppressClick = false;

    const now = () => window.performance.now();

    const measure = () => {
        halfWidth = track.scrollWidth / (track.querySelector('[data-talk-clone]') ? 2 : 1);
    };

    const ensureClones = () => {
        track.querySelectorAll('[data-talk-clone]').forEach((node) => node.remove());
        slides().forEach((slide) => {
            const clone = slide.cloneNode(true);
            clone.setAttribute('data-talk-clone', 'true');
            clone.setAttribute('aria-hidden', 'true');
            clone.querySelectorAll('a, button').forEach((el) => {
                el.setAttribute('tabindex', '-1');
            });
            track.appendChild(clone);
        });
        measure();
    };

    // Keep the position inside the first copy so the loop stays seamless (clones are identical).
    const wrap = (value) => (halfWidth > 0 ? ((value % halfWidth) + halfWidth) % halfWidth : value);

    const normalize = () => {
        const wrapped = wrap(offset);
        if (wrapped !== offset) {
            offset = wrapped;
            viewport.scrollLeft = wrapped;
        }
    };

    const stepSize = () => {
        const slide = slides()[0];
        if (!slide) return 180;
        const styles = window.getComputedStyle(track);
        const gap = Number.parseFloat(styles.columnGap || styles.gap || '0') || 0;
        return slide.getBoundingClientRect().width + gap;
    };

    const tick = (ts) => {
        if (!lastTs) lastTs = ts;
        const delta = Math.min(32, ts - lastTs);
        lastTs = ts;

        const canMove = !paused
            && !jumping
            && !reducedMotion.matches
            && halfWidth > 0
            && slides().length > 1
            && ts >= resumeAt;

        if (canMove) {
            offset = wrap(offset + (speed * delta) / 1000);
            viewport.scrollLeft = offset;
        }

        frame = window.requestAnimationFrame(tick);
    };

    const start = () => {
        if (frame) window.cancelAnimationFrame(frame);
        lastTs = 0;
        frame = window.requestAnimationFrame(tick);
    };

    // Any scroll the browser did itself (trackpad, touch, scrollbar, keyboard, drag) becomes the new position.
    viewport.addEventListener('scroll', () => {
        const current = viewport.scrollLeft;
        if (Math.abs(current - offset) <= 2) return; // our own auto-advance write
        offset = current;
        resumeAt = now() + resumeDelay;
        if (!jumping) normalize();
    }, { passive: true });

    const jump = (direction) => {
        if (halfWidth <= 0) return;
        resumeAt = now() + resumeDelay;
        jumping = true;

        // At the very start, step into the identical second copy first so "previous" loops smoothly.
        if (direction < 0 && viewport.scrollLeft < stepSize()) {
            offset = viewport.scrollLeft + halfWidth;
            viewport.scrollLeft = offset;
        }

        viewport.scrollBy({
            left: direction * stepSize(),
            behavior: reducedMotion.matches ? 'auto' : 'smooth',
        });

        window.setTimeout(() => {
            jumping = false;
            offset = viewport.scrollLeft;
            normalize();
        }, 700);
    };

    const updateControls = () => {
        const enabled = halfWidth > viewport.clientWidth + 2 || slides().length > 1;
        controls.hidden = !enabled;
        previousButton.disabled = !enabled;
        nextButton.disabled = !enabled;
    };

    previousButton.addEventListener('click', () => jump(-1));
    nextButton.addEventListener('click', () => jump(1));

    viewport.addEventListener('mouseenter', () => {
        paused = true;
    });
    viewport.addEventListener('mouseleave', () => {
        paused = false;
    });
    viewport.addEventListener('focusin', () => {
        paused = true;
    });
    viewport.addEventListener('focusout', () => {
        paused = false;
    });
    viewport.addEventListener('pointerdown', () => {
        paused = true;
    });
    viewport.addEventListener('pointerup', () => {
        paused = false;
        resumeAt = now() + resumeDelay;
    });

    // Mouse drag-to-scroll (touch and trackpad already scroll natively).
    viewport.addEventListener('pointerdown', (event) => {
        if (event.pointerType !== 'mouse' || event.button !== 0) return;
        dragState = { x: event.clientX, left: viewport.scrollLeft, moved: false };
    });
    window.addEventListener('pointermove', (event) => {
        if (!dragState) return;
        const dx = event.clientX - dragState.x;
        if (!dragState.moved && Math.abs(dx) > 4) {
            dragState.moved = true;
            viewport.classList.add('is-dragging');
        }
        if (dragState.moved) {
            viewport.scrollLeft = dragState.left - dx;
        }
    });
    const endDrag = () => {
        if (!dragState) return;
        if (dragState.moved) suppressClick = true;
        dragState = null;
        viewport.classList.remove('is-dragging');
    };
    window.addEventListener('pointerup', endDrag);
    window.addEventListener('pointercancel', endDrag);
    viewport.addEventListener('dragstart', (event) => event.preventDefault());
    viewport.addEventListener('click', (event) => {
        if (!suppressClick) return;
        suppressClick = false;
        event.preventDefault();
        event.stopPropagation();
    }, true);

    document.addEventListener('visibilitychange', () => {
        paused = document.hidden;
    });

    window.addEventListener('resize', () => {
        measure();
        normalize();
        updateControls();
    }, { passive: true });

    if (typeof reducedMotion.addEventListener === 'function') {
        reducedMotion.addEventListener('change', () => {
            if (reducedMotion.matches) {
                offset = 0;
                viewport.scrollLeft = 0;
            }
        });
    }

    ensureClones();
    viewport.scrollLeft = 0;
    offset = 0;
    updateControls();
    start();
})();

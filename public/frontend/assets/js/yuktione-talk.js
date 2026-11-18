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
    let offset = 0;
    let halfWidth = 0;
    let frame = null;
    let lastTs = 0;
    let paused = false;
    const speed = 36; // px per second, right -> left

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

    const applyTransform = () => {
        if (halfWidth > 0) {
            offset = ((offset % halfWidth) + halfWidth) % halfWidth;
        }
        track.style.transform = `translate3d(${-offset}px, 0, 0)`;
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

        if (!paused && !reducedMotion.matches && halfWidth > 0 && slides().length > 1) {
            offset += (speed * delta) / 1000;
            applyTransform();
        }

        frame = window.requestAnimationFrame(tick);
    };

    const start = () => {
        if (frame) window.cancelAnimationFrame(frame);
        lastTs = 0;
        frame = window.requestAnimationFrame(tick);
    };

    const stop = () => {
        if (frame) {
            window.cancelAnimationFrame(frame);
            frame = null;
        }
    };

    const jump = (direction) => {
        offset += direction * stepSize();
        applyTransform();
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
    });

    document.addEventListener('visibilitychange', () => {
        paused = document.hidden;
    });

    window.addEventListener('resize', () => {
        measure();
        applyTransform();
        updateControls();
    }, { passive: true });

    if (typeof reducedMotion.addEventListener === 'function') {
        reducedMotion.addEventListener('change', () => {
            if (reducedMotion.matches) {
                offset = 0;
                applyTransform();
            }
        });
    }

    ensureClones();
    applyTransform();
    updateControls();
    start();
})();

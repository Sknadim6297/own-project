const searchInput = document.getElementById('serviceSearch');
const searchResults = document.getElementById('searchResults');
const searchButton = document.getElementById('searchButton');

if (searchInput && searchResults && searchButton) {
    let searchTimeout;
    let requestNumber = 0;
    let currentResults = [];

    const hideResults = () => {
        searchResults.classList.add('d-none');
        searchResults.style.display = 'none';
        searchResults.replaceChildren();
        currentResults = [];
    };

    const showMessage = (message) => {
        const list = document.createElement('div');
        const item = document.createElement('div');

        list.className = 'list-group';
        item.className = 'list-group-item text-muted';
        item.textContent = message;
        list.append(item);
        searchResults.replaceChildren(list);
        searchResults.classList.remove('d-none');
        searchResults.style.display = 'block';
    };

    const resultUrl = (item) => {
        if (typeof item?.url === 'string' && item.url) {
            return item.url;
        }

        if (item?.type === 'service' && typeof item?.slug === 'string') {
            const serviceBase = (searchInput.dataset.serviceBase || '/service').replace(/\/$/, '');
            return `${serviceBase}/${encodeURIComponent(item.slug)}`;
        }

        return null;
    };

    const renderResults = (items) => {
        const list = document.createElement('div');
        list.className = 'list-group';

        items.forEach((item) => {
            const url = resultUrl(item);
            if (!url) {
                return;
            }

            const link = document.createElement('a');
            link.className = 'list-group-item list-group-item-action yk-hero__result';
            link.href = url;
            link.setAttribute('role', 'option');

            const copy = document.createElement('span');
            copy.className = 'yk-hero__result-copy';

            const title = document.createElement('span');
            title.className = 'yk-hero__result-title';
            title.textContent = item.name;
            copy.append(title);

            if (item.meta) {
                const meta = document.createElement('span');
                meta.className = 'yk-hero__result-meta';
                meta.textContent = item.meta;
                copy.append(meta);
            }

            const badge = document.createElement('span');
            badge.className = `yk-hero__result-badge is-${item.type || 'service'}`;
            badge.textContent = item.type_label || 'Result';

            link.append(copy, badge);
            list.append(link);
        });

        searchResults.replaceChildren(list);
        searchResults.classList.remove('d-none');
        searchResults.style.display = 'block';
    };

    const normalizeResults = (data) => {
        if (Array.isArray(data.results) && data.results.length) {
            return data.results.filter((item) => typeof item?.name === 'string' && resultUrl(item));
        }

        // Legacy payload: services only
        const serviceBase = (searchInput.dataset.serviceBase || '/service').replace(/\/$/, '');
        return (Array.isArray(data.services) ? data.services : [])
            .filter((service) => typeof service?.slug === 'string' && typeof service?.name === 'string')
            .map((service) => ({
                type: 'service',
                type_label: 'Service',
                name: service.name,
                slug: service.slug,
                url: `${serviceBase}/${encodeURIComponent(service.slug)}`,
                meta: null,
            }));
    };

    const findServices = async (query) => {
        const requestId = ++requestNumber;

        if (!query) {
            hideResults();
            return [];
        }

        try {
            const endpoint = searchInput.dataset.searchEndpoint || '/search-services';
            const response = await fetch(`${endpoint}?query=${encodeURIComponent(query)}`, {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                throw new Error(`Service search returned ${response.status}.`);
            }

            const data = await response.json();
            const items = normalizeResults(data);

            if (requestId !== requestNumber) {
                return [];
            }

            currentResults = items;
            if (!items.length) {
                showMessage('No results found. Try a different search.');
                return items;
            }

            renderResults(items);
            return items;
        } catch (error) {
            if (requestId === requestNumber) {
                currentResults = [];
                showMessage('Search is temporarily unavailable. Please try again.');
                console.error('Unable to search homepage catalog:', error);
            }

            return [];
        }
    };

    const openFirstResult = async () => {
        const query = searchInput.value.trim();
        if (!query) {
            searchInput.focus();
            return;
        }

        const items = currentResults.length ? currentResults : await findServices(query);
        const exactMatch = items.find((item) => item.name.toLowerCase() === query.toLowerCase());
        const item = exactMatch || items[0];
        const url = item ? resultUrl(item) : null;

        if (url) {
            window.location.assign(url);
        }
    };

    searchInput.addEventListener('input', () => {
        window.clearTimeout(searchTimeout);
        searchTimeout = window.setTimeout(() => findServices(searchInput.value.trim()), 300);
    });

    searchInput.addEventListener('focus', () => {
        const query = searchInput.value.trim();
        if (query) {
            findServices(query);
        }
    });

    searchInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            openFirstResult();
        } else if (event.key === 'Escape') {
            hideResults();
        }
    });

    searchButton.addEventListener('click', openFirstResult);

    document.querySelectorAll('[data-hero-search]').forEach((button) => {
        button.addEventListener('click', () => {
            const term = button.getAttribute('data-hero-search') || '';
            searchInput.value = term;
            searchInput.focus();
            searchInput.dispatchEvent(new Event('input', { bubbles: true }));
        });
    });

    document.addEventListener('click', (event) => {
        if (!searchInput.contains(event.target) && !searchResults.contains(event.target)) {
            hideResults();
        }
    });
}

document.querySelectorAll('.yk-home-blog-card__image img').forEach((image) => {
    image.addEventListener('error', () => {
        image.hidden = true;
        const fallback = image.parentElement?.querySelector('[data-blog-image-fallback]');
        if (fallback) {
            fallback.hidden = false;
        }
    }, { once: true });
});

document.querySelectorAll('[data-testimonial-avatar]').forEach((image) => {
    image.addEventListener('error', () => {
        image.hidden = true;
        const fallback = image.parentElement?.querySelector('.yk-home-testimonial-card__avatar-fallback');
        if (fallback) {
            fallback.hidden = false;
        }
    }, { once: true });
});

document.querySelectorAll('[data-event-image], [data-how-image]').forEach((image) => {
    image.addEventListener('error', () => {
        image.hidden = true;
        const fallback = image.parentElement?.querySelector('.yk-home-event-card__image-fallback, .yk-home-how-card__image-fallback');
        if (fallback) {
            fallback.hidden = false;
        }
    }, { once: true });
});

document.querySelectorAll('[data-testimonial-carousel]').forEach((carousel) => {
    const track = carousel.querySelector('[data-testimonial-track]');
    const previous = carousel.querySelector('[data-testimonial-previous]');
    const next = carousel.querySelector('[data-testimonial-next]');

    if (!(track instanceof HTMLElement) || !(previous instanceof HTMLButtonElement) || !(next instanceof HTMLButtonElement)) {
        return;
    }

    const updateControls = () => {
        const maxScrollLeft = track.scrollWidth - track.clientWidth;
        previous.disabled = track.scrollLeft <= 1;
        next.disabled = track.scrollLeft >= maxScrollLeft - 1;
    };

    const scrollByCard = (direction) => {
        const card = track.querySelector('.yk-home-testimonial-card');
        if (!(card instanceof HTMLElement)) {
            return;
        }

        const gap = Number.parseFloat(getComputedStyle(track).columnGap) || 0;
        track.scrollBy({
            left: direction * (card.getBoundingClientRect().width + gap),
            behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
        });
    };

    previous.addEventListener('click', () => scrollByCard(-1));
    next.addEventListener('click', () => scrollByCard(1));
    track.addEventListener('scroll', updateControls, { passive: true });
    window.addEventListener('resize', updateControls);
    updateControls();
});

document.querySelectorAll('[data-home-faq]').forEach((faq) => {
    faq.addEventListener('click', (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }

        const toggle = event.target.closest('[data-home-faq-toggle]');
        if (!toggle || !faq.contains(toggle)) {
            return;
        }

        const panelId = toggle.getAttribute('aria-controls');
        const panel = panelId ? document.getElementById(panelId) : null;
        if (!panel) {
            console.error('The FAQ answer panel could not be found.');
            return;
        }

        const shouldExpand = toggle.getAttribute('aria-expanded') !== 'true';
        faq.querySelectorAll('[data-home-faq-toggle][aria-expanded="true"]').forEach((openToggle) => {
            if (openToggle === toggle) {
                return;
            }

            const openPanelId = openToggle.getAttribute('aria-controls');
            const openPanel = openPanelId ? document.getElementById(openPanelId) : null;
            openToggle.setAttribute('aria-expanded', 'false');
            openPanel?.setAttribute('aria-hidden', 'true');
            if (openPanel) {
                openPanel.inert = true;
            }
        });

        toggle.setAttribute('aria-expanded', String(shouldExpand));
        panel.setAttribute('aria-hidden', String(!shouldExpand));
        panel.inert = !shouldExpand;
    });
});

document.querySelectorAll('[data-hero-video]').forEach((card) => {
    const video = card.querySelector('video');
    const playButtons = card.querySelectorAll('[data-hero-play]');

    if (!(video instanceof HTMLVideoElement) || !playButtons.length) {
        return;
    }

    const stop = () => {
        video.pause();
        video.controls = false;
        card.classList.remove('is-playing');
    };

    const play = async () => {
        card.classList.add('is-playing');
        video.controls = true;

        try {
            await video.play();
        } catch (error) {
            stop();
            console.error('The homepage video could not be played.', error);
        }
    };

    playButtons.forEach((button) => {
        button.addEventListener('click', play);
    });

    video.addEventListener('ended', stop);
    video.addEventListener('error', () => {
        stop();
        console.error('The homepage video file could not be loaded.');
    });
});

const revealTargets = document.querySelectorAll('[data-home-reveal]');
if (revealTargets.length && !window.matchMedia('(prefers-reduced-motion: reduce)').matches && 'IntersectionObserver' in window) {
    document.documentElement.classList.add('yk-home-reveal-enabled');

    const revealObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.12, rootMargin: '0px 0px -24px 0px' });

    revealTargets.forEach((target) => revealObserver.observe(target));
}

const bindHomeCarousel = (rootSelector, trackAttr, prevAttr, nextAttr) => {
    document.querySelectorAll(rootSelector).forEach((carousel) => {
        const track = carousel.querySelector(`[${trackAttr}]`);
        const previous = carousel.querySelector(`[${prevAttr}]`);
        const next = carousel.querySelector(`[${nextAttr}]`);

        if (!(track instanceof HTMLElement) || !(previous instanceof HTMLButtonElement) || !(next instanceof HTMLButtonElement)) {
            return;
        }

        const updateControls = () => {
            const maxScrollLeft = Math.max(0, track.scrollWidth - track.clientWidth);
            previous.disabled = track.scrollLeft <= 2;
            next.disabled = track.scrollLeft >= maxScrollLeft - 2;
        };

        const scrollAmount = () => {
            const item = track.querySelector(':scope > li');
            if (item instanceof HTMLElement) {
                const styles = window.getComputedStyle(track);
                const gap = Number.parseFloat(styles.columnGap || styles.gap || '0') || 0;
                return Math.max(item.getBoundingClientRect().width + gap, 160);
            }

            return Math.max(track.clientWidth * 0.75, 180);
        };

        const scrollByPage = (direction) => {
            track.scrollBy({
                left: direction * scrollAmount(),
                behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
            });
        };

        previous.addEventListener('click', () => scrollByPage(-1));
        next.addEventListener('click', () => scrollByPage(1));
        track.addEventListener('scroll', updateControls, { passive: true });
        window.addEventListener('resize', updateControls);
        updateControls();
    });
};

bindHomeCarousel('[data-categories-carousel]', 'data-categories-track', 'data-categories-prev', 'data-categories-next');
bindHomeCarousel('[data-services-carousel]', 'data-services-track', 'data-services-prev', 'data-services-next');
bindHomeCarousel('[data-tools-carousel]', 'data-tools-track', 'data-tools-prev', 'data-tools-next');

document.querySelectorAll('[data-public-header]').forEach((header) => {
    const mobileToggle = header.querySelector('[data-mobile-nav-toggle]');
    const mobileNav = header.querySelector('[data-mobile-nav]');
    const mobileBackdrop = header.querySelector('[data-mobile-nav-backdrop]');
    const mobileClose = header.querySelector('[data-mobile-nav-close]');
    const accountToggle = header.querySelector('[data-account-toggle]');
    const accountMenuRoot = header.querySelector('[data-account-menu]');
    const accountMenu = header.querySelector('[data-account-panel]') || accountMenuRoot?.querySelector('.yk-navbar__account-menu');
    const searchToggle = header.querySelector('[data-home-search-toggle]');
    const searchForm = header.querySelector('[data-header-search]');
    const desktopBreakpoint = window.matchMedia('(min-width: 64rem)');
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    let accountCloseTimer = null;

    const setAccountMenu = (isOpen) => {
        if (!accountToggle || !accountMenu) {
            return;
        }

        window.clearTimeout(accountCloseTimer);
        accountToggle.setAttribute('aria-expanded', String(isOpen));

        if (isOpen) {
            accountMenu.hidden = false;
            accountMenu.setAttribute('aria-hidden', 'false');
            requestAnimationFrame(() => {
                accountMenu.classList.add('is-open');
            });
            accountMenu.querySelector('a, button')?.focus();
            return;
        }

        accountMenu.classList.remove('is-open');
        accountMenu.setAttribute('aria-hidden', 'true');

        const finalizeClose = () => {
            accountMenu.hidden = true;
        };

        if (prefersReducedMotion.matches) {
            finalizeClose();
            return;
        }

        accountCloseTimer = window.setTimeout(finalizeClose, 200);
    };

    const setMobileNavigation = (isOpen) => {
        if (!mobileToggle || !mobileNav || !mobileBackdrop) {
            return;
        }

        mobileToggle.setAttribute('aria-expanded', String(isOpen));
        mobileToggle.setAttribute('aria-label', isOpen ? 'Close navigation menu' : 'Open navigation menu');
        mobileNav.classList.toggle('is-open', isOpen);
        mobileBackdrop.hidden = !isOpen;

        if (isOpen) {
            mobileNav.setAttribute('role', 'dialog');
            mobileNav.setAttribute('aria-modal', 'true');
            mobileNav.setAttribute('aria-label', 'Main menu');
        } else {
            mobileNav.removeAttribute('role');
            mobileNav.removeAttribute('aria-modal');
            mobileNav.removeAttribute('aria-label');
        }
        document.documentElement.classList.toggle('tazen-public-nav-open', isOpen);
        document.body.classList.toggle('tazen-public-nav-open', isOpen);

        if (isOpen) {
            mobileClose?.focus();
        } else if (document.activeElement !== mobileToggle) {
            mobileToggle.focus();
        }
    };

    mobileToggle?.addEventListener('click', () => {
        setMobileNavigation(mobileToggle.getAttribute('aria-expanded') !== 'true');
    });
    mobileClose?.addEventListener('click', () => setMobileNavigation(false));
    mobileBackdrop?.addEventListener('click', () => setMobileNavigation(false));

    searchToggle?.addEventListener('click', () => {
        if (!searchForm) {
            return;
        }

        const isOpen = searchToggle.getAttribute('aria-expanded') === 'true';
        searchToggle.setAttribute('aria-expanded', String(!isOpen));
        searchForm.classList.toggle('is-open', !isOpen);

        if (!isOpen) {
            searchForm.querySelector('input[name="query"]')?.focus();
        }
    });

    accountToggle?.addEventListener('click', (event) => {
        event.stopPropagation();
        if (!accountMenu) {
            return;
        }

        const isOpen = accountToggle.getAttribute('aria-expanded') === 'true';
        setAccountMenu(!isOpen);
    });

    searchForm?.addEventListener('submit', async (event) => {
        event.preventDefault();

        const input = searchForm.querySelector('input[name="query"]');
        const submitButton = searchForm.querySelector('button[type="submit"]');
        const status = searchForm.querySelector('[data-search-status]');
        const query = input?.value.trim();

        if (!query || !submitButton || !status) {
            return;
        }

        status.textContent = '';
        submitButton.disabled = true;

        try {
            const endpoint = searchForm.dataset.searchEndpoint;
            const serviceUrl = searchForm.dataset.serviceUrl;

            if (!endpoint || !serviceUrl) {
                throw new Error('The service search is not configured.');
            }

            const response = await fetch(`${endpoint}?query=${encodeURIComponent(query)}`, {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                throw new Error(`Service search returned ${response.status}.`);
            }

            const data = await response.json();
            const services = Array.isArray(data.services) ? data.services : [];
            const result = services.find((service) =>
                service.name?.toLowerCase() === query.toLowerCase()
            ) || services[0];

            if (!result?.slug) {
                status.textContent = 'No matching services found. Try another search.';
                return;
            }

            window.location.assign(`${serviceUrl}/${encodeURIComponent(result.slug)}`);
        } catch (error) {
            console.error('Unable to search services:', error);
            status.textContent = 'Search is unavailable. Please try again.';
        } finally {
            submitButton.disabled = false;
        }
    });

    header.addEventListener('click', (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }

        if (event.target.closest('[data-placeholder-link]')) {
            event.preventDefault();
        }

        if (accountMenu && accountToggle?.getAttribute('aria-expanded') === 'true' && !event.target.closest('[data-account-menu]')) {
            setAccountMenu(false);
        }

        if (mobileNav?.classList.contains('is-open') && event.target.closest('a[href]')) {
            setMobileNavigation(false);
        }
    });

    header.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            if (searchForm?.classList.contains('is-open')) {
                searchForm.classList.remove('is-open');
                searchToggle?.setAttribute('aria-expanded', 'false');
                searchToggle?.focus();
            } else if (accountMenu && accountToggle?.getAttribute('aria-expanded') === 'true') {
                setAccountMenu(false);
                accountToggle?.focus();
            } else if (mobileNav?.classList.contains('is-open')) {
                setMobileNavigation(false);
            }
        }

        if (event.key === 'Tab' && mobileNav?.classList.contains('is-open')) {
            const focusable = [...mobileNav.querySelectorAll('a[href], button:not([disabled]), input:not([disabled])')]
                .filter((element) => !element.closest('[hidden]'));
            const first = focusable[0];
            const last = focusable[focusable.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last?.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first?.focus();
            }
        }
    });

    document.addEventListener('click', (event) => {
        if (searchForm?.classList.contains('is-open') && !header.contains(event.target)) {
            searchForm.classList.remove('is-open');
            searchToggle?.setAttribute('aria-expanded', 'false');
        }

        if (
            accountMenu
            && accountToggle?.getAttribute('aria-expanded') === 'true'
            && !accountMenuRoot?.contains(event.target)
        ) {
            setAccountMenu(false);
        }
    });

    desktopBreakpoint.addEventListener('change', (event) => {
        if (event.matches && mobileNav?.classList.contains('is-open')) {
            setMobileNavigation(false);
        }
    });
});

document.querySelectorAll('[data-contact-redirect]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        const email = new FormData(form).get('email');

        if (typeof email !== 'string' || email.length === 0) {
            return;
        }

        window.location.href = `/contact?email=${encodeURIComponent(email)}`;
    });
});

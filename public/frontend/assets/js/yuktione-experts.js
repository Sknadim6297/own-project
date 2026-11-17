(() => {
    const root = document.querySelector("[data-experts]");
    if (!root) {
        return;
    }

    const endpoint = root.getAttribute("data-experts-endpoint") || window.location.pathname;
    const form = root.querySelector("[data-experts-search]");
    const input = root.querySelector("[data-experts-q]");
    const clearBtn = root.querySelector("[data-experts-clear]");
    const results = root.querySelector("[data-experts-results]");
    const filters = root.querySelector("[data-experts-filters]");

    if (!form || !input || !results) {
        return;
    }

    let timer = 0;
    let requestId = 0;
    let controller = null;
    let lastQuery = normalize(input.value);

    function normalize(value) {
        return String(value ?? "").trim();
    }

    function setClearVisible(show) {
        if (!clearBtn) {
            return;
        }
        clearBtn.hidden = !show;
        clearBtn.classList.toggle("is-hidden", !show);
    }

    function syncFilterLinks(query) {
        if (!filters) {
            return;
        }

        filters.querySelectorAll("a[href]").forEach((anchor) => {
            try {
                const url = new URL(anchor.href, window.location.origin);
                if (query) {
                    url.searchParams.set("q", query);
                } else {
                    url.searchParams.delete("q");
                }
                url.searchParams.delete("page");
                url.searchParams.delete("partial");
                anchor.href = `${url.pathname}${url.search}`;
            } catch (_error) {
                // Ignore malformed hrefs.
            }
        });
    }

    function syncBrowserUrl(query, page) {
        try {
            const url = new URL(window.location.href);
            if (query) {
                url.searchParams.set("q", query);
            } else {
                url.searchParams.delete("q");
            }
            if (page && Number(page) > 1) {
                url.searchParams.set("page", String(page));
            } else {
                url.searchParams.delete("page");
            }
            url.searchParams.delete("partial");
            window.history.replaceState({}, "", `${url.pathname}${url.search}`);
        } catch (_error) {
            // History API unavailable — ignore.
        }
    }

    function buildRequestUrl(query, page) {
        const url = new URL(endpoint, window.location.origin);
        const current = new URL(window.location.href);

        current.searchParams.forEach((value, key) => {
            if (key === "q" || key === "page" || key === "partial") {
                return;
            }
            url.searchParams.append(key, value);
        });

        if (query) {
            url.searchParams.set("q", query);
        }
        if (page && Number(page) > 1) {
            url.searchParams.set("page", String(page));
        }
        url.searchParams.set("partial", "1");

        return url;
    }

    async function fetchResults({ query, page = null, pushUrl = true } = {}) {
        const trimmed = normalize(query);
        const id = ++requestId;

        if (controller) {
            controller.abort();
        }
        controller = new AbortController();

        results.setAttribute("aria-busy", "true");
        results.classList.add("is-loading");

        try {
            const url = buildRequestUrl(trimmed, page);
            const response = await fetch(url.toString(), {
                headers: {
                    Accept: "text/html",
                    "X-Requested-With": "XMLHttpRequest",
                },
                credentials: "same-origin",
                signal: controller.signal,
            });

            if (!response.ok) {
                throw new Error(`Experts search failed (${response.status})`);
            }

            const html = await response.text();
            if (id !== requestId) {
                return;
            }

            results.innerHTML = html;
            lastQuery = trimmed;
            setClearVisible(trimmed !== "");
            syncFilterLinks(trimmed);
            if (pushUrl) {
                syncBrowserUrl(trimmed, page);
            }

            const hiddenQ = form.querySelector('input[name="q"]');
            if (hiddenQ && hiddenQ !== input) {
                hiddenQ.value = trimmed;
            }
        } catch (error) {
            if (error && error.name === "AbortError") {
                return;
            }
            console.error(error);
        } finally {
            if (id === requestId) {
                results.setAttribute("aria-busy", "false");
                results.classList.remove("is-loading");
            }
        }
    }

    function scheduleSearch(immediate = false) {
        const query = normalize(input.value);
        setClearVisible(input.value.length > 0);

        window.clearTimeout(timer);
        timer = window.setTimeout(() => {
            if (query === lastQuery) {
                return;
            }
            fetchResults({ query, page: null, pushUrl: true });
        }, immediate ? 0 : 180);
    }

    input.addEventListener("input", () => {
        scheduleSearch(false);
    });

    input.addEventListener("search", () => {
        // Native clear on type=search (Esc / clear icon in some browsers).
        scheduleSearch(true);
    });

    form.addEventListener("submit", (event) => {
        event.preventDefault();
        scheduleSearch(true);
    });

    clearBtn?.addEventListener("click", () => {
        input.value = "";
        setClearVisible(false);
        input.focus();
        scheduleSearch(true);
    });

    results.addEventListener("click", (event) => {
        const target = event.target;
        if (!(target instanceof Element)) {
            return;
        }

        const clearSearch = target.closest("[data-experts-clear-search]");
        if (clearSearch) {
            event.preventDefault();
            input.value = "";
            setClearVisible(false);
            input.focus();
            scheduleSearch(true);
            return;
        }

        const pageLink = target.closest("[data-experts-page]");
        if (pageLink instanceof HTMLAnchorElement) {
            event.preventDefault();
            try {
                const url = new URL(pageLink.href, window.location.origin);
                const page = url.searchParams.get("page");
                fetchResults({ query: normalize(input.value), page, pushUrl: true });
            } catch (_error) {
                window.location.assign(pageLink.href);
            }
        }
    });

    setClearVisible(normalize(input.value) !== "");
    syncFilterLinks(normalize(input.value));
})();

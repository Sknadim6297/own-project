(() => {
    const root = document.querySelector("[data-experts]");
    if (!root) {
        return;
    }

    // Favourite (heart) — visual toggle only, nothing is stored yet.
    // Delegated so it keeps working after live-search replaces the results.
    root.addEventListener("click", (event) => {
        const target = event.target;
        if (!(target instanceof Element)) {
            return;
        }
        const fav = target.closest("[data-pro-fav]");
        if (!fav) {
            return;
        }
        event.preventDefault();
        const pressed = fav.getAttribute("aria-pressed") !== "true";
        fav.setAttribute("aria-pressed", pressed ? "true" : "false");
        const card = fav.closest(".yk-pcard");
        const name = card?.querySelector(".yk-pcard__name")?.textContent?.trim() || "expert";
        fav.setAttribute("aria-label", pressed ? `Remove ${name} from favourites` : `Save ${name} to favourites`);
        fav.classList.remove("is-pop");
        void fav.offsetWidth;
        fav.classList.add("is-pop");
    });

    // Experience / sort selects submit the whole filter form (keeps the current search text).
    root.querySelectorAll("[data-experts-auto-submit]").forEach((select) => {
        select.addEventListener("change", () => {
            if (select.hasAttribute("data-experts-category-select")) {
                // A new category invalidates any service-level filter carried in the form.
                select.form?.querySelectorAll('input[name="service"], input[name="sub_service"]').forEach((input) => input.remove());
            }
            select.form?.submit();
        });
    });

    // Category tabs: scroll arrows + edge fades, active tab kept in view.
    const cats = root.querySelector("[data-pro-cats]");
    const catsScroll = cats?.querySelector("[data-pro-cats-scroll]");
    if (cats && catsScroll) {
        const prev = cats.querySelector("[data-pro-cats-prev]");
        const next = cats.querySelector("[data-pro-cats-next]");

        const syncCats = () => {
            const max = catsScroll.scrollWidth - catsScroll.clientWidth;
            const atStart = catsScroll.scrollLeft <= 2;
            const atEnd = catsScroll.scrollLeft >= max - 2;
            const overflowing = max > 4;
            cats.classList.toggle("has-overflow-start", overflowing && !atStart);
            cats.classList.toggle("has-overflow-end", overflowing && !atEnd);
            if (prev) {
                prev.hidden = !(overflowing && !atStart);
            }
            if (next) {
                next.hidden = !(overflowing && !atEnd);
            }
        };

        const step = () => Math.max(160, Math.round(catsScroll.clientWidth * 0.7));
        prev?.addEventListener("click", () => catsScroll.scrollBy({ left: -step(), behavior: "smooth" }));
        next?.addEventListener("click", () => catsScroll.scrollBy({ left: step(), behavior: "smooth" }));
        catsScroll.addEventListener("scroll", syncCats, { passive: true });
        window.addEventListener("resize", syncCats);

        const active = catsScroll.querySelector(".is-active");
        if (active) {
            const left = active.offsetLeft - (catsScroll.clientWidth - active.offsetWidth) / 2;
            catsScroll.scrollTo({ left: Math.max(0, left), behavior: "auto" });
        }
        syncCats();
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

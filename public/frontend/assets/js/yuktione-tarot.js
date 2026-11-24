/**
 * Phase 4F — Tarot Reflection interactions (native JS).
 * Selection UX + reveal presentation only. Card identities stay server-authoritative.
 */
(function () {
    'use strict';

    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function enhanceSelectForms() {
        document.querySelectorAll('[data-tarot-select-form]').forEach(function (form) {
            var button = form.querySelector('[data-tarot-continue]');
            var sync = function () {
                var chosen = form.querySelector('input[type="radio"]:checked');
                if (button) {
                    button.disabled = !chosen;
                    button.setAttribute('aria-disabled', chosen ? 'false' : 'true');
                }
                form.querySelectorAll('.yk-tarot__option').forEach(function (card) {
                    var input = card.querySelector('input[type="radio"]');
                    card.classList.toggle('is-selected', !!(input && input.checked));
                });
            };
            form.addEventListener('change', sync);
            // Some mobile browsers update radio state on click without a reliable change event order.
            form.querySelectorAll('.yk-tarot__option').forEach(function (card) {
                card.addEventListener('click', function () {
                    window.setTimeout(sync, 0);
                });
            });
            sync();
        });
    }

    function enhanceQuestion() {
        var area = document.getElementById('tarot-question');
        var count = document.querySelector('[data-tarot-count]');
        if (!area || !count) return;
        var sync = function () { count.textContent = String(area.value.length); };
        area.addEventListener('input', sync);
        sync();
        document.querySelectorAll('[data-tarot-example]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                area.value = btn.getAttribute('data-tarot-example') || '';
                sync();
                area.focus();
            });
        });
    }

    function markSubmitterBusy(btn, label) {
        if (!btn) return;
        // Never set disabled on the active submitter during submit — Chromium
        // aborts the native POST when the submitter is disabled mid-submit.
        btn.classList.add('is-busy');
        btn.setAttribute('aria-busy', 'true');
        if (label) {
            if (!btn.getAttribute('data-tarot-original-label')) {
                btn.setAttribute('data-tarot-original-label', btn.textContent.trim());
            }
            btn.textContent = label;
        }
    }

    function preventDoubleSubmit() {
        document.querySelectorAll('[data-tarot-save-once], [data-tarot-once]').forEach(function (form) {
            // Fan draw forms use the dedicated handler below — binding both
            // would set data-tarot-submitted then immediately preventDefault.
            if (form.closest('.yk-tarot__fan')) return;

            form.addEventListener('submit', function (event) {
                if (form.getAttribute('data-tarot-submitted') === '1') {
                    event.preventDefault();
                    return;
                }
                form.setAttribute('data-tarot-submitted', '1');
                form.classList.add('is-submitting');

                var btn = form.querySelector('button[type="submit"]');
                var saving = btn && (
                    btn.getAttribute('data-tarot-cta') === 'save'
                    || /save/i.test(btn.textContent || '')
                );
                markSubmitterBusy(btn, saving ? 'Saving…' : null);
            });
        });

        document.querySelectorAll('.yk-tarot__fan form').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (form.getAttribute('data-tarot-submitted') === '1') {
                    event.preventDefault();
                    return;
                }
                form.setAttribute('data-tarot-submitted', '1');

                var btn = form.querySelector('button[type="submit"]');
                if (btn) {
                    btn.classList.add('is-picking');
                    markSubmitterBusy(btn, null);
                }
                // Safe to disable sibling cards; they are not the submitter.
                document.querySelectorAll('.yk-tarot__fan button[type="submit"]').forEach(function (other) {
                    if (other !== btn) {
                        other.disabled = true;
                        other.setAttribute('aria-disabled', 'true');
                    }
                });
            });
        });

        // Restore controls if the user returns via bfcache after a cancelled navigation.
        window.addEventListener('pageshow', function (event) {
            if (!event.persisted) return;
            document.querySelectorAll('[data-tarot-submitted="1"]').forEach(function (form) {
                form.removeAttribute('data-tarot-submitted');
                form.classList.remove('is-submitting');
                form.querySelectorAll('button[type="submit"]').forEach(function (btn) {
                    btn.disabled = false;
                    btn.removeAttribute('aria-disabled');
                    btn.removeAttribute('aria-busy');
                    btn.classList.remove('is-picking', 'is-busy');
                    var original = btn.getAttribute('data-tarot-original-label')
                        || btn.getAttribute('data-tarot-save-label');
                    if (original) btn.textContent = original;
                });
            });
        });
    }

    function revealMotion() {
        var cards = document.querySelectorAll('[data-tarot-reveal-card]');
        if (!cards.length) return;

        var status = document.querySelector('[data-tarot-reveal-status]');
        var names = [];

        cards.forEach(function (card, index) {
            var flip = card.querySelector('[data-tarot-flip]');
            var title = card.querySelector('h2');
            if (title) names.push(title.textContent.trim());

            card.style.setProperty('--yk-reveal-delay', (index * 140) + 'ms');
            card.classList.add('is-animate-in');

            if (!flip) return;
            if (reduceMotion) {
                flip.classList.add('is-static', 'is-flipped');
                return;
            }
            window.setTimeout(function () {
                flip.classList.add('is-flipped');
            }, 180 + (index * 220));
        });

        if (status && names.length) {
            status.textContent = names.length === 1
                ? ('Revealed: ' + names[0])
                : ('Revealed ' + names.length + ' cards: ' + names.join('; '));
        }
    }

    function markReady() {
        var root = document.querySelector('.yk-tarot');
        if (root) root.classList.add('is-ready');
    }

    function boot() {
        enhanceSelectForms();
        enhanceQuestion();
        preventDoubleSubmit();
        revealMotion();
        markReady();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();

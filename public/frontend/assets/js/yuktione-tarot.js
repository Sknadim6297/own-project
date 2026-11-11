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
                if (button) button.disabled = !chosen;
                form.querySelectorAll('.yk-tarot__option').forEach(function (card) {
                    var input = card.querySelector('input[type="radio"]');
                    card.classList.toggle('is-selected', !!(input && input.checked));
                });
            };
            form.addEventListener('change', sync);
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

    function preventDoubleSubmit() {
        document.querySelectorAll('[data-tarot-save-once], [data-tarot-once]').forEach(function (form) {
            form.addEventListener('submit', function () {
                var btn = form.querySelector('button[type="submit"]');
                if (!btn || btn.disabled) return;
                btn.disabled = true;
                if (btn.getAttribute('data-tarot-cta') === 'save' || /save/i.test(btn.textContent || '')) {
                    btn.textContent = 'Saving…';
                }
                form.classList.add('is-submitting');
            });
        });

        document.querySelectorAll('.yk-tarot__fan form').forEach(function (form) {
            form.addEventListener('submit', function () {
                var btn = form.querySelector('button[type="submit"]');
                if (btn) {
                    btn.disabled = true;
                    btn.classList.add('is-picking');
                }
                document.querySelectorAll('.yk-tarot__fan button[type="submit"]').forEach(function (other) {
                    if (other !== btn) other.disabled = true;
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

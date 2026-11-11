/**
 * Phase 4I — Nutrition Tracker interactions (native JS).
 * Duplicate-submit / search busy only. Nutrition totals remain server-authoritative.
 */
(function () {
    'use strict';

    function guardForms() {
        document.querySelectorAll('[data-nut-submit-once]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (form.getAttribute('data-submitting') === '1') {
                    event.preventDefault();
                    return;
                }
                form.setAttribute('data-submitting', '1');
                form.querySelectorAll('button[type="submit"]').forEach(function (btn) {
                    btn.disabled = true;
                    btn.classList.add('is-busy');
                });
            });
        });
    }

    function guardSearch() {
        document.querySelectorAll('[data-nut-search]').forEach(function (form) {
            form.addEventListener('submit', function () {
                form.querySelectorAll('button[type="submit"]').forEach(function (btn) {
                    btn.disabled = true;
                    btn.classList.add('is-busy');
                    if (!btn.getAttribute('data-label')) {
                        btn.setAttribute('data-label', btn.textContent);
                    }
                    btn.textContent = 'Searching…';
                });
            });
        });
    }

    function boot() {
        guardForms();
        guardSearch();
        var root = document.querySelector('[data-yk-nut]');
        if (root) root.classList.add('is-ready');
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();

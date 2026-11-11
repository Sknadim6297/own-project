/**
 * Phase 4H — Weight Journey interactions (native JS).
 * Duplicate-submit guard only. Totals/charts remain server-authoritative.
 */
(function () {
    'use strict';

    function guardForms() {
        document.querySelectorAll('[data-wj-submit-once]').forEach(function (form) {
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

    function boot() {
        guardForms();
        var root = document.querySelector('[data-yk-wj]');
        if (root) root.classList.add('is-ready');
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();

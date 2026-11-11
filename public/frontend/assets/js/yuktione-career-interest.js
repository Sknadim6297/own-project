/**
 * Phase 4E — Career Interest Test interactions (native JS).
 * Enables Continue only when every radio group on the page is answered.
 * Does not calculate scores — server remains authoritative.
 */
(function () {
    'use strict';

    var form = document.querySelector('[data-cit-form]');
    if (!form) return;

    var button = form.querySelector('[data-cit-continue]');
    var submitting = false;
    var groups = {};

    form.querySelectorAll('input[type="radio"]').forEach(function (input) {
        groups[input.name] = true;
    });

    var sync = function () {
        var ready = Object.keys(groups).every(function (name) {
            return !!form.querySelector('input[name="' + name + '"]:checked');
        });
        if (button) button.disabled = !ready;
    };

    form.addEventListener('change', sync);
    form.addEventListener('submit', function (event) {
        if (submitting) {
            event.preventDefault();
            return;
        }
        var ready = Object.keys(groups).every(function (name) {
            return !!form.querySelector('input[name="' + name + '"]:checked');
        });
        if (!ready) {
            event.preventDefault();
            sync();
            return;
        }
        submitting = true;
        if (button) button.disabled = true;
    });

    sync();
})();

/**
 * Phase 4C — Digital Journal interactions (native JS).
 */
(function () {
    'use strict';

    function initWriteForm() {
        var form = document.querySelector('[data-journal-form]');
        if (!form) return;

        var title = form.querySelector('[data-journal-title]');
        var body = form.querySelector('[data-journal-body]');
        var count = form.querySelector('[data-journal-count]');
        var bodyMax = Number(form.getAttribute('data-body-max') || 5000);
        var titleTouched = form.getAttribute('data-preserve-title') === '1'
            || Boolean(title && title.value.trim() !== '' && !form.querySelector('[data-journal-prompt].is-selected'));

        var syncCount = function () {
            if (!body || !count) return;
            count.textContent = body.value.length + ' / ' + bodyMax.toLocaleString();
        };

        if (body) body.addEventListener('input', syncCount);
        if (title) {
            title.addEventListener('input', function () {
                titleTouched = true;
            });
        }

        form.querySelectorAll('[data-journal-prompt]').forEach(function (label) {
            var input = label.querySelector('input');
            if (!input) return;
            input.addEventListener('change', function () {
                form.querySelectorAll('[data-journal-prompt]').forEach(function (node) {
                    node.classList.remove('is-selected');
                });
                label.classList.add('is-selected');
                if (title && !titleTouched) {
                    title.value = label.getAttribute('data-prompt-label') || '';
                }
            });
        });

        syncCount();
    }

    function initDeleteDialog() {
        var dialog = document.querySelector('[data-journal-delete-dialog]');
        var openBtn = document.querySelector('[data-journal-delete-open]');
        if (!dialog || !openBtn) return;

        var confirmBtn = dialog.querySelector('[data-journal-delete-confirm]');
        var lastFocus = null;

        var open = function () {
            lastFocus = document.activeElement;
            dialog.hidden = false;
            document.body.classList.add('yk-journal-dialog-open');
            if (confirmBtn) confirmBtn.focus();
        };

        var close = function () {
            dialog.hidden = true;
            document.body.classList.remove('yk-journal-dialog-open');
            if (lastFocus && lastFocus.focus) lastFocus.focus();
        };

        openBtn.addEventListener('click', open);
        dialog.querySelectorAll('[data-journal-delete-cancel]').forEach(function (node) {
            node.addEventListener('click', close);
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !dialog.hidden) close();
        });
    }

    initWriteForm();
    initDeleteDialog();
})();

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

        var progress = form.querySelector('[data-journal-progress]');
        var guide = form.querySelector('[data-journal-guide]');
        var guideText = form.querySelector('[data-journal-guide-text]');

        var syncCount = function () {
            if (!body) return;
            if (count) count.textContent = body.value.length + ' / ' + bodyMax.toLocaleString();
            if (progress) {
                var pct = Math.min(100, (body.value.length / bodyMax) * 100);
                progress.style.width = pct + '%';
                progress.classList.toggle('is-near', pct >= 90);
            }
        };

        var syncGuide = function (label) {
            if (!guide || !guideText) return;
            var question = label ? (label.getAttribute('data-prompt-question') || '') : '';
            guideText.textContent = question;
            guide.hidden = question === '';
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
                syncGuide(label);
                if (title && !titleTouched) {
                    title.value = label.getAttribute('data-prompt-label') || '';
                }
            });
        });

        var saveBtn = form.querySelector('[data-journal-save]');
        var saveLabel = form.querySelector('[data-journal-save-label]');
        var defaultLabel = saveLabel ? saveLabel.textContent : '';
        form.addEventListener('submit', function (event) {
            if (form.getAttribute('data-submitting') === '1') {
                event.preventDefault();
                return;
            }
            form.setAttribute('data-submitting', '1');
            if (saveBtn) {
                saveBtn.disabled = true;
                saveBtn.classList.add('is-saving');
                saveBtn.setAttribute('aria-busy', 'true');
            }
            if (saveLabel) {
                saveLabel.textContent = 'Saving…';
            }
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

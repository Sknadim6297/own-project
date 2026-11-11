/**
 * Phase 4C — Gratitude Journal guided steps (native JS).
 * Preserves all three answer fields in the DOM; shows one step at a time.
 */
(function () {
    'use strict';

    function initGuidedSteps() {
        var form = document.querySelector('[data-gratitude-form]');
        var root = document.querySelector('[data-gratitude-steps], [data-gratitude-carousel]');
        if (!form || !root) return;

        var cards = Array.prototype.slice.call(root.querySelectorAll('[data-gratitude-card]'));
        var dots = Array.prototype.slice.call(root.querySelectorAll('[data-gratitude-dot]'));
        var status = root.querySelector('[data-gratitude-status]');
        var prevBtn = root.querySelector('[data-gratitude-prev]');
        var nextBtn = root.querySelector('[data-gratitude-next]');
        var skipBtn = root.querySelector('[data-gratitude-skip]');
        var error = form.querySelector('[data-gratitude-error]');
        var progressBar = root.querySelector('[data-gratitude-progress]');
        var minAnswered = Number(form.getAttribute('data-min-answered') || 1);
        var answerMax = Number(form.getAttribute('data-answer-max') || 1000);
        var index = 0;

        var answerOf = function (card) {
            var field = card.querySelector('[data-gratitude-answer]');
            return field ? field.value.trim() : '';
        };

        var answeredCount = function () {
            return cards.reduce(function (total, card) {
                return total + (answerOf(card) ? 1 : 0);
            }, 0);
        };

        var syncCounts = function () {
            cards.forEach(function (card) {
                var field = card.querySelector('[data-gratitude-answer]');
                var count = card.querySelector('[data-gratitude-count]');
                if (field && count) count.textContent = String(field.value.length);
            });
        };

        var show = function (nextIndex) {
            index = Math.max(0, Math.min(cards.length - 1, nextIndex));
            cards.forEach(function (card, i) {
                var active = i === index;
                card.classList.toggle('is-active', active);
                card.setAttribute('aria-hidden', active ? 'false' : 'true');
                if (active) card.removeAttribute('hidden');
                else card.setAttribute('hidden', 'hidden');
            });
            dots.forEach(function (dot, i) {
                var active = i === index;
                dot.classList.toggle('is-active', active);
                dot.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            if (status) status.textContent = 'Card ' + (index + 1) + ' of ' + cards.length;
            if (progressBar) {
                var pct = Math.round(((index + 1) / Math.max(1, cards.length)) * 100);
                progressBar.style.width = pct + '%';
                progressBar.setAttribute('aria-valuenow', String(pct));
            }
            var hasAnswer = answerOf(cards[index]) !== '';
            if (prevBtn) prevBtn.hidden = index === 0;
            if (nextBtn) nextBtn.hidden = !(hasAnswer && index < cards.length - 1);
            if (skipBtn) skipBtn.hidden = hasAnswer || index >= cards.length - 1;
        };

        var go = function (delta) {
            show(index + delta);
            var field = cards[index] && cards[index].querySelector('[data-gratitude-answer]');
            if (field) field.focus();
        };

        cards.forEach(function (card) {
            var field = card.querySelector('[data-gratitude-answer]');
            if (!field) return;
            field.addEventListener('input', function () {
                if (field.value.length > answerMax) {
                    field.value = field.value.slice(0, answerMax);
                }
                syncCounts();
                show(index);
            });
        });

        if (prevBtn) prevBtn.addEventListener('click', function () { go(-1); });
        if (nextBtn) {
            nextBtn.addEventListener('click', function () {
                if (answerOf(cards[index])) go(1);
            });
        }
        if (skipBtn) skipBtn.addEventListener('click', function () { go(1); });
        dots.forEach(function (dot) {
            dot.addEventListener('click', function () {
                show(Number(dot.getAttribute('data-card-index') || 0));
                var field = cards[index] && cards[index].querySelector('[data-gratitude-answer]');
                if (field) field.focus();
            });
        });

        form.addEventListener('submit', function (event) {
            if (answeredCount() >= minAnswered) {
                if (error) {
                    error.hidden = true;
                    error.textContent = '';
                }
                return;
            }
            event.preventDefault();
            if (error) {
                error.hidden = false;
                error.textContent = 'Please fill in at least one card before saving.';
            }
            show(0);
            var first = cards[0] && cards[0].querySelector('[data-gratitude-answer]');
            if (first) first.focus();
        });

        syncCounts();
        show(0);
    }

    function initDeleteDialog() {
        var dialog = document.querySelector('[data-gratitude-delete-dialog]');
        var openBtn = document.querySelector('[data-gratitude-delete-open]');
        if (!dialog || !openBtn) return;

        var confirmBtn = dialog.querySelector('[data-gratitude-delete-confirm]');
        var lastFocus = null;

        var open = function () {
            lastFocus = document.activeElement;
            dialog.hidden = false;
            document.body.classList.add('yk-gratitude-dialog-open');
            if (confirmBtn) confirmBtn.focus();
        };

        var close = function () {
            dialog.hidden = true;
            document.body.classList.remove('yk-gratitude-dialog-open');
            if (lastFocus && lastFocus.focus) lastFocus.focus();
        };

        openBtn.addEventListener('click', open);
        dialog.querySelectorAll('[data-gratitude-delete-cancel]').forEach(function (node) {
            node.addEventListener('click', close);
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !dialog.hidden) close();
        });
    }

    initGuidedSteps();
    initDeleteDialog();
})();

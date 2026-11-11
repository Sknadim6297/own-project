/**
 * Phase 4B — Daily Mood Check-in interactions (native JS only).
 */
(function () {
    'use strict';

    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function initMoodSelection() {
        var form = document.querySelector('[data-mood-form]');
        if (!form) return;
        var button = form.querySelector('[data-mood-continue]');
        var sync = function () {
            var chosen = form.querySelector('input[name="mood"]:checked');
            if (button) button.disabled = !chosen;
            form.querySelectorAll('[data-mood-card]').forEach(function (card) {
                var input = card.querySelector('input[name="mood"]');
                var on = !!(input && input.checked);
                card.classList.toggle('is-selected', on);
                if (on) card.setAttribute('data-selected', 'true');
                else card.removeAttribute('data-selected');
            });
        };
        form.addEventListener('change', sync);
        sync();
    }

    function initEmotions() {
        var form = document.querySelector('[data-emotion-form]');
        if (!form) return;
        var limit = parseInt(form.getAttribute('data-emotion-limit'), 10) || 6;
        var count = form.querySelector('[data-emotion-count]');
        var sync = function () {
            var boxes = Array.prototype.slice.call(form.querySelectorAll('input[name="emotions[]"]'));
            var chosen = boxes.filter(function (box) { return box.checked; });
            if (count) count.textContent = chosen.length + ' of ' + limit + ' selected';
            boxes.forEach(function (box) {
                var chip = box.closest('[data-emotion-chip]');
                if (!chip) return;
                var on = box.checked;
                chip.classList.toggle('is-selected', on);
                if (on) chip.setAttribute('data-selected', 'true');
                else chip.removeAttribute('data-selected');
                var locked = !on && chosen.length >= limit;
                box.disabled = locked;
                chip.classList.toggle('is-locked', locked);
            });
        };
        form.addEventListener('change', function (event) {
            var box = event.target;
            if (!box || box.name !== 'emotions[]') return;
            var chosen = form.querySelectorAll('input[name="emotions[]"]:checked').length;
            if (box.checked && chosen > limit) {
                box.checked = false;
            }
            sync();
        });
        sync();
    }

    function initReflection() {
        var form = document.querySelector('[data-reflection-form]');
        if (!form) return;
        var input = form.querySelector('[data-reflection-input]');
        var count = form.querySelector('[data-reflection-count]');
        var confirmBox = form.querySelector('[data-prompt-confirm]');
        var limit = parseInt(form.getAttribute('data-reflection-limit'), 10) || 1000;
        var pending = '';

        if (!input) return;

        var lengthOf = function (value) {
            return Array.from(value).length;
        };

        var sync = function () {
            if (lengthOf(input.value) > limit) {
                input.value = Array.from(input.value).slice(0, limit).join('');
            }
            if (count) count.textContent = lengthOf(input.value) + ' / 1,000';
            if (!reduceMotion) {
                input.style.height = 'auto';
                input.style.height = Math.min(Math.max(input.scrollHeight, 140), 240) + 'px';
            }
        };

        var insert = function (prompt) {
            var current = input.value;
            var next = current.trim() === '' ? prompt : current.replace(/\s*$/, '') + '\n' + prompt;
            input.value = Array.from(next).slice(0, limit).join('');
            sync();
            input.focus();
        };

        form.querySelectorAll('[data-reflection-prompt]').forEach(function (button) {
            button.addEventListener('click', function () {
                var prompt = button.getAttribute('data-reflection-prompt') || '';
                if (input.value.trim() === '') {
                    insert(prompt);
                    return;
                }
                pending = prompt;
                if (confirmBox) confirmBox.hidden = false;
            });
        });

        var add = form.querySelector('[data-prompt-add]');
        var cancel = form.querySelector('[data-prompt-cancel]');
        if (add) {
            add.addEventListener('click', function () {
                if (pending) insert(pending);
                pending = '';
                if (confirmBox) confirmBox.hidden = true;
            });
        }
        if (cancel) {
            cancel.addEventListener('click', function () {
                pending = '';
                if (confirmBox) confirmBox.hidden = true;
                input.focus();
            });
        }

        input.addEventListener('input', sync);
        sync();
    }

    initMoodSelection();
    initEmotions();
    initReflection();
})();

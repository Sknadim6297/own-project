/**
 * Career Interest Test interactions (native JS).
 * - Enables Continue only when every radio group on the page is answered.
 * - Marks answered questions and shows an "n of N answered" counter.
 * - After answering, gently scrolls the next unanswered question into view.
 * Does not calculate scores — the server remains authoritative.
 */
(function () {
    'use strict';

    var form = document.querySelector('[data-cit-form]');
    if (!form) return;

    var button = form.querySelector('[data-cit-continue]');
    var counter = form.querySelector('[data-cit-answered]');
    var questions = Array.prototype.slice.call(form.querySelectorAll('.yk-cit__question'));
    var submitting = false;
    var groups = {};
    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    form.querySelectorAll('input[type="radio"]').forEach(function (input) {
        groups[input.name] = true;
    });

    var answered = function (name) {
        return !!form.querySelector('input[name="' + name + '"]:checked');
    };

    var allAnswered = function () {
        return Object.keys(groups).every(answered);
    };

    var sync = function () {
        var count = 0;
        questions.forEach(function (fieldset) {
            var input = fieldset.querySelector('input[type="radio"]');
            var done = input ? answered(input.name) : false;
            fieldset.classList.toggle('is-answered', done);
            if (done) count += 1;
        });
        if (counter) counter.textContent = String(count);
        if (button) button.disabled = !allAnswered();
    };

    form.addEventListener('change', function (event) {
        sync();

        var target = event.target;
        if (!target || target.type !== 'radio') return;
        var current = target.closest('.yk-cit__question');
        var index = questions.indexOf(current);
        if (index < 0) return;

        for (var i = index + 1; i < questions.length; i += 1) {
            if (!questions[i].classList.contains('is-answered')) {
                questions[i].scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'center' });
                break;
            }
        }
    });

    form.addEventListener('submit', function (event) {
        if (submitting || !allAnswered()) {
            event.preventDefault();
            sync();
            return;
        }
        submitting = true;
        if (button) button.disabled = true;
    });

    sync();
})();

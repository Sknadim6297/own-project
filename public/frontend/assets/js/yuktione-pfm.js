(function () {
    function initWizard(form) {
        if (!form || form.getAttribute('data-pfm-wizard') !== '1') {
            return;
        }

        var steps = Array.prototype.slice.call(form.querySelectorAll('[data-pfm-step]'));
        if (!steps.length) {
            return;
        }

        var labels = Array.prototype.slice.call(form.querySelectorAll('[data-pfm-step-label]'));
        var prevBtn = form.querySelector('[data-pfm-prev]');
        var nextBtn = form.querySelector('[data-pfm-next]');
        var submitBtn = form.querySelector('[data-pfm-submit]');
        var index = 0;

        function visibleRequired(step) {
            return Array.prototype.slice.call(step.querySelectorAll('input, select, textarea')).filter(function (el) {
                return el.willValidate !== false && el.hasAttribute('required') && !el.disabled;
            });
        }

        function validateStep(step) {
            var fields = visibleRequired(step);
            for (var i = 0; i < fields.length; i += 1) {
                if (!fields[i].checkValidity()) {
                    fields[i].reportValidity();
                    return false;
                }
            }
            return true;
        }

        function render() {
            steps.forEach(function (step, i) {
                var active = i === index;
                step.hidden = !active;
                if (active) {
                    var focusable = step.querySelector('input, select, textarea, button');
                    if (focusable) {
                        focusable.focus({ preventScroll: true });
                    }
                }
            });

            labels.forEach(function (label, i) {
                label.classList.toggle('is-active', i === index);
                label.classList.toggle('is-done', i < index);
            });

            if (prevBtn) {
                prevBtn.hidden = index === 0;
            }
            if (nextBtn) {
                nextBtn.hidden = index >= steps.length - 1;
            }
            if (submitBtn) {
                submitBtn.hidden = index < steps.length - 1;
            }
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', function () {
                index = Math.max(0, index - 1);
                render();
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', function () {
                if (!validateStep(steps[index])) {
                    return;
                }
                index = Math.min(steps.length - 1, index + 1);
                render();
            });
        }

        form.addEventListener('submit', function (event) {
            if (!validateStep(steps[index])) {
                event.preventDefault();
            }
        });

        render();
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('form[data-pfm-form]').forEach(initWizard);
    });
})();

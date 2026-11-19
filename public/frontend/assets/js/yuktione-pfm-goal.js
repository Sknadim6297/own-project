(function () {
    'use strict';

    var root = document.querySelector('[data-yk-goal]');
    if (!root) {
        return;
    }
    var form = root.querySelector('form[data-pfm-form]');
    if (!form) {
        return;
    }

    var DASH = '\u2014';
    var thisYear = parseInt(root.getAttribute('data-goal-year'), 10) || new Date().getFullYear();

    function field(name) {
        return form.querySelector('[name="' + name + '"]');
    }

    function num(name) {
        var el = field(name);
        if (!el || el.value === '') {
            return null;
        }
        var value = parseFloat(el.value);
        return isFinite(value) ? value : null;
    }

    function rupees(value) {
        return '\u20B9' + Math.round(value).toLocaleString('en-IN');
    }

    function words(value) {
        if (value >= 1e7) {
            return trim(value / 1e7) + ' crore';
        }
        if (value >= 1e5) {
            return trim(value / 1e5) + ' lakh';
        }
        if (value >= 1e3) {
            return trim(value / 1e3) + ' thousand';
        }
        return '';
    }

    function trim(value) {
        return String(Math.round(value * 100) / 100);
    }

    function setAll(key, text) {
        root.querySelectorAll('[data-goal-out="' + key + '"]').forEach(function (el) {
            el.textContent = text;
        });
    }

    function yearsLabel(years) {
        if (years === null || years <= 0) {
            return DASH;
        }
        return trim(years) + (years === 1 ? ' year' : ' years');
    }

    function selectedType() {
        var picked = form.querySelector('input[name="goal_type"]:checked');
        return picked ? picked.closest('.yk-goal__type') : null;
    }

    function update() {
        var tile = selectedType();
        var typeLabel = tile ? tile.getAttribute('data-goal-label') : 'Custom';
        var nameField = field('goal_name');
        var name = nameField && nameField.value.trim() !== '' ? nameField.value.trim() : typeLabel;

        var cost = num('current_cost');
        var years = num('years');
        var inflation = num('inflation_percent');

        setAll('name', name);
        setAll('type', typeLabel);
        setAll('cost', cost !== null && cost > 0 ? rupees(cost) : DASH);
        setAll('years', yearsLabel(years));

        if (years !== null && years > 0) {
            var target = new Date();
            target.setMonth(target.getMonth() + Math.round(years * 12));
            setAll('year', String(target.getFullYear() || thisYear));
        } else {
            setAll('year', DASH);
        }

        if (cost !== null && cost > 0 && years !== null && years > 0 && inflation !== null) {
            var future = cost * Math.pow(1 + inflation / 100, years);
            setAll('future', rupees(future));
            setAll('future-note', 'At ' + trim(inflation) + '% inflation. Final numbers come next.');
        } else {
            setAll('future', DASH);
            setAll('future-note', 'Add a cost and timeline to see this.');
        }

        var costWords = root.querySelector('[data-goal-out="cost-words"]');
        if (costWords) {
            var w = cost !== null && cost > 0 ? words(cost) : '';
            costWords.textContent = w ? '\u2248 ' + w + ' rupees today' : 'Enter the price as it is today.';
        }

        var icon = root.querySelector('[data-goal-icon]');
        var source = tile ? tile.querySelector('.yk-goal__type-icon svg') : null;
        if (icon && source) {
            icon.innerHTML = '';
            icon.appendChild(source.cloneNode(true));
        }

        root.querySelectorAll('[data-goal-years]').forEach(function (button) {
            var active = years !== null && parseFloat(button.getAttribute('data-goal-years')) === years;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
    }

    root.querySelectorAll('[data-goal-years]').forEach(function (button) {
        button.addEventListener('click', function () {
            var el = field('years');
            if (!el) {
                return;
            }
            el.value = button.getAttribute('data-goal-years');
            el.dispatchEvent(new Event('input', { bubbles: true }));
            el.focus({ preventScroll: true });
        });
    });

    form.addEventListener('input', update);
    form.addEventListener('change', update);
    update();
})();

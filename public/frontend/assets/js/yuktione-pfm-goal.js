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

        syncYearsUi(false);
    }

    /* ---- Years slider sync (Task 33) ---- */
    var yearsWrap = root.querySelector('[data-yk-goal-years]');
    var yearsInput = yearsWrap ? yearsWrap.querySelector('[data-goal-years-input]') : null;
    var yearsSlider = yearsWrap ? yearsWrap.querySelector('[data-goal-years-slider]') : null;
    var yearsDisplay = yearsWrap ? yearsWrap.querySelector('[data-goal-years-display]') : null;
    var YEARS_MIN = yearsWrap ? parseFloat(yearsWrap.getAttribute('data-years-min')) || 0.01 : 0.01;
    var YEARS_MAX = yearsWrap ? parseFloat(yearsWrap.getAttribute('data-years-max')) || 100 : 100;
    var YEARS_STEP = yearsWrap ? parseFloat(yearsWrap.getAttribute('data-years-step')) || 0.01 : 0.01;
    var YEARS_BUMP = yearsWrap ? parseFloat(yearsWrap.getAttribute('data-years-bump')) || 1 : 1;
    var YEARS_SLIDER_IDLE = 10;
    var syncingYears = false;

    function clampYears(value) {
        var n = Math.round(value * 100) / 100;
        if (n < YEARS_MIN) {
            return YEARS_MIN;
        }
        if (n > YEARS_MAX) {
            return YEARS_MAX;
        }
        return n;
    }

    function formatYears(value) {
        return trim(value);
    }

    function paintSlider(value) {
        if (!yearsSlider) {
            return;
        }
        var min = parseFloat(yearsSlider.min) || YEARS_MIN;
        var max = parseFloat(yearsSlider.max) || YEARS_MAX;
        var pct = ((value - min) / (max - min)) * 100;
        if (!isFinite(pct)) {
            pct = 0;
        }
        pct = Math.max(0, Math.min(100, pct));
        yearsSlider.style.setProperty('--yk-years-pct', pct + '%');
        yearsSlider.setAttribute('aria-valuenow', String(value));
    }

    function setYearsValue(value, fromSlider) {
        if (!yearsInput) {
            return;
        }
        var next = clampYears(value);
        syncingYears = true;
        yearsInput.value = formatYears(next);
        if (yearsSlider && (fromSlider || true)) {
            yearsSlider.value = String(next);
        }
        if (yearsDisplay) {
            yearsDisplay.textContent = formatYears(next);
        }
        paintSlider(next);
        syncingYears = false;
        yearsInput.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function syncYearsUi(writeEmptyAsIdle) {
        if (!yearsInput || !yearsSlider) {
            return;
        }
        var raw = yearsInput.value;
        if (raw === '' || !isFinite(parseFloat(raw))) {
            if (yearsDisplay) {
                yearsDisplay.textContent = DASH;
            }
            var idle = YEARS_SLIDER_IDLE;
            if (!syncingYears) {
                yearsSlider.value = String(idle);
            }
            paintSlider(idle);
            return;
        }
        var parsed = parseFloat(raw);
        var thumb = clampYears(parsed);
        if (!syncingYears) {
            yearsSlider.value = String(thumb);
        }
        if (yearsDisplay) {
            yearsDisplay.textContent = formatYears(parsed);
        }
        paintSlider(thumb);
        if (writeEmptyAsIdle) {
            /* no-op marker for callers */
        }
    }

    if (yearsSlider && yearsInput) {
        yearsSlider.addEventListener('input', function () {
            if (syncingYears) {
                return;
            }
            setYearsValue(parseFloat(yearsSlider.value), true);
        });

        yearsInput.addEventListener('input', function () {
            if (syncingYears) {
                return;
            }
            syncYearsUi(false);
        });
        yearsInput.addEventListener('change', function () {
            if (syncingYears) {
                return;
            }
            var raw = yearsInput.value;
            if (raw === '' || !isFinite(parseFloat(raw))) {
                syncYearsUi(false);
                return;
            }
            setYearsValue(parseFloat(raw), false);
        });

        var dec = yearsWrap.querySelector('[data-goal-years-dec]');
        var inc = yearsWrap.querySelector('[data-goal-years-inc]');
        if (dec) {
            dec.addEventListener('click', function () {
                var current = num('years');
                var base = current === null ? YEARS_BUMP : current;
                setYearsValue(base - YEARS_BUMP, false);
            });
        }
        if (inc) {
            inc.addEventListener('click', function () {
                var current = num('years');
                var base = current === null ? 0 : current;
                setYearsValue(base + YEARS_BUMP, false);
            });
        }
    }

    root.querySelectorAll('[data-goal-years]').forEach(function (button) {
        button.addEventListener('click', function () {
            var el = field('years');
            if (!el) {
                return;
            }
            setYearsValue(parseFloat(button.getAttribute('data-goal-years')), false);
            el.focus({ preventScroll: true });
        });
    });

    form.addEventListener('input', update);
    form.addEventListener('change', update);
    update();

    /* Task 35: after validation failure, bring the first invalid field into view */
    var firstError = form.querySelector('.yk-goal__error, .yk-goal__control.is-invalid input');
    var alertEl = root.querySelector('.yk-goal__alert');
    var scrollEl = firstError || alertEl;
    if (scrollEl) {
        var fieldWrap = firstError && firstError.closest ? firstError.closest('.yk-goal__field') : null;
        var focusTarget = (fieldWrap && fieldWrap.querySelector('input, select, textarea'))
            || form.querySelector('.yk-goal__control.is-invalid input')
            || null;
        if (focusTarget && typeof focusTarget.focus === 'function') {
            try {
                focusTarget.focus({ preventScroll: true });
            } catch (e) {
                /* ignore */
            }
        }
        if (typeof scrollEl.scrollIntoView === 'function') {
            scrollEl.scrollIntoView({ block: 'center', behavior: 'smooth' });
        }
    }
})();

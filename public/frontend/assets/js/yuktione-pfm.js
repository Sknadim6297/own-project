(function () {
    var DIRTY_KEY = 'pfm.unsaved.monthly-money-planner';
    var CONFIRM_MSG = 'Start a new calculation? Your unsaved changes will be discarded. Previously saved calculations will not be affected.';

    function markMoneyPlannerDirty() {
        try {
            sessionStorage.setItem(DIRTY_KEY, '1');
        } catch (e) {
            /* ignore */
        }
    }

    function clearMoneyPlannerDirty() {
        try {
            sessionStorage.removeItem(DIRTY_KEY);
        } catch (e) {
            /* ignore */
        }
    }

    window.ykPfmClearFreshDirty = clearMoneyPlannerDirty;

    window.ykPfmConfirmFreshStart = function (message) {
        try {
            if (sessionStorage.getItem(DIRTY_KEY) === '1') {
                if (!window.confirm(message || CONFIRM_MSG)) {
                    return false;
                }
            }
        } catch (e) {
            /* ignore */
        }

        clearMoneyPlannerDirty();

        return true;
    };

    function initDirtyTracking(form) {
        if (!form || !form.closest('[data-yk-pfm]')) {
            return;
        }

        // Track typed values even before a server-side draft exists (Task 26).
        form.addEventListener('input', markMoneyPlannerDirty);
        form.addEventListener('change', markMoneyPlannerDirty);
        form.addEventListener('submit', clearMoneyPlannerDirty);
    }

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

        var initial = true;

        function render() {
            steps.forEach(function (step, i) {
                var active = i === index;
                step.hidden = !active;
                if (active && !initial) {
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
        initial = false;
    }

    function formatInrWhole(value) {
        var n = String(Math.round(Math.abs(Number(value) || 0)));
        if (n.length > 3) {
            var head = n.slice(0, -3).replace(/\B(?=(\d{2})+(?!\d))/g, ',');
            n = head + ',' + n.slice(-3);
        }
        return '₹' + n;
    }

    function initDebtFinancedPreview(root) {
        if (!root) {
            return;
        }
        var purchase = root.querySelector('[data-yk-debt-purchase]');
        var down = root.querySelector('[data-yk-debt-down]');
        var valueEl = root.querySelector('[data-yk-debt-financed-value]');
        if (!purchase || !down || !valueEl) {
            return;
        }

        function refresh() {
            var p = parseFloat(purchase.value);
            var d = parseFloat(down.value);
            if (!isFinite(p)) {
                p = 0;
            }
            if (!isFinite(d) || down.value === '') {
                d = 0;
            }
            var financed = Math.max(0, p - d);
            valueEl.textContent = formatInrWhole(financed);
        }

        purchase.addEventListener('input', refresh);
        purchase.addEventListener('change', refresh);
        down.addEventListener('input', refresh);
        down.addEventListener('change', refresh);
        refresh();
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('form[data-pfm-form]').forEach(function (form) {
            initWizard(form);
            if (document.body.querySelector('[data-yk-pfm-editing], form[data-pfm-form][action*="money-planner"]')
                || (form.getAttribute('action') || '').indexOf('money-planner') !== -1) {
                initDirtyTracking(form);
            }
        });

        document.querySelectorAll('[data-yk-debt-down-payment]').forEach(initDebtFinancedPreview);
        document.querySelectorAll('[data-yk-si-amount]').forEach(initSavingsInvestmentAmount);

        // Successful fresh start / save landing clears client dirty flag.
        var status = document.querySelector('.yk-pfm__status');
        if (status && /Started a new calculation|Calculation saved|Changes saved/i.test(status.textContent || '')) {
            clearMoneyPlannerDirty();
        }

    });

    /**
     * Task 48 — Savings & Investment amount UI (presentation only).
     * Does not compute maturity/returns; contribution preview only.
     */
    function initSavingsInvestmentAmount(root) {
        if (!root || root.getAttribute('data-yk-si-amount-ready') === '1') {
            return;
        }
        root.setAttribute('data-yk-si-amount-ready', '1');

        var form = root.closest('form') || root.closest('[data-yk-pfm]');
        var initial = root.querySelector('[data-yk-si-initial]');
        var monthly = root.querySelector('[data-yk-si-monthly]');
        var years = root.querySelector('[data-yk-si-years]');
        var errorEl = root.querySelector('[data-yk-si-amount-error]');
        var previewInitial = root.querySelector('[data-yk-si-preview-initial]');
        var previewMonthly = root.querySelector('[data-yk-si-preview-monthly]');
        var previewMonths = root.querySelector('[data-yk-si-preview-months]');
        var previewTotal = root.querySelector('[data-yk-si-preview-total]');
        var previewFrequency = root.querySelector('[data-yk-si-preview-frequency]');
        var previewPerYear = root.querySelector('[data-yk-si-preview-per-year]');
        var previewCount = root.querySelector('[data-yk-si-preview-count]');
        var previewRecurring = root.querySelector('[data-yk-si-preview-recurring]');
        var contributionLabel = root.querySelector('[data-yk-si-contribution-label]');
        var contributionHelp = root.querySelector('[data-yk-si-contribution-help]');
        var monthlyAlias = root.querySelector('[data-yk-si-monthly-alias]');
        var frequencyGroup = root.querySelector('[data-yk-si-frequency-group]');
        var typeOptions = root.querySelectorAll('[data-yk-si-type-option]');
        var fieldInitial = root.querySelector('[data-yk-si-field="initial"]');
        var fieldMonthly = root.querySelector('[data-yk-si-field="monthly"]');
        var durationWrap = root.querySelector('[data-yk-si-duration]');
        var yearsSlider = durationWrap ? durationWrap.querySelector('[data-yk-si-years-slider]') : null;
        var yearsDisplay = durationWrap ? durationWrap.querySelector('[data-yk-si-years-display]') : null;
        var monthsLabel = durationWrap ? durationWrap.querySelector('[data-yk-si-months-label]') : null;
        var contribLabel = durationWrap ? durationWrap.querySelector('[data-yk-si-contrib-label]') : null;
        var timelineMid = durationWrap ? durationWrap.querySelector('[data-yk-si-timeline-mid]') : null;
        var timelineEnd = durationWrap ? durationWrap.querySelector('[data-yk-si-timeline-end]') : null;

        if (!initial || !monthly) {
            return;
        }

        var FREQ = {
            monthly: { label: 'Monthly', adverb: 'every month', period: 'month', perYear: 12, interval: 1, amountLabel: 'Monthly investment' },
            quarterly: { label: 'Quarterly', adverb: 'every three months', period: 'quarter', perYear: 4, interval: 3, amountLabel: 'Quarterly investment' },
            half_yearly: { label: 'Half-Yearly', adverb: 'every six months', period: 'half-year', perYear: 2, interval: 6, amountLabel: 'Half-yearly investment' },
            yearly: { label: 'Yearly', adverb: 'every year', period: 'year', perYear: 1, interval: 12, amountLabel: 'Yearly investment' }
        };

        var memory = {
            initial: initial.value !== '' ? initial.value : '0',
            monthly: monthly.value !== '' ? monthly.value : '0'
        };
        var showErrors = false;
        var YEARS_MIN = durationWrap ? parseFloat(durationWrap.getAttribute('data-years-min')) || 0.01 : 0.01;
        var YEARS_MAX = durationWrap ? parseFloat(durationWrap.getAttribute('data-years-max')) || 100 : 100;
        var YEARS_BUMP = durationWrap ? parseFloat(durationWrap.getAttribute('data-years-bump')) || 1 : 1;
        var YEARS_SLIDER_IDLE = 10;
        var syncingYears = false;

        function parseAmount(el) {
            var n = parseFloat(el && el.value);
            return isFinite(n) ? n : 0;
        }

        function formatInrMoney(value) {
            var n = Number(value);
            if (!isFinite(n)) {
                n = 0;
            }
            var sign = n < 0 ? '-' : '';
            var abs = Math.abs(n);
            var whole = String(Math.floor(abs + 1e-9));
            var frac = String(Math.round((abs - Math.floor(abs + 1e-9)) * 100));
            if (frac.length < 2) {
                frac = ('0' + frac).slice(-2);
            }
            if (whole.length > 3) {
                var head = whole.slice(0, -3).replace(/\B(?=(\d{2})+(?!\d))/g, ',');
                whole = head + ',' + whole.slice(-3);
            }
            return sign + '₹' + whole + '.' + frac;
        }

        function currentType() {
            var checked = root.querySelector('[data-yk-si-type-option]:checked');
            return checked ? checked.value : (root.getAttribute('data-yk-si-type') || 'combined');
        }

        function currentFrequency() {
            var checked = root.querySelector('[data-yk-si-frequency-option]:checked');
            var key = checked ? checked.value : (root.getAttribute('data-yk-si-frequency') || 'monthly');
            return FREQ[key] ? key : 'monthly';
        }

        function frequencyMeta(key) {
            return FREQ[key] || FREQ.monthly;
        }

        function syncContributionAlias() {
            if (monthlyAlias) {
                monthlyAlias.value = monthly.value === '' ? '0' : monthly.value;
            }
        }

        function paintFrequencyUi() {
            var key = currentFrequency();
            var meta = frequencyMeta(key);
            root.setAttribute('data-yk-si-frequency', key);
            root.querySelectorAll('.yk-sifreq__option').forEach(function (label) {
                var input = label.querySelector('[data-yk-si-frequency-option]');
                label.classList.toggle('is-active', !!(input && input.value === key));
            });
            if (contributionLabel) {
                contributionLabel.textContent = meta.amountLabel;
            }
            if (contributionHelp) {
                contributionHelp.textContent = 'Amount contributed once per ' + meta.period + ' (' + meta.adverb + '). Switching frequency does not convert this amount.';
            }
        }

        function setTypeActive(type) {
            root.setAttribute('data-yk-si-type', type);
            root.querySelectorAll('.yk-siamt__type').forEach(function (label) {
                var input = label.querySelector('[data-yk-si-type-option]');
                label.classList.toggle('is-active', !!(input && input.value === type));
            });
        }

        function applyType(type, fromUser) {
            setTypeActive(type);

            if (fromUser) {
                if (type !== 'monthly' && initial.value !== '' && parseAmount(initial) > 0) {
                    memory.initial = initial.value;
                }
                if (type !== 'lump' && monthly.value !== '' && parseAmount(monthly) > 0) {
                    memory.monthly = monthly.value;
                }
            }

            if (fieldInitial) {
                fieldInitial.hidden = type === 'monthly';
            }
            if (fieldMonthly) {
                fieldMonthly.hidden = type === 'lump';
            }
            if (frequencyGroup) {
                frequencyGroup.hidden = type === 'lump';
            }

            if (type === 'lump') {
                if (parseAmount(initial) <= 0 && memory.initial) {
                    initial.value = memory.initial;
                }
                monthly.value = '0';
                monthly.setAttribute('aria-required', 'false');
                initial.setAttribute('aria-required', 'true');
            } else if (type === 'monthly') {
                if (parseAmount(monthly) <= 0 && memory.monthly) {
                    monthly.value = memory.monthly;
                }
                initial.value = '0';
                initial.setAttribute('aria-required', 'false');
                monthly.setAttribute('aria-required', 'true');
            } else {
                if (parseAmount(initial) <= 0 && memory.initial) {
                    initial.value = memory.initial;
                }
                if (parseAmount(monthly) <= 0 && memory.monthly) {
                    monthly.value = memory.monthly;
                }
                initial.setAttribute('aria-required', 'true');
                monthly.setAttribute('aria-required', 'true');
            }

            syncContributionAlias();
            paintFrequencyUi();
            validateAmounts();
            refreshPreview();
        }

        function validateAmounts() {
            var type = currentType();
            var i = parseAmount(initial);
            var m = parseAmount(monthly);
            var message = '';

            if (initial.value !== '' && Number(initial.value) < 0) {
                message = 'Initial investment cannot be negative.';
            } else if (monthly.value !== '' && Number(monthly.value) < 0) {
                message = 'Contribution amount cannot be negative.';
            } else if (type === 'lump' && i <= 0) {
                message = 'Enter an initial investment greater than zero for a lump-sum plan.';
            } else if (type === 'monthly' && m <= 0) {
                message = 'Enter a contribution amount greater than zero for a recurring plan.';
            } else if (type === 'combined' && i <= 0 && m <= 0) {
                message = 'Enter an initial amount, a recurring contribution, or both.';
            }

            if (errorEl) {
                if (showErrors && message) {
                    errorEl.hidden = false;
                    errorEl.textContent = message;
                } else {
                    errorEl.hidden = true;
                    errorEl.textContent = '';
                }
            }

            return message === '';
        }

        function formatYearsLabel(value) {
            var n = Math.round(Number(value) * 100) / 100;
            if (!isFinite(n)) {
                return '—';
            }
            var text = String(n);
            if (text.indexOf('.') !== -1) {
                text = text.replace(/\.?0+$/, '');
            }
            return text;
        }

        function clampYears(value) {
            var n = Math.round(Number(value) * 100) / 100;
            if (!isFinite(n)) {
                return YEARS_MIN;
            }
            if (n < YEARS_MIN) {
                return YEARS_MIN;
            }
            if (n > YEARS_MAX) {
                return YEARS_MAX;
            }
            return n;
        }

        function effectiveMonths(yearsValue) {
            var y = parseFloat(yearsValue);
            if (!isFinite(y) || y <= 0) {
                return 0;
            }
            // Matches PfmMath::futureValuePlan — (int) round(years * 12)
            return Math.round(y * 12);
        }

        function paintYearsSlider(value) {
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
            yearsSlider.style.setProperty('--yk-si-years-pct', pct + '%');
            yearsSlider.setAttribute('aria-valuenow', String(value));
        }

        function refreshDurationUi() {
            if (!years) {
                return;
            }
            var raw = years.value;
            var parsed = parseFloat(raw);
            var hasValid = raw !== '' && isFinite(parsed) && parsed > 0;
            var months = hasValid ? effectiveMonths(parsed) : 0;
            var type = currentType();

            if (yearsDisplay) {
                yearsDisplay.textContent = hasValid ? formatYearsLabel(parsed) : '—';
            }
            if (monthsLabel) {
                monthsLabel.textContent = months + ' months';
            }
            if (contribLabel) {
                var freqKey = currentFrequency();
                var meta = frequencyMeta(freqKey);
                var count = months > 0 ? Math.floor(months / meta.interval) : 0;
                if (type === 'lump') {
                    contribLabel.textContent = ' · Lump sum — no recurring contribution periods';
                } else if (months > 0 && count === 0) {
                    contribLabel.textContent = ' · No complete ' + meta.label.toLowerCase() + ' contribution period in this duration';
                } else if (months > 0) {
                    contribLabel.textContent = ' · ' + count + ' ' + meta.label.toLowerCase() + ' contribution ' + (count === 1 ? 'period' : 'periods');
                } else {
                    contribLabel.textContent = ' · Contribution periods unavailable until a duration is entered';
                }
            }
            if (timelineMid) {
                timelineMid.textContent = hasValid
                    ? formatYearsLabel(parsed / 2) + ' yr'
                    : 'Midpoint';
            }
            if (timelineEnd) {
                if (hasValid) {
                    var unit = Math.abs(parsed - 1) < 1e-9 ? 'year' : 'years';
                    timelineEnd.textContent = 'After ' + formatYearsLabel(parsed) + ' ' + unit;
                } else {
                    timelineEnd.textContent = 'Select a duration';
                }
            }

            if (!syncingYears && yearsSlider) {
                var thumb = hasValid ? clampYears(parsed) : YEARS_SLIDER_IDLE;
                yearsSlider.value = String(thumb);
                paintYearsSlider(thumb);
            }

            root.querySelectorAll('[data-yk-si-years-preset]').forEach(function (btn) {
                var preset = parseFloat(btn.getAttribute('data-yk-si-years-preset'));
                btn.classList.toggle('is-active', hasValid && Math.abs(parsed - preset) < 1e-9);
            });
        }

        function setYearsValue(value) {
            if (!years) {
                return;
            }
            var next = clampYears(value);
            syncingYears = true;
            years.value = formatYearsLabel(next);
            if (yearsSlider) {
                yearsSlider.value = String(next);
                paintYearsSlider(next);
            }
            syncingYears = false;
            refreshDurationUi();
            refreshPreview();
        }

        function refreshPreview() {
            var type = currentType();
            var freqKey = currentFrequency();
            var meta = frequencyMeta(freqKey);
            var i = type === 'monthly' ? 0 : parseAmount(initial);
            var m = type === 'lump' ? 0 : parseAmount(monthly);
            var y = years ? parseFloat(years.value) : NaN;
            var months = isFinite(y) && y > 0 ? effectiveMonths(y) : 0;
            var count = type === 'lump' ? 0 : (months > 0 ? Math.floor(months / meta.interval) : 0);
            var recurring = m * count;
            var total = i + recurring;

            syncContributionAlias();
            paintFrequencyUi();

            if (previewInitial) {
                previewInitial.textContent = formatInrMoney(i);
            }
            if (previewMonthly) {
                previewMonthly.textContent = formatInrMoney(m);
            }
            if (previewFrequency) {
                previewFrequency.textContent = type === 'lump' ? 'Not used (lump sum)' : meta.label;
            }
            if (previewPerYear) {
                previewPerYear.textContent = String(type === 'lump' ? 0 : meta.perYear);
            }
            if (previewMonths) {
                previewMonths.textContent = months + ' months';
            }
            if (previewCount) {
                previewCount.textContent = String(count);
            }
            if (previewRecurring) {
                previewRecurring.textContent = formatInrMoney(recurring);
            }
            if (previewTotal) {
                previewTotal.textContent = formatInrMoney(total);
            }
            refreshDurationUi();
        }

        function syncMemoryFromInputs() {
            var type = currentType();
            if (type !== 'monthly') {
                memory.initial = initial.value !== '' ? initial.value : memory.initial;
            }
            if (type !== 'lump') {
                memory.monthly = monthly.value !== '' ? monthly.value : memory.monthly;
            }
        }

        typeOptions.forEach(function (option) {
            option.addEventListener('change', function () {
                showErrors = true;
                applyType(option.value, true);
            });
        });

        root.querySelectorAll('[data-yk-si-frequency-option]').forEach(function (option) {
            option.addEventListener('change', function () {
                // Do not convert the entered contribution amount when frequency changes.
                paintFrequencyUi();
                refreshPreview();
            });
        });

        root.querySelectorAll('[data-yk-si-preset]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                showErrors = true;
                var target = btn.getAttribute('data-yk-si-preset');
                var value = btn.getAttribute('data-yk-si-preset-value');
                if (!value) {
                    return;
                }
                if (target === 'initial') {
                    initial.value = value;
                    memory.initial = value;
                    if (currentType() === 'monthly') {
                        var combined = root.querySelector('[data-yk-si-type-option][value="combined"]');
                        if (combined) {
                            combined.checked = true;
                            applyType('combined', false);
                            return;
                        }
                    }
                } else if (target === 'monthly') {
                    monthly.value = value;
                    memory.monthly = value;
                    if (currentType() === 'lump') {
                        var combinedMonthly = root.querySelector('[data-yk-si-type-option][value="combined"]');
                        if (combinedMonthly) {
                            combinedMonthly.checked = true;
                            applyType('combined', false);
                            return;
                        }
                    }
                }
                validateAmounts();
                refreshPreview();
            });
        });

        ['input', 'change'].forEach(function (evt) {
            initial.addEventListener(evt, function () {
                showErrors = true;
                syncMemoryFromInputs();
                validateAmounts();
                refreshPreview();
            });
            monthly.addEventListener(evt, function () {
                showErrors = true;
                syncMemoryFromInputs();
                syncContributionAlias();
                validateAmounts();
                refreshPreview();
            });
        });

        if (years) {
            years.addEventListener('input', function () {
                if (syncingYears) {
                    return;
                }
                refreshPreview();
            });
            years.addEventListener('change', function () {
                if (syncingYears) {
                    return;
                }
                // Do not silently replace invalid typed values; keep the entered text for server validation.
                refreshPreview();
            });
        }

        if (yearsSlider && years) {
            yearsSlider.addEventListener('input', function () {
                if (syncingYears) {
                    return;
                }
                setYearsValue(parseFloat(yearsSlider.value));
            });
        }

        if (durationWrap) {
            var dec = durationWrap.querySelector('[data-yk-si-years-dec]');
            var inc = durationWrap.querySelector('[data-yk-si-years-inc]');
            if (dec) {
                dec.addEventListener('click', function () {
                    var current = parseFloat(years && years.value);
                    var base = isFinite(current) && current > 0 ? current : YEARS_BUMP;
                    setYearsValue(base - YEARS_BUMP);
                });
            }
            if (inc) {
                inc.addEventListener('click', function () {
                    var current = parseFloat(years && years.value);
                    var base = isFinite(current) && current > 0 ? current : 0;
                    setYearsValue(base + YEARS_BUMP);
                });
            }
            durationWrap.querySelectorAll('[data-yk-si-years-preset]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var preset = parseFloat(btn.getAttribute('data-yk-si-years-preset'));
                    if (!isFinite(preset)) {
                        return;
                    }
                    setYearsValue(preset);
                    if (years) {
                        years.focus({ preventScroll: true });
                    }
                });
            });
        }

        var formEl = root.closest('form') || form;
        if (formEl) {
            formEl.addEventListener('submit', function (event) {
                showErrors = true;
                var type = currentType();
                if (type === 'lump') {
                    monthly.value = '0';
                    // Keep a monthly frequency posted for lump sum; it does not affect maturity.
                    var monthlyFreq = root.querySelector('[data-yk-si-frequency-option][value="monthly"]');
                    if (monthlyFreq) {
                        monthlyFreq.checked = true;
                    }
                } else if (type === 'monthly') {
                    initial.value = '0';
                }
                syncContributionAlias();
                if (!validateAmounts()) {
                    event.preventDefault();
                    if (errorEl && !errorEl.hidden) {
                        errorEl.focus?.();
                        errorEl.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                    }
                }
            });
        }

        applyType(currentType(), false);
        refreshDurationUi();
    }
})();

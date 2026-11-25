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
        var typeOptions = root.querySelectorAll('[data-yk-si-type-option]');
        var fieldInitial = root.querySelector('[data-yk-si-field="initial"]');
        var fieldMonthly = root.querySelector('[data-yk-si-field="monthly"]');

        if (!initial || !monthly) {
            return;
        }

        var memory = {
            initial: initial.value !== '' ? initial.value : '0',
            monthly: monthly.value !== '' ? monthly.value : '0'
        };
        var showErrors = false;

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
                message = 'Monthly investment cannot be negative.';
            } else if (type === 'lump' && i <= 0) {
                message = 'Enter an initial investment greater than zero for a lump-sum plan.';
            } else if (type === 'monthly' && m <= 0) {
                message = 'Enter a monthly investment greater than zero for a monthly plan.';
            } else if (type === 'combined' && i <= 0 && m <= 0) {
                message = 'Enter an initial amount, a monthly contribution, or both.';
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

        function refreshPreview() {
            var type = currentType();
            var i = type === 'monthly' ? 0 : parseAmount(initial);
            var m = type === 'lump' ? 0 : parseAmount(monthly);
            var y = years ? parseFloat(years.value) : NaN;
            var months = isFinite(y) && y > 0 ? Math.round(y * 12) : 0;
            var total = i + (m * months);

            if (previewInitial) {
                previewInitial.textContent = formatInrMoney(i);
            }
            if (previewMonthly) {
                previewMonthly.textContent = formatInrMoney(m);
            }
            if (previewMonths) {
                previewMonths.textContent = String(months);
            }
            if (previewTotal) {
                previewTotal.textContent = formatInrMoney(total);
            }
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
                validateAmounts();
                refreshPreview();
            });
            if (years) {
                years.addEventListener(evt, refreshPreview);
            }
        });

        var formEl = root.closest('form');
        if (formEl) {
            formEl.addEventListener('submit', function (event) {
                showErrors = true;
                var type = currentType();
                if (type === 'lump') {
                    monthly.value = '0';
                } else if (type === 'monthly') {
                    initial.value = '0';
                }
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
    }
})();

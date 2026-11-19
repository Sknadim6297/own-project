/**
 * BMI & BMR Calculator interactions (native JS).
 * - Unit toggle: only active unit inputs are enabled/submitted.
 * - Live preview: client-side estimate while typing (display only; the server calculates the real result).
 */
(function () {
    'use strict';

    function setPanel(panel, active) {
        if (!panel) return;
        if (active) {
            panel.hidden = false;
            panel.removeAttribute('aria-hidden');
        } else {
            panel.hidden = true;
            panel.setAttribute('aria-hidden', 'true');
        }
        panel.querySelectorAll('input, select, textarea').forEach(function (field) {
            field.disabled = !active;
        });
    }

    function num(field) {
        if (!field || field.disabled || field.value === '') return NaN;
        var n = parseFloat(field.value);
        return isFinite(n) ? n : NaN;
    }

    function initForm() {
        var form = document.querySelector('[data-bmi-form]');
        if (!form) return;

        var preview = document.querySelector('[data-bmi-preview]');
        var needle = document.querySelector('[data-bmi-needle]');
        var liveBmi = document.querySelector('[data-bmi-live-bmi]');
        var liveLabel = document.querySelector('[data-bmi-live-label]');
        var kpiBmi = document.querySelector('[data-bmi-kpi-bmi]');
        var kpiBmr = document.querySelector('[data-bmi-kpi-bmr]');
        var kpiTdee = document.querySelector('[data-bmi-kpi-tdee]');

        var syncUnits = function () {
            var hu = form.querySelector('input[name="height_unit"]:checked');
            var wu = form.querySelector('input[name="weight_unit"]:checked');
            var heightIsCm = !hu || hu.value === 'cm';
            var weightIsKg = !wu || wu.value === 'kg';

            setPanel(form.querySelector('[data-bmi-height-cm]'), heightIsCm);
            setPanel(form.querySelector('[data-bmi-height-ft]'), !heightIsCm);
            setPanel(form.querySelector('[data-bmi-weight-kg]'), weightIsKg);
            setPanel(form.querySelector('[data-bmi-weight-lb]'), !weightIsKg);

            form.setAttribute('data-height-mode', heightIsCm ? 'cm' : 'ft_in');
            form.setAttribute('data-weight-mode', weightIsKg ? 'kg' : 'lb');
        };

        var readKg = function () {
            var wu = form.querySelector('input[name="weight_unit"]:checked');
            if (wu && wu.value === 'lb') {
                var lb = num(form.querySelector('[name="weight_lb"]'));
                return isNaN(lb) ? NaN : lb * 0.45359237;
            }
            return num(form.querySelector('[name="weight_kg"]'));
        };

        var readCm = function () {
            var hu = form.querySelector('input[name="height_unit"]:checked');
            if (hu && hu.value === 'ft_in') {
                var ft = num(form.querySelector('[name="height_ft"]'));
                var inch = num(form.querySelector('[name="height_in"]'));
                if (isNaN(ft)) return NaN;
                return (ft * 12 + (isNaN(inch) ? 0 : inch)) * 2.54;
            }
            return num(form.querySelector('[name="height_cm"]'));
        };

        var fmt = function (n) {
            return Math.round(n).toLocaleString('en-US');
        };

        var setState = function (state) {
            if (preview) preview.setAttribute('data-state', state);
        };

        var update = function () {
            var kg = readKg();
            var cm = readCm();
            var validBody = !isNaN(kg) && !isNaN(cm) && kg >= 10 && kg <= 400 && cm >= 50 && cm <= 250;

            if (!validBody) {
                setState('empty');
                if (needle) needle.style.transform = 'rotate(-90deg)';
                if (liveBmi) liveBmi.textContent = '—';
                if (liveLabel) liveLabel.textContent = 'Enter height & weight';
                if (kpiBmi) kpiBmi.textContent = '—';
                if (kpiBmr) kpiBmr.textContent = '—';
                if (kpiTdee) kpiTdee.textContent = '—';
                return;
            }

            var m = cm / 100;
            var bmi = kg / (m * m);
            var age = num(form.querySelector('[name="age"]'));
            var adult = !isNaN(age) && age >= 18;

            var state = 'healthy';
            var label = 'Normal';
            if (bmi < 18.5) { state = 'under'; label = 'Underweight'; }
            else if (bmi >= 30) { state = 'obese'; label = 'Obesity'; }
            else if (bmi >= 25) { state = 'over'; label = 'Overweight'; }
            if (!adult) label = isNaN(age) ? label : 'Adult categories apply from 18+';
            if (!adult && !isNaN(age)) state = 'neutral';

            var t = Math.max(0, Math.min(1, (bmi - 10) / 30));
            if (needle) needle.style.transform = 'rotate(' + (-90 + t * 180).toFixed(1) + 'deg)';
            if (liveBmi) liveBmi.textContent = bmi.toFixed(1);
            if (liveLabel) liveLabel.textContent = label;
            if (kpiBmi) kpiBmi.textContent = bmi.toFixed(1);
            setState(state);

            var sex = form.querySelector('input[name="sex"]:checked');
            var excluded = form.querySelector('[name="excluded_population"]');
            var bmr = NaN;
            if (adult && sex && (sex.value === 'male' || sex.value === 'female') && !(excluded && excluded.checked)) {
                bmr = 10 * kg + 6.25 * cm - 5 * age + (sex.value === 'male' ? 5 : -161);
            }
            if (kpiBmr) kpiBmr.textContent = isNaN(bmr) ? '—' : fmt(bmr);

            var act = form.querySelector('input[name="activity"]:checked');
            var factor = act ? parseFloat(act.getAttribute('data-factor')) : NaN;
            if (kpiTdee) kpiTdee.textContent = (isNaN(bmr) || isNaN(factor)) ? '—' : fmt(bmr * factor);
        };

        form.addEventListener('change', function (event) {
            var target = event.target;
            if (!target) return;
            if (target.matches('[data-bmi-height-unit], [data-bmi-weight-unit]')) {
                syncUnits();
            }
            update();
        });
        form.addEventListener('input', update);

        syncUnits();
        update();
    }

    initForm();
})();

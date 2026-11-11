/**
 * Phase 4D — BMI & BMR Calculator interactions (native JS).
 * Unit toggle: only active unit inputs are enabled/submitted.
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

    function initUnitToggle() {
        var form = document.querySelector('[data-bmi-form]');
        if (!form) return;

        var sync = function () {
            var hu = form.querySelector('input[name="height_unit"]:checked');
            var wu = form.querySelector('input[name="weight_unit"]:checked');
            var cm = form.querySelector('[data-bmi-height-cm]');
            var ft = form.querySelector('[data-bmi-height-ft]');
            var kg = form.querySelector('[data-bmi-weight-kg]');
            var lb = form.querySelector('[data-bmi-weight-lb]');

            var heightIsCm = !hu || hu.value === 'cm';
            var weightIsKg = !wu || wu.value === 'kg';

            setPanel(cm, heightIsCm);
            setPanel(ft, !heightIsCm);
            setPanel(kg, weightIsKg);
            setPanel(lb, !weightIsKg);

            form.setAttribute('data-height-mode', heightIsCm ? 'cm' : 'ft_in');
            form.setAttribute('data-weight-mode', weightIsKg ? 'kg' : 'lb');
        };

        form.addEventListener('change', function (event) {
            var target = event.target;
            if (!target) return;
            if (target.matches('[data-bmi-height-unit], [data-bmi-weight-unit]')) {
                sync();
            }
        });

        sync();
    }

    initUnitToggle();
})();

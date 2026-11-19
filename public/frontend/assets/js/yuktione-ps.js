/**
 * Parenting & Spirituality tools (native JS).
 * - Step wizard (progressive enhancement: without JS every step is visible and the form still submits).
 * - Inline validation, Enter-to-continue, character counter.
 * - Mindfulness timer with a progress ring (timestamp based, so it stays accurate in background tabs).
 */
(function () {
  'use strict';

  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ------------------------------------------------------------------ Wizard */

  function clearErrors(scope) {
    scope.querySelectorAll('.has-error').forEach(function (el) { el.classList.remove('has-error'); });
    scope.querySelectorAll('.yk-ps__field-error').forEach(function (el) { el.remove(); });
    scope.querySelectorAll('[aria-invalid="true"]').forEach(function (el) { el.removeAttribute('aria-invalid'); });
  }

  function showError(container, message, control) {
    if (!container || container.classList.contains('has-error')) return;
    container.classList.add('has-error');
    var note = document.createElement('p');
    note.className = 'yk-ps__field-error';
    note.setAttribute('role', 'alert');
    note.textContent = message;
    container.appendChild(note);
    if (control) control.setAttribute('aria-invalid', 'true');
  }

  /** Returns the first invalid control in the step (and marks errors), or null. */
  function validateStep(step) {
    clearErrors(step);
    var firstInvalid = null;
    var seenRadios = {};

    step.querySelectorAll('input, select, textarea').forEach(function (el) {
      if (el.disabled || el.type === 'hidden' || !el.hasAttribute('required')) return;

      if (el.type === 'radio') {
        if (seenRadios[el.name]) return;
        seenRadios[el.name] = true;
        var group = step.querySelectorAll('input[type="radio"][name="' + el.name + '"]');
        var checked = Array.prototype.some.call(group, function (r) { return r.checked; });
        if (!checked) {
          showError(el.closest('[data-ps-required-group], fieldset'), 'Please choose an option to continue.', el);
          firstInvalid = firstInvalid || el;
        }
        return;
      }

      if (!el.checkValidity()) {
        var message = 'Please fill this in.';
        if (el.validity.tooShort) message = 'Please write at least ' + el.minLength + ' characters.';
        else if (el.validity.rangeUnderflow || el.validity.rangeOverflow) message = 'Enter a value between ' + el.min + ' and ' + el.max + '.';
        showError(el.closest('.yk-ps__field') || el.parentElement, message, el);
        firstInvalid = firstInvalid || el;
      }
    });

    return firstInvalid;
  }

  function initWizard(form) {
    var steps = Array.prototype.slice.call(form.querySelectorAll('[data-ps-step]'));
    if (!steps.length) return;

    var labels = Array.prototype.slice.call(form.querySelectorAll('[data-ps-step-label]'));
    var prevBtn = form.querySelector('[data-ps-prev]');
    var nextBtn = form.querySelector('[data-ps-next]');
    var submitBtn = form.querySelector('[data-ps-submit]');
    var bar = form.querySelector('[data-ps-progress]');
    var panel = form.closest('.yk-ps__panel') || form;
    var last = steps.length - 1;
    var index = 0;

    form.classList.add('is-wizard');

    function render(moved) {
      steps.forEach(function (step, i) {
        step.classList.toggle('is-active', i === index);
      });
      labels.forEach(function (label, i) {
        label.classList.toggle('is-active', i === index);
        label.classList.toggle('is-done', i < index);
        if (i === index) label.setAttribute('aria-current', 'step');
        else label.removeAttribute('aria-current');
      });
      if (bar) bar.style.width = ((index + 1) / steps.length * 100) + '%';
      if (prevBtn) prevBtn.hidden = index === 0;
      if (nextBtn) nextBtn.hidden = index >= last;
      if (submitBtn) submitBtn.hidden = index < last;

      if (moved) {
        var top = panel.getBoundingClientRect().top + window.pageYOffset - 90;
        window.scrollTo({ top: Math.max(0, top), behavior: reduceMotion ? 'auto' : 'smooth' });
      }
    }

    function focusInvalid(control) {
      if (!control) return;
      var target = control.type === 'radio' ? (control.closest('fieldset') || control) : control;
      target.scrollIntoView({ block: 'center', behavior: reduceMotion ? 'auto' : 'smooth' });
      try { control.focus({ preventScroll: true }); } catch (e) { /* ignore */ }
    }

    function goNext() {
      var invalid = validateStep(steps[index]);
      if (invalid) { focusInvalid(invalid); return; }
      index = Math.min(last, index + 1);
      render(true);
    }

    if (prevBtn) {
      prevBtn.addEventListener('click', function () {
        index = Math.max(0, index - 1);
        render(true);
      });
    }
    if (nextBtn) nextBtn.addEventListener('click', goNext);

    // Enter inside a text/number/time field moves forward instead of submitting early.
    form.addEventListener('keydown', function (event) {
      if (event.key !== 'Enter' || index >= last) return;
      var tag = event.target && event.target.tagName;
      if (tag === 'TEXTAREA' || tag === 'BUTTON' || tag === 'A') return;
      event.preventDefault();
      goNext();
    });

    // Clear a field's error as soon as the user fixes it.
    form.addEventListener('input', function (event) {
      var holder = event.target.closest && event.target.closest('.has-error');
      if (!holder) return;
      holder.classList.remove('has-error');
      var note = holder.querySelector('.yk-ps__field-error');
      if (note) note.remove();
      event.target.removeAttribute('aria-invalid');
    });
    form.addEventListener('change', function (event) {
      var holder = event.target.closest && event.target.closest('.has-error');
      if (!holder) return;
      holder.classList.remove('has-error');
      var note = holder.querySelector('.yk-ps__field-error');
      if (note) note.remove();
    });

    // On submit validate every step; jump to the first one that needs attention.
    form.addEventListener('submit', function (event) {
      for (var i = 0; i < steps.length; i += 1) {
        var invalid = validateStep(steps[i]);
        if (invalid) {
          event.preventDefault();
          index = i;
          render(true);
          setTimeout(function () { focusInvalid(invalid); }, reduceMotion ? 0 : 250);
          return;
        }
      }
    });

    render(false);
  }

  function initPlainForm(form) {
    // Single-page forms (no wizard): same inline validation on submit.
    form.addEventListener('submit', function (event) {
      var invalid = validateStep(form);
      if (invalid) {
        event.preventDefault();
        invalid.scrollIntoView({ block: 'center', behavior: reduceMotion ? 'auto' : 'smooth' });
        try { invalid.focus({ preventScroll: true }); } catch (e) { /* ignore */ }
      }
    });
  }

  function initCounters() {
    document.querySelectorAll('[data-ps-count-for]').forEach(function (out) {
      var field = document.getElementById(out.getAttribute('data-ps-count-for'));
      if (!field) return;
      var update = function () { out.textContent = String(field.value.length); };
      field.addEventListener('input', update);
      update();
    });
  }

  /* ------------------------------------------------------------------- Timer */

  function format(seconds) {
    var m = Math.floor(seconds / 60);
    var s = seconds % 60;
    return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
  }

  function initTimer(root) {
    var total = parseInt(root.getAttribute('data-seconds') || '180', 10);
    var remainingMs = total * 1000;
    var endAt = 0;
    var timerId = null;

    var display = root.querySelector('[data-ps-timer-display]');
    var ring = root.querySelector('[data-ps-timer-ring]');
    var startBtn = root.querySelector('[data-ps-timer-start]');
    var pauseBtn = root.querySelector('[data-ps-timer-pause]');
    var resumeBtn = root.querySelector('[data-ps-timer-resume]');
    var finishBtn = root.querySelector('[data-ps-timer-finish]');
    var status = root.querySelector('[data-ps-timer-status]');
    var circumference = ring ? parseFloat(ring.getAttribute('stroke-dasharray')) : 0;

    function render() {
      var seconds = Math.max(0, Math.ceil(remainingMs / 1000));
      if (display) display.textContent = format(seconds);
      if (ring && circumference) {
        var elapsed = 1 - Math.max(0, remainingMs) / (total * 1000);
        ring.setAttribute('stroke-dashoffset', String(circumference * (1 - elapsed)));
      }
    }

    function stop() {
      if (timerId) { clearInterval(timerId); timerId = null; }
    }

    function complete() {
      stop();
      remainingMs = 0;
      render();
      root.classList.add('is-complete');
      root.classList.remove('is-running');
      if (startBtn) startBtn.hidden = true;
      if (pauseBtn) pauseBtn.hidden = true;
      if (resumeBtn) resumeBtn.hidden = true;
      if (finishBtn) finishBtn.hidden = true;
      if (status) status.hidden = false;
    }

    function tick() {
      remainingMs = endAt - Date.now();
      if (remainingMs <= 0) { complete(); return; }
      render();
    }

    function run() {
      if (timerId || remainingMs <= 0) return;
      endAt = Date.now() + remainingMs;
      root.classList.add('is-running');
      timerId = setInterval(tick, 250);
    }

    if (startBtn) {
      startBtn.addEventListener('click', function () {
        startBtn.hidden = true;
        if (pauseBtn) pauseBtn.hidden = false;
        if (resumeBtn) resumeBtn.hidden = true;
        run();
      });
    }

    if (pauseBtn) {
      pauseBtn.addEventListener('click', function () {
        remainingMs = Math.max(0, endAt - Date.now());
        stop();
        root.classList.remove('is-running');
        pauseBtn.hidden = true;
        if (resumeBtn) resumeBtn.hidden = false;
        render();
      });
    }

    if (resumeBtn) {
      resumeBtn.addEventListener('click', function () {
        resumeBtn.hidden = true;
        if (pauseBtn) pauseBtn.hidden = false;
        run();
      });
    }

    if (finishBtn) finishBtn.addEventListener('click', complete);

    render();
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('form[data-ps-form]').forEach(function (form) {
      if (form.getAttribute('data-ps-wizard') === '1') initWizard(form);
      else initPlainForm(form);
    });
    initCounters();
    document.querySelectorAll('[data-ps-timer]').forEach(initTimer);
  });
})();

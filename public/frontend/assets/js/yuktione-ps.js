(function () {
  function format(seconds) {
    var m = Math.floor(seconds / 60);
    var s = seconds % 60;
    return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
  }

  function initTimer(root) {
    var total = parseInt(root.getAttribute('data-seconds') || '180', 10);
    var remaining = total;
    var elapsed = 0;
    var timerId = null;
    var display = root.querySelector('[data-ps-timer-display]');
    var startBtn = root.querySelector('[data-ps-timer-start]');
    var pauseBtn = root.querySelector('[data-ps-timer-pause]');
    var resumeBtn = root.querySelector('[data-ps-timer-resume]');
    var finishBtn = root.querySelector('[data-ps-timer-finish]');
    var status = root.querySelector('[data-ps-timer-status]');

    function render() {
      if (display) display.textContent = format(remaining);
    }

    function stop() {
      if (timerId) {
        clearInterval(timerId);
        timerId = null;
      }
    }

    function complete() {
      stop();
      remaining = 0;
      elapsed = total;
      render();
      if (startBtn) startBtn.hidden = true;
      if (pauseBtn) pauseBtn.hidden = true;
      if (resumeBtn) resumeBtn.hidden = true;
      if (status) status.hidden = false;
    }

    function tick() {
      if (remaining <= 0) {
        complete();
        return;
      }
      remaining -= 1;
      elapsed += 1;
      render();
      if (remaining <= 0) complete();
    }

    if (startBtn) {
      startBtn.addEventListener('click', function () {
        if (timerId) return;
        startBtn.hidden = true;
        if (pauseBtn) pauseBtn.hidden = false;
        if (resumeBtn) resumeBtn.hidden = true;
        timerId = setInterval(tick, 1000);
      });
    }

    if (pauseBtn) {
      pauseBtn.addEventListener('click', function () {
        stop();
        pauseBtn.hidden = true;
        if (resumeBtn) resumeBtn.hidden = false;
      });
    }

    if (resumeBtn) {
      resumeBtn.addEventListener('click', function () {
        if (timerId || remaining <= 0) return;
        resumeBtn.hidden = true;
        if (pauseBtn) pauseBtn.hidden = false;
        timerId = setInterval(tick, 1000);
      });
    }

    if (finishBtn) {
      finishBtn.addEventListener('click', function () {
        complete();
      });
    }

    render();
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-ps-timer]').forEach(initTimer);
  });
})();

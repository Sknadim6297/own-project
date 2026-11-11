(function () {
  'use strict';

  var root = document.querySelector('[data-yk-rewards]');
  if (!root) return;

  var live = root.querySelector('[data-yk-live]');
  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function announce(message, isError) {
    if (!live) return;
    live.textContent = message || '';
    live.dataset.tone = isError ? 'error' : 'ok';
  }

  function copyText(text, preferredInput) {
    if (!text) return Promise.reject(new Error('Nothing to copy'));

    function legacyCopy() {
      return new Promise(function (resolve, reject) {
        if (preferredInput && typeof preferredInput.select === 'function') {
          preferredInput.focus();
          preferredInput.select();
          preferredInput.setSelectionRange(0, text.length);
          try {
            if (document.execCommand('copy')) {
              resolve();
              return;
            }
          } catch (err) {
            /* fall through to textarea */
          }
        }
        var area = document.createElement('textarea');
        area.value = text;
        area.setAttribute('readonly', '');
        area.style.position = 'fixed';
        area.style.left = '-9999px';
        document.body.appendChild(area);
        area.focus();
        area.select();
        try {
          if (!document.execCommand('copy')) {
            throw new Error('Copy command failed');
          }
          resolve();
        } catch (err) {
          reject(err);
        } finally {
          document.body.removeChild(area);
        }
      });
    }

    if (navigator.clipboard && navigator.clipboard.writeText && window.isSecureContext) {
      return navigator.clipboard.writeText(text).catch(function () {
        return legacyCopy();
      });
    }
    return legacyCopy();
  }

  function whatsappUrl(url, text) {
    var msg = (text || 'Join me on YuktiOne') + ' ' + url;
    return 'https://wa.me/?text=' + encodeURIComponent(msg);
  }

  root.addEventListener('click', function (event) {
    var btn = event.target.closest('[data-yk-action]');
    if (!btn || !root.contains(btn)) return;

    if (btn.classList.contains('is-disabled') || btn.getAttribute('aria-disabled') === 'true' || btn.disabled) {
      return;
    }

    var action = btn.getAttribute('data-yk-action');
    var url = btn.getAttribute('data-yk-url') || '';
    var title = btn.getAttribute('data-yk-title') || 'YuktiOne';
    var text = btn.getAttribute('data-yk-text') || 'Join me on YuktiOne';

    if (action === 'copy') {
      event.preventDefault();
      var group = btn.closest('[data-yk-share-group], .yk-rewards__panel');
      var preferredInput = group ? group.querySelector('[data-yk-url-field]') : null;
      copyText(url, preferredInput)
        .then(function () { announce('Referral link copied.'); })
        .catch(function () { announce('Could not copy the link. Please copy it manually.', true); });
      return;
    }

    if (action === 'whatsapp') {
      event.preventDefault();
      if (!url) {
        announce('Choose a valid share target first.', true);
        return;
      }
      window.open(whatsappUrl(url, text), '_blank', 'noopener');
      announce('Opening WhatsApp share…');
      return;
    }

    if (action === 'native-share') {
      event.preventDefault();
      if (!url) {
        announce('Choose a valid share target first.', true);
        return;
      }
      if (!navigator.share) {
        copyText(url)
          .then(function () { announce('Sharing is unavailable here — link copied instead.'); })
          .catch(function () { announce('Sharing is unavailable on this device.', true); });
        return;
      }
      navigator.share({ title: title, text: text, url: url })
        .then(function () { announce('Share sheet opened.'); })
        .catch(function () { announce('Share cancelled.'); });
    }
  });

  root.querySelectorAll('[data-yk-select-target]').forEach(function (select) {
    select.addEventListener('change', function () {
      var option = select.options[select.selectedIndex];
      var url = option ? option.getAttribute('data-url') || '' : '';
      var group = select.closest('[data-yk-share-group]');
      if (!group) return;
      group.querySelectorAll('[data-yk-action]').forEach(function (btn) {
        btn.setAttribute('data-yk-url', url);
        if (!url) {
          btn.setAttribute('aria-disabled', 'true');
          btn.classList.add('is-disabled');
        } else {
          btn.removeAttribute('aria-disabled');
          btn.classList.remove('is-disabled');
        }
      });
      var input = group.querySelector('[data-yk-url-field]');
      if (input) input.value = url;
      announce(url ? 'Share target ready.' : 'Select a target to share.');
    });
  });

  if (!reduced) {
    root.querySelectorAll('[data-yk-progress]').forEach(function (bar) {
      var value = Number(bar.getAttribute('data-yk-progress') || 0);
      var fill = bar.querySelector('span');
      if (!fill) return;
      requestAnimationFrame(function () {
        fill.style.width = Math.max(0, Math.min(100, value)) + '%';
      });
    });
  } else {
    root.querySelectorAll('[data-yk-progress]').forEach(function (bar) {
      var value = Number(bar.getAttribute('data-yk-progress') || 0);
      var fill = bar.querySelector('span');
      if (fill) fill.style.width = Math.max(0, Math.min(100, value)) + '%';
    });
  }
})();

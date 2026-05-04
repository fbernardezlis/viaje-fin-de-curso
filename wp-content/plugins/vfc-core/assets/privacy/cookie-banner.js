(function () {
  'use strict';

  var cfg = window.VFC_PRIVACY || {};
  var root = cfg.restUrl || '';
  var nonce = cfg.nonce || '';

  function postConsent(body) {
    return fetch(root, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': nonce,
      },
      body: JSON.stringify(body),
    });
  }

  function hideBar() {
    var bar = document.getElementById('vfc-cookie-banner');
    if (bar) {
      bar.setAttribute('hidden', 'hidden');
      bar.setAttribute('aria-hidden', 'true');
    }
    document.documentElement.classList.remove('vfc-cookie-banner-open');
  }

  function showPrefs(open) {
    var panel = document.getElementById('vfc-cookie-prefs');
    if (!panel) {
      return;
    }
    if (open) {
      panel.removeAttribute('hidden');
    } else {
      panel.setAttribute('hidden', 'hidden');
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    var bar = document.getElementById('vfc-cookie-banner');
    if (!bar) {
      return;
    }

    document.documentElement.classList.add('vfc-cookie-banner-open');

    bar.querySelectorAll('[data-vfc-consent]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var mode = btn.getAttribute('data-vfc-consent');
        var functional = mode === 'functional' || mode === 'all';
        var analytics = mode === 'all';
        if (mode === 'necessary') {
          functional = false;
          analytics = false;
        }
        postConsent({ functional: functional, analytics: analytics, revoke: false }).then(function (r) {
          if (r.ok) {
            hideBar();
            window.location.reload();
          }
        });
      });
    });

    var btnPrefs = document.getElementById('vfc-cookie-open-prefs');
    if (btnPrefs) {
      btnPrefs.addEventListener('click', function () {
        showPrefs(true);
      });
    }
    var btnClosePrefs = document.getElementById('vfc-cookie-close-prefs');
    if (btnClosePrefs) {
      btnClosePrefs.addEventListener('click', function () {
        showPrefs(false);
      });
    }

    var btnSavePrefs = document.getElementById('vfc-cookie-save-prefs');
    if (btnSavePrefs) {
      btnSavePrefs.addEventListener('click', function () {
        var f = document.getElementById('vfc-cookie-opt-functional');
        var a = document.getElementById('vfc-cookie-opt-analytics');
        postConsent({
          functional: !!(f && f.checked),
          analytics: !!(a && a.checked),
          revoke: false,
        }).then(function (r) {
          if (r.ok) {
            hideBar();
            window.location.reload();
          }
        });
      });
    }

    var btnRevoke = document.getElementById('vfc-cookie-revoke');
    if (btnRevoke) {
      btnRevoke.addEventListener('click', function () {
        postConsent({ revoke: true }).then(function (r) {
          if (r.ok) {
            window.location.reload();
          }
        });
      });
    }
  });
})();

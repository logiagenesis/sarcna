/* ==========================================================================
   SARCNA 2027 Convention — progressive enhancement
   Every page works without this file; it only adds polish and tracking.
   ========================================================================== */
(function () {
  'use strict';

  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ------------------------------------------------------------ analytics */

  function track(event, params) {
    if (typeof window.gtag === 'function') {
      window.gtag('event', event, params || {});
    }
  }
  window.sarcnaTrack = track;

  /* -------------------------------------------------------------- header */

  var header = document.querySelector('.site-header');
  var toggle = document.querySelector('.nav-toggle');
  var mobileNav = document.getElementById('mobile-nav');

  if (header) {
    var onScroll = function () {
      header.classList.toggle('is-scrolled', window.scrollY > 12);
    };
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
  }

  if (toggle && mobileNav) {
    toggle.addEventListener('click', function () {
      var open = mobileNav.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      document.body.style.overflow = open ? 'hidden' : '';
    });

    mobileNav.addEventListener('click', function (event) {
      if (event.target.tagName === 'A') {
        mobileNav.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
      }
    });
  }

  /* ----------------------------------------------------------- countdown */

  document.querySelectorAll('[data-countdown]').forEach(function (element) {
    var target = new Date(element.getAttribute('data-countdown').replace(' ', 'T')).getTime();

    if (isNaN(target)) { return; }

    var fields = {
      days: element.querySelector('[data-unit="days"]'),
      hours: element.querySelector('[data-unit="hours"]'),
      minutes: element.querySelector('[data-unit="minutes"]'),
      seconds: element.querySelector('[data-unit="seconds"]')
    };

    var pad = function (value) { return value < 10 ? '0' + value : String(value); };

    var tick = function () {
      var remaining = target - Date.now();

      if (remaining <= 0) {
        element.setAttribute('data-finished', 'true');
        Object.keys(fields).forEach(function (key) { if (fields[key]) { fields[key].textContent = '00'; } });
        return;
      }

      var seconds = Math.floor(remaining / 1000);
      if (fields.days) { fields.days.textContent = pad(Math.floor(seconds / 86400)); }
      if (fields.hours) { fields.hours.textContent = pad(Math.floor((seconds % 86400) / 3600)); }
      if (fields.minutes) { fields.minutes.textContent = pad(Math.floor((seconds % 3600) / 60)); }
      if (fields.seconds) { fields.seconds.textContent = pad(seconds % 60); }
    };

    tick();
    setInterval(tick, 1000);
  });

  /* ---------------------------------------------------------- hold timer */

  document.querySelectorAll('[data-hold-expires]').forEach(function (element) {
    var expires = new Date(element.getAttribute('data-hold-expires').replace(' ', 'T')).getTime();
    var output = element.querySelector('[data-hold-clock]');

    if (isNaN(expires) || !output) { return; }

    var tick = function () {
      var remaining = Math.max(0, Math.floor((expires - Date.now()) / 1000));
      var minutes = Math.floor(remaining / 60);
      var seconds = remaining % 60;

      output.textContent = minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
      element.classList.toggle('is-urgent', remaining < 180);

      if (remaining === 0) {
        output.textContent = 'expired';
        window.setTimeout(function () { window.location.reload(); }, 2000);
      }
    };

    tick();
    setInterval(tick, 1000);
  });

  /* --------------------------------------------------------- reveal in */

  var revealables = document.querySelectorAll('.reveal');

  if (revealables.length) {
    if (reduceMotion || !('IntersectionObserver' in window)) {
      revealables.forEach(function (element) { element.classList.add('is-visible'); });
    } else {
      var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target);
          }
        });
      }, { rootMargin: '0px 0px -8% 0px', threshold: 0.06 });

      revealables.forEach(function (element) { observer.observe(element); });
    }
  }

  /* ---------------------------------------------------------- lightbox */

  var lightbox = document.getElementById('lightbox');

  if (lightbox) {
    var lightboxImage = lightbox.querySelector('img');
    var lightboxCaption = lightbox.querySelector('.lightbox__caption');

    var close = function () {
      lightbox.classList.remove('is-open');
      document.body.style.overflow = '';
    };

    document.querySelectorAll('[data-lightbox]').forEach(function (item) {
      item.addEventListener('click', function () {
        lightboxImage.src = item.getAttribute('data-lightbox');
        lightboxImage.alt = item.getAttribute('data-lightbox-alt') || '';
        if (lightboxCaption) { lightboxCaption.textContent = item.getAttribute('data-lightbox-caption') || ''; }
        lightbox.classList.add('is-open');
        document.body.style.overflow = 'hidden';
      });
    });

    lightbox.addEventListener('click', function (event) {
      if (event.target === lightbox || event.target.classList.contains('lightbox__close')) { close(); }
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && lightbox.classList.contains('is-open')) { close(); }
    });
  }

  /* --------------------------------------------------- quantity steppers */

  document.querySelectorAll('.qty').forEach(function (widget) {
    var input = widget.querySelector('input');

    widget.querySelectorAll('button').forEach(function (button) {
      button.addEventListener('click', function () {
        var step = button.getAttribute('data-step') === 'up' ? 1 : -1;
        var min = parseInt(input.getAttribute('min') || '1', 10);
        var max = parseInt(input.getAttribute('max') || '99', 10);
        var next = Math.min(max, Math.max(min, (parseInt(input.value, 10) || min) + step));

        input.value = next;
        input.dispatchEvent(new Event('change', { bubbles: true }));

        if (widget.hasAttribute('data-autosubmit')) {
          var form = widget.closest('form');
          if (form) { form.submit(); }
        }
      });
    });
  });

  /* ------------------------------------------------------- cart badge */

  function refreshCartCount() {
    var badge = document.querySelector('[data-cart-count]');
    if (!badge) { return; }

    fetch('/cart/status', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (response) { return response.ok ? response.json() : null; })
      .then(function (data) {
        if (data && typeof data.count === 'number') {
          badge.textContent = data.count;
          badge.hidden = data.count === 0;
        }
      })
      .catch(function () { /* the server-rendered count stays */ });
  }

  window.addEventListener('pageshow', function (event) {
    if (event.persisted) { refreshCartCount(); }
  });

  /* -------------------------------------------------- ecommerce tracking */

  document.querySelectorAll('[data-track]').forEach(function (element) {
    element.addEventListener('click', function () {
      var params = {};
      try { params = JSON.parse(element.getAttribute('data-track-params') || '{}'); } catch (e) { params = {}; }
      track(element.getAttribute('data-track'), params);
    });
  });

  document.querySelectorAll('form[data-track-submit]').forEach(function (form) {
    form.addEventListener('submit', function () {
      var params = {};
      try { params = JSON.parse(form.getAttribute('data-track-params') || '{}'); } catch (e) { params = {}; }
      track(form.getAttribute('data-track-submit'), params);
    });
  });

  /* --------------------------------------------------- single submission */

  document.querySelectorAll('form[data-once]').forEach(function (form) {
    form.addEventListener('submit', function () {
      var button = form.querySelector('[type="submit"]');
      if (button) {
        button.classList.add('is-disabled');
        button.setAttribute('aria-busy', 'true');
        if (button.dataset.busyLabel) { button.textContent = button.dataset.busyLabel; }
      }
    });
  });

  /* ------------------------------------------------------ cookie notice */

  var cookieNotice = document.getElementById('cookie-notice');

  if (cookieNotice) {
    var STORAGE_KEY = 'sarcna_cookie_ack';
    var acknowledged = false;

    try { acknowledged = window.localStorage.getItem(STORAGE_KEY) === '1'; } catch (e) { acknowledged = false; }

    if (!acknowledged) {
      cookieNotice.hidden = false;
    }

    var dismiss = cookieNotice.querySelector('[data-dismiss]');
    if (dismiss) {
      dismiss.addEventListener('click', function () {
        cookieNotice.hidden = true;
        try { window.localStorage.setItem(STORAGE_KEY, '1'); } catch (e) { /* private mode */ }
      });
    }
  }

  /* ------------------------------------------------- dismissible notices */

  document.querySelectorAll('[data-dismissible]').forEach(function (element) {
    var button = element.querySelector('[data-dismiss]');
    if (!button) { return; }

    button.addEventListener('click', function () { element.hidden = true; });
  });

  /* -------------------------------------------------- WhatsApp on checkout */

  var whatsapp = document.querySelector('.whatsapp-fab');

  if (whatsapp) {
    if (document.body.dataset.minimiseWhatsapp === '1') {
      whatsapp.classList.add('is-minimised');
    }

    whatsapp.addEventListener('click', function () {
      track('whatsapp_click', { page: window.location.pathname });
    });
  }

  /* -------------------------------------------------- accommodation form */

  document.querySelectorAll('[data-booking-form]').forEach(function (form) {
    var modeInputs = form.querySelectorAll('input[name="mode"]');
    var nightInputs = form.querySelectorAll('input[name="nights[]"]');
    var totalOutput = form.querySelector('[data-booking-total]');
    var submit = form.querySelector('[type="submit"]');

    function recalculate() {
      var mode = form.querySelector('input[name="mode"]:checked');
      var isPrivate = mode && mode.value === 'unit';
      var total = 0;
      var selected = 0;

      nightInputs.forEach(function (input) {
        var option = input.closest('.night-option');

        if (input.checked && !input.disabled) {
          selected++;
          total += parseInt(isPrivate ? input.dataset.unitPrice : input.dataset.bedPrice, 10) || 0;
        }

        if (option) {
          var price = parseInt(isPrivate ? input.dataset.unitPrice : input.dataset.bedPrice, 10) || 0;
          var label = option.querySelector('[data-night-price]');
          if (label) { label.textContent = 'R' + (price / 100).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' '); }
        }
      });

      if (totalOutput) {
        totalOutput.textContent = 'R' + (total / 100).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
      }

      if (submit) {
        submit.classList.toggle('is-disabled', selected === 0);
      }
    }

    modeInputs.forEach(function (input) { input.addEventListener('change', recalculate); });
    nightInputs.forEach(function (input) { input.addEventListener('change', recalculate); });
    recalculate();
  });

  /* ------------------------------------------------------ transport form */

  document.querySelectorAll('[data-transport-form]').forEach(function (form) {
    var slot = form.querySelector('select[name="slot_id"]');
    var flight = form.querySelector('[data-flight-field]');

    if (!slot || !flight) { return; }

    var sync = function () {
      var option = slot.options[slot.selectedIndex];
      flight.hidden = !(option && option.dataset.requiresFlight === '1');
    };

    slot.addEventListener('change', sync);
    sync();
  });

  /* -------------------------------------------------- donation amount */

  document.querySelectorAll('[data-donation-form]').forEach(function (form) {
    var presets = form.querySelectorAll('[data-amount]');
    var custom = form.querySelector('input[name="amount"]');

    presets.forEach(function (button) {
      button.addEventListener('click', function (event) {
        event.preventDefault();
        if (custom) {
          custom.value = button.getAttribute('data-amount');
          custom.focus();
        }
        presets.forEach(function (other) { other.classList.remove('btn--gold'); other.classList.add('btn--ghost'); });
        button.classList.remove('btn--ghost');
        button.classList.add('btn--gold');
      });
    });
  });
})();

/* ------------------------------------------------- venue videos (facade) */
/* Nothing is fetched from YouTube until the visitor presses play; the click
   swaps the poster for a privacy-enhanced youtube-nocookie.com iframe. */
(function () {
  document.querySelectorAll('.video-card').forEach(function (card) {
    var poster = card.querySelector('.video-card__poster');
    if (!poster) return;

    poster.addEventListener('click', function () {
      var id    = card.getAttribute('data-video-id');
      var title = card.getAttribute('data-video-title') || 'Video';
      if (!id || !/^[A-Za-z0-9_-]{11}$/.test(id)) return;

      var frame = document.createElement('div');
      frame.className = 'video-card__frame';

      var iframe = document.createElement('iframe');
      iframe.src = 'https://www.youtube-nocookie.com/embed/' + id + '?autoplay=1&rel=0';
      iframe.title = title;
      iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
      iframe.allowFullscreen = true;

      frame.appendChild(iframe);
      poster.replaceWith(frame);
      iframe.focus();
    });
  });
})();

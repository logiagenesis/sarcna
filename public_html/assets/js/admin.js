/* SARCNA 2027 admin — small conveniences only. Everything works without it. */
(function () {
  'use strict';

  // Confirm destructive actions.
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      if (!window.confirm(form.getAttribute('data-confirm'))) {
        event.preventDefault();
      }
    });
  });

  // Auto-submit filter forms when a select changes.
  document.querySelectorAll('form[data-autosubmit] select').forEach(function (select) {
    select.addEventListener('change', function () { select.form.submit(); });
  });

  // Slug helper: fill the slug from the name unless it has been edited.
  document.querySelectorAll('[data-slug-source]').forEach(function (source) {
    var target = document.getElementById(source.getAttribute('data-slug-source'));
    if (!target) { return; }

    var touched = target.value !== '';
    target.addEventListener('input', function () { touched = true; });

    source.addEventListener('input', function () {
      if (touched) { return; }
      target.value = source.value.toLowerCase()
        .normalize('NFD').replace(/[̀-ͯ]/g, '')
        .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
    });
  });

  // Keep a running total when editing bed rates.
  document.querySelectorAll('[data-rate-table]').forEach(function (table) {
    table.addEventListener('input', function () {
      var total = 0;
      table.querySelectorAll('input[data-rate]').forEach(function (input) {
        total += parseFloat(input.value || '0');
      });
      var output = table.querySelector('[data-rate-total]');
      if (output) { output.textContent = 'R' + total.toFixed(2); }
    });
  });
})();

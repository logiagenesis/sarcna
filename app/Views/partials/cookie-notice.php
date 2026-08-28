<?php
use App\Services\SettingsService;

if (!SettingsService::bool('cookie_notice_enabled', true)) {
    return;
}
?>
<div id="cookie-notice" hidden
     style="position:fixed;left:1rem;right:1rem;bottom:1rem;z-index:85;max-width:520px;background:var(--surface-raised);border:1px solid var(--line);border-radius:var(--radius-m);box-shadow:var(--shadow-l);padding:1rem 1.15rem">
  <p style="font-size:var(--step--1);margin-bottom:.6rem">
    We use a small number of cookies to keep your cart and sign-in working, and anonymised Google Analytics to understand
    how the site is used. See our <a href="<?= e(url('/privacy-policy')) ?>">privacy policy</a>.
  </p>
  <button class="btn btn--sm" type="button" data-dismiss>Got it</button>
</div>

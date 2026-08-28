<?php
use App\Services\SettingsService;

$email    = (string) SettingsService::get('contact_email', config('contact.email'));
$phone    = (string) SettingsService::get('contact_phone', '');
$event    = config('event');
?>
<footer class="site-footer">
  <div class="container">
    <div class="footer-grid">
      <div class="footer-brand">
        <img src="<?= e(asset('brand/logo-light.svg')) ?>" alt="<?= e(SettingsService::get('site_name', config('app.name'))) ?>" width="250" height="72">
        <p><?= e(SettingsService::get('event_slogan', $event['slogan'])) ?></p>
        <p><strong><?= e(SettingsService::get('event_dates_label', $event['dates_label'])) ?></strong><br>
           <?= e(SettingsService::get('event_venue_name', $event['venue_name'])) ?><br>
           <?= e($event['venue_region']) ?></p>
      </div>

      <div>
        <h4>The convention</h4>
        <ul>
          <li><a href="<?= e(url('/convention')) ?>">Convention overview</a></li>
          <li><a href="<?= e(url('/programme')) ?>">Weekend programme</a></li>
          <li><a href="<?= e(url('/venue')) ?>">The venue</a></li>
          <li><a href="<?= e(url('/venue/history')) ?>">Venue history</a></li>
          <li><a href="<?= e(url('/gallery')) ?>">Gallery</a></li>
          <li><a href="<?= e(url('/faq')) ?>">FAQ</a></li>
        </ul>
      </div>

      <div>
        <h4>Book &amp; buy</h4>
        <ul>
          <li><a href="<?= e(url('/shop/registration')) ?>">Registration</a></li>
          <li><a href="<?= e(url('/accommodation')) ?>">Accommodation</a></li>
          <li><a href="<?= e(url('/transport')) ?>">Transport &amp; shuttles</a></li>
          <li><a href="<?= e(url('/shop/merchandise')) ?>">Merchandise</a></li>
          <li><a href="<?= e(url('/donations')) ?>">Donations</a></li>
          <li><a href="<?= e(url('/service')) ?>">Service applications</a></li>
        </ul>
      </div>

      <div>
        <h4>Your account</h4>
        <ul>
          <li><a href="<?= e(url('/account')) ?>">Dashboard</a></li>
          <li><a href="<?= e(url('/account/orders')) ?>">Order history</a></li>
          <li><a href="<?= e(url('/account/bookings')) ?>">Accommodation bookings</a></li>
          <li><a href="<?= e(url('/account/transport')) ?>">Transport bookings</a></li>
          <li><a href="<?= e(url('/cart')) ?>">Cart</a></li>
        </ul>
      </div>

      <div>
        <h4>Get in touch</h4>
        <ul>
          <li><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></li>
          <?php if ($phone !== ''): ?><li><a href="tel:<?= e(preg_replace('/\s+/', '', $phone)) ?>"><?= e($phone) ?></a></li><?php endif; ?>
          <li><a href="<?= e(whatsapp_link()) ?>" rel="noopener" target="_blank">WhatsApp us</a></li>
          <li><a href="<?= e(url('/contact')) ?>">Contact form</a></li>
        </ul>
        <h4 style="margin-top:1.25rem">Policies</h4>
        <ul>
          <li><a href="<?= e(url('/privacy-policy')) ?>">Privacy policy</a></li>
          <li><a href="<?= e(url('/terms')) ?>">Terms &amp; conditions</a></li>
          <li><a href="<?= e(url('/refund-policy')) ?>">Refund policy</a></li>
          <li><a href="<?= e(url('/code-of-conduct')) ?>">Code of conduct</a></li>
          <li><a href="<?= e(url('/photo-anonymity-notice')) ?>">Photo &amp; anonymity</a></li>
        </ul>
      </div>
    </div>

    <div class="footer-bottom">
      <p>&copy; <?= date('Y') ?> <?= e(SettingsService::get('site_name', config('app.name'))) ?>. All rights reserved.</p>
      <p>Payments processed securely by PayFast. <a href="<?= e(url('/sitemap.xml')) ?>">Sitemap</a></p>
    </div>
  </div>
</footer>

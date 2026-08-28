<?php
use App\Core\View;
View::layout('layouts.public');
View::start('content');
$event = config('event');
?>
<?php View::include('partials.page-hero', [
  'eyebrow' => 'Contact',
  'title'   => 'Talk to the committee',
  'lede'    => 'WhatsApp is usually fastest. Email and the form below both reach the same people.',
  'image'   => 'img/venue/dining-lounge.jpg',
  'crumbs'  => ['Contact' => null],
]); ?>

<section class="section">
  <div class="container grid grid--sidebar">
    <div>
      <div class="rule"></div>
      <h2>Ways to reach us</h2>

      <div class="grid grid--2" style="margin-top:1.25rem">
        <?php foreach ($emails as $label => $address): if ($address === '') continue; ?>
          <div class="card"><div class="card__body">
            <h3 class="card__title" style="font-size:var(--step-0)"><?= e($label) ?></h3>
            <p class="card__text"><a href="mailto:<?= e($address) ?>"><?= e($address) ?></a></p>
          </div></div>
        <?php endforeach; ?>

        <?php if ($phone !== ''): ?>
          <div class="card"><div class="card__body">
            <h3 class="card__title" style="font-size:var(--step-0)">Phone</h3>
            <p class="card__text"><a href="tel:<?= e(preg_replace('/\s+/', '', $phone)) ?>"><?= e($phone) ?></a></p>
          </div></div>
        <?php endif; ?>

        <div class="card"><div class="card__body">
          <h3 class="card__title" style="font-size:var(--step-0)">WhatsApp</h3>
          <p class="card__text"><a href="<?= e(whatsapp_link()) ?>" target="_blank" rel="noopener" data-track="whatsapp_click">Message the committee</a></p>
        </div></div>
      </div>

      <h2 style="margin-top:2rem">The venue</h2>
      <p><strong><?= e($event['venue_name']) ?></strong><br><?= e($event['venue_region']) ?></p>
      <p class="muted" style="font-size:var(--step--1)">Please do not contact the venue directly about convention bookings — they will send you back to us. Everything about the weekend runs through the committee.</p>
      <a class="btn btn--ghost" href="https://www.google.com/maps/search/?api=1&amp;query=Boschendal+Franschhoek" target="_blank" rel="noopener">Directions in Google Maps</a>

      <h2 style="margin-top:2rem">Before you write</h2>
      <p>Most questions are already answered on the <a href="<?= e(url('/faq')) ?>">FAQ page</a> — especially about booking single beds, transport times and refunds.</p>
    </div>

    <aside class="summary">
      <h2 style="font-size:var(--step-2);margin-bottom:.25rem">Send a message</h2>
      <p class="muted" style="font-size:var(--step--1)">We reply to everything, usually within two or three days.</p>

      <form method="post" action="<?= e(url('/contact')) ?>" data-once data-track-submit="generate_lead" data-track-params='{"form":"contact"}'>
        <?= csrf_field() ?>
        <div class="honeypot"><label>Leave this empty <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

        <div class="field">
          <label class="field__label" for="name">Your name</label>
          <input type="text" id="name" name="name" required value="<?= e(old('name', auth() ? trim(auth()['first_name'] . ' ' . auth()['last_name']) : '')) ?>">
          <?php if ($m = error_for('name')): ?><p class="field__error"><?= e($m) ?></p><?php endif; ?>
        </div>

        <div class="field">
          <label class="field__label" for="email">Email</label>
          <input type="email" id="email" name="email" required value="<?= e(old('email', auth()['email'] ?? '')) ?>">
          <?php if ($m = error_for('email')): ?><p class="field__error"><?= e($m) ?></p><?php endif; ?>
        </div>

        <div class="field">
          <label class="field__label" for="phone">Phone (optional)</label>
          <input type="tel" id="phone" name="phone" value="<?= e(old('phone')) ?>">
        </div>

        <div class="field">
          <label class="field__label" for="subject">Subject</label>
          <select id="subject" name="subject" required>
            <?php foreach (['Registration', 'Accommodation', 'Transport', 'Merchandise', 'Service', 'Accessibility', 'Payments', 'Something else'] as $subject): ?>
              <option value="<?= e($subject) ?>" <?= old('subject') === $subject ? 'selected' : '' ?>><?= e($subject) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field">
          <label class="field__label" for="message">Message</label>
          <textarea id="message" name="message" rows="6" required><?= e(old('message')) ?></textarea>
          <?php if ($m = error_for('message')): ?><p class="field__error"><?= e($m) ?></p><?php endif; ?>
        </div>

        <button class="btn btn--block btn--lg" type="submit" data-busy-label="Sending…">Send message</button>
        <p class="muted" style="font-size:var(--step--1);margin-top:.75rem">We use your details only to reply. See the <a href="<?= e(url('/privacy-policy')) ?>">privacy policy</a>.</p>
      </form>
    </aside>
  </div>
</section>
<?php View::stop(); ?>

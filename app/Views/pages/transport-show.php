<?php
use App\Core\View;
use App\Services\TransportService;
View::layout('layouts.public');
View::start('content');
$available = array_values(array_filter($slots, static fn (array $s): bool => (int) $s['capacity'] > (int) $s['seats_taken']));
?>
<?php View::include('partials.page-hero', [
  'eyebrow' => 'Transport',
  'title'   => $route['name'],
  'lede'    => (int) $route['price_cents'] === 0 ? 'Free — book one per vehicle.' : money((int) $route['price_cents']) . ' per passenger.',
  'image'   => 'img/transport/airport-shuttle.jpg',
  'crumbs'  => ['Transport' => '/transport', $route['name'] => null],
]); ?>

<section class="section">
  <div class="container grid grid--sidebar">
    <div>
      <div class="prose"><?= $route['description'] ?></div>

      <h2 style="margin-top:2rem">Departures</h2>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Departs</th><th>Pick-up</th><th>Drop-off</th><th class="numeric">Seats left</th></tr></thead>
          <tbody>
            <?php foreach ($slots as $slot):
              $left = max(0, (int) $slot['capacity'] - (int) $slot['seats_taken']); ?>
              <tr>
                <td><strong><?= e(za_date((string) $slot['departs_at'], 'D j M Y, H:i')) ?></strong><?php if ($slot['notes']): ?><br><span class="muted"><?= e($slot['notes']) ?></span><?php endif; ?></td>
                <td><?= e($slot['pickup_point']) ?></td>
                <td><?= e($slot['dropoff_point']) ?></td>
                <td class="numeric"><?= $left === 0 ? '<span class="badge badge--error">Full</span>' : $left . ' of ' . (int) $slot['capacity'] ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <h2 style="margin-top:2rem">What to expect</h2>
      <ul>
        <li>Be at the pick-up point <strong>15 minutes before departure</strong>.</li>
        <li>One suitcase and one small bag per passenger — tell us if you have more.</li>
        <li>The transport co-ordinator sends a WhatsApp with the exact meeting point the week before.</li>
        <li>Delayed flight? Message us as early as you can and we will move you to a later shuttle if there is a seat.</li>
      </ul>
    </div>

    <aside class="summary">
      <?php if (!$isOpen): ?>
        <div class="alert alert--warning" style="margin:0"><div><p>Transport booking is closed at the moment.</p></div></div>
      <?php elseif ($available === []): ?>
        <div class="empty-state" style="padding:1.5rem">
          <div class="empty-state__icon">🚐</div>
          <h3 style="font-size:var(--step-1)">Every departure is full</h3>
          <p style="font-size:var(--step--1)">Contact the transport co-ordinator — we add vehicles when there is enough demand.</p>
          <a class="btn btn--ghost btn--sm" style="margin-top:.75rem" href="<?= e(url('/contact')) ?>">Contact us</a>
        </div>
      <?php else: ?>
        <h2 style="font-size:var(--step-2);margin-bottom:.25rem">Book your seat</h2>
        <p class="muted" style="font-size:var(--step--1)"><?= (int) $route['price_cents'] === 0 ? 'Free' : money((int) $route['price_cents']) . ' per passenger' ?></p>

        <form method="post" action="<?= e(url('/transport/' . $route['slug'] . '/book')) ?>" data-transport-form data-once
              data-track-submit="add_to_cart" data-track-params='{"item_category":"transport"}'>
          <?= csrf_field() ?>

          <div class="field">
            <label class="field__label" for="slot_id">Departure</label>
            <select id="slot_id" name="slot_id" required>
              <?php foreach ($available as $slot): ?>
                <option value="<?= (int) $slot['id'] ?>" data-requires-flight="<?= (int) $route['requires_flight_number'] ?>">
                  <?= e(za_date((string) $slot['departs_at'], 'D j M, H:i')) ?> — <?= e($slot['pickup_point']) ?>
                  (<?= max(0, (int) $slot['capacity'] - (int) $slot['seats_taken']) ?> seats)
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="field">
            <label class="field__label" for="seats">Seats</label>
            <select id="seats" name="seats">
              <?php for ($i = 1; $i <= 6; $i++): ?><option value="<?= $i ?>"><?= $i ?> passenger<?= $i === 1 ? '' : 's' ?></option><?php endfor; ?>
            </select>
          </div>

          <div class="field">
            <label class="field__label" for="passenger_name">Lead passenger name</label>
            <input type="text" id="passenger_name" name="passenger_name" required
                   value="<?= e(old('passenger_name', auth() ? trim(auth()['first_name'] . ' ' . auth()['last_name']) : '')) ?>">
            <?php if ($message = error_for('passenger_name')): ?><p class="field__error"><?= e($message) ?></p><?php endif; ?>
          </div>

          <div class="field-row field-row--2">
            <div class="field">
              <label class="field__label" for="phone">Mobile number</label>
              <input type="tel" id="phone" name="phone" required value="<?= e(old('phone', auth()['phone'] ?? '')) ?>">
              <?php if ($message = error_for('phone')): ?><p class="field__error"><?= e($message) ?></p><?php endif; ?>
            </div>
            <div class="field">
              <label class="field__label" for="email">Email</label>
              <input type="email" id="email" name="email" required value="<?= e(old('email', auth()['email'] ?? '')) ?>">
            </div>
          </div>

          <div class="field" data-flight-field <?= (int) $route['requires_flight_number'] === 1 ? '' : 'hidden' ?>>
            <label class="field__label" for="flight_number">Flight number</label>
            <input type="text" id="flight_number" name="flight_number" placeholder="e.g. FA231" value="<?= e(old('flight_number')) ?>">
            <p class="field__hint">So we can match you to the shuttle closest to your landing time.</p>
          </div>

          <div class="field-row field-row--2">
            <div class="field">
              <label class="field__label" for="luggage_count">Bags</label>
              <input type="number" id="luggage_count" name="luggage_count" min="0" max="6" value="1">
            </div>
            <div class="field">
              <label class="field__label" for="accessibility_needs">Accessibility</label>
              <input type="text" id="accessibility_needs" name="accessibility_needs" placeholder="Wheelchair, mobility aid…">
            </div>
          </div>

          <div class="field">
            <label class="field__label" for="notes">Notes</label>
            <textarea id="notes" name="notes" rows="2" style="min-height:70px"></textarea>
          </div>

          <button class="btn btn--block btn--lg" type="submit" data-busy-label="Adding…">Add to cart</button>
        </form>
      <?php endif; ?>
    </aside>
  </div>
</section>

<?php if ($others !== []): ?>
<section class="section section--sunk">
  <div class="container">
    <div class="section-head"><div class="rule"></div><h2>Other routes</h2></div>
    <div class="grid grid--3">
      <?php foreach ($others as $other): ?>
        <a class="card card--link" href="<?= e(url('/transport/' . $other['slug'])) ?>">
          <div class="card__body">
            <h3 class="card__title" style="font-size:var(--step-0)"><?= e($other['name']) ?></h3>
            <div class="card__foot">
              <span class="card__price"><?= (int) $other['price_cents'] === 0 ? 'Free' : e(money((int) $other['price_cents'])) ?></span>
              <span class="link-arrow">Book <span>&rarr;</span></span>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
<?php View::stop(); ?>

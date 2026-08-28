<?php
use App\Core\View;
View::layout('layouts.public');
View::start('content');
$items = $totals['items'];
?>
<section class="section">
  <div class="container">
    <ol class="steps">
      <li class="is-done">Cart</li>
      <li class="is-current">Details</li>
      <li>Payment</li>
      <li>Confirmed</li>
    </ol>

    <h1 style="font-size:var(--step-3)">Checkout</h1>

    <?php if ($holdExpires !== null): ?>
      <div class="hold-timer" data-hold-expires="<?= e($holdExpires) ?>">
        <span>⏱</span><span>Your beds are held for <strong data-hold-clock>--:--</strong>.</span>
      </div>
    <?php endif; ?>

    <form method="post" action="<?= e(url('/checkout')) ?>" data-once
          data-track-submit="add_payment_info" data-track-params='{"value":<?= (int) $totals['total_cents'] / 100 ?>,"currency":"ZAR"}'>
      <?= csrf_field() ?>

      <div class="grid grid--sidebar">
        <div>
          <div class="form-panel">
            <h2 style="font-size:var(--step-2)">Who is booking</h2>
            <div class="field-row field-row--2">
              <div class="field">
                <label class="field__label" for="first_name">First name</label>
                <input type="text" id="first_name" name="first_name" required value="<?= e(old('first_name', $user['first_name'] ?? '')) ?>">
                <?php if ($m = error_for('first_name')): ?><p class="field__error"><?= e($m) ?></p><?php endif; ?>
              </div>
              <div class="field">
                <label class="field__label" for="last_name">Last name</label>
                <input type="text" id="last_name" name="last_name" required value="<?= e(old('last_name', $user['last_name'] ?? '')) ?>">
              </div>
            </div>
            <div class="field-row field-row--2">
              <div class="field">
                <label class="field__label" for="email">Email</label>
                <input type="email" id="email" name="email" required value="<?= e(old('email', $user['email'] ?? '')) ?>">
                <p class="field__hint">Confirmations and your check-in code go here.</p>
                <?php if ($m = error_for('email')): ?><p class="field__error"><?= e($m) ?></p><?php endif; ?>
              </div>
              <div class="field">
                <label class="field__label" for="phone">Mobile number</label>
                <input type="tel" id="phone" name="phone" required value="<?= e(old('phone', $user['phone'] ?? '')) ?>">
                <p class="field__hint">Used for WhatsApp updates about shuttles and check-in.</p>
                <?php if ($m = error_for('phone')): ?><p class="field__error"><?= e($m) ?></p><?php endif; ?>
              </div>
            </div>
          </div>

          <?php
          $registrations   = array_filter($items, static fn (array $i): bool => $i['item_type'] === 'registration');
          $accommodation   = array_filter($items, static fn (array $i): bool => $i['item_type'] === 'accommodation');
          $transport       = array_filter($items, static fn (array $i): bool => $i['item_type'] === 'transport');
          ?>

          <?php if ($registrations !== []): ?>
            <div class="form-panel" style="margin-top:1.5rem">
              <h2 style="font-size:var(--step-2)">Attendee details</h2>
              <p class="muted" style="font-size:var(--step--1)">One badge per registration. Leave blank to use the booking name above.</p>
              <?php foreach ($registrations as $item): ?>
                <fieldset>
                  <legend style="font-size:var(--step--1)"><?= e($item['description']) ?><?= (int) $item['quantity'] > 1 ? ' × ' . (int) $item['quantity'] : '' ?></legend>
                  <div class="field-row field-row--2">
                    <div class="field">
                      <label class="field__label" for="attendee_name_<?= (int) $item['id'] ?>">Attendee name</label>
                      <input type="text" id="attendee_name_<?= (int) $item['id'] ?>" name="items[<?= (int) $item['id'] ?>][attendee_name]"
                             value="<?= e($item['meta']['attendee_name'] ?? '') ?>">
                    </div>
                    <div class="field">
                      <label class="field__label" for="attendee_email_<?= (int) $item['id'] ?>">Attendee email</label>
                      <input type="email" id="attendee_email_<?= (int) $item['id'] ?>" name="items[<?= (int) $item['id'] ?>][attendee_email]"
                             value="<?= e($item['meta']['attendee_email'] ?? '') ?>">
                    </div>
                  </div>
                  <div class="field" style="margin-bottom:0">
                    <label class="field__label" for="dietary_<?= (int) $item['id'] ?>">Dietary notes</label>
                    <input type="text" id="dietary_<?= (int) $item['id'] ?>" name="items[<?= (int) $item['id'] ?>][dietary_notes]"
                           value="<?= e($item['meta']['dietary_notes'] ?? '') ?>" placeholder="Vegetarian, halaal, allergies…">
                  </div>
                </fieldset>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <?php if ($accommodation !== []): ?>
            <div class="form-panel" style="margin-top:1.5rem">
              <h2 style="font-size:var(--step-2)">Who is sleeping where</h2>
              <p class="muted" style="font-size:var(--step--1)">Confirm the guest for each bed-night so the rooming list is right.</p>
              <?php foreach ($accommodation as $item): $meta = $item['meta']; ?>
                <fieldset>
                  <legend style="font-size:var(--step--1)"><?= e($item['description']) ?></legend>
                  <?php if (!empty($meta['unit_name'])): ?>
                    <p class="muted" style="font-size:var(--step--1);margin-bottom:.75rem">
                      <?= e($meta['unit_name']) ?> &middot; <?= (int) ($meta['bed_count'] ?? 1) ?> bed<?= (int) ($meta['bed_count'] ?? 1) === 1 ? '' : 's' ?>
                      <?= !empty($meta['is_private_unit']) ? ' &middot; whole unit' : '' ?>
                    </p>
                  <?php endif; ?>
                  <div class="field-row field-row--2">
                    <div class="field">
                      <label class="field__label" for="guest_name_<?= (int) $item['id'] ?>">Guest name</label>
                      <input type="text" id="guest_name_<?= (int) $item['id'] ?>" name="items[<?= (int) $item['id'] ?>][guest_name]" value="<?= e($meta['guest_name'] ?? '') ?>">
                    </div>
                    <div class="field">
                      <label class="field__label" for="guest_phone_<?= (int) $item['id'] ?>">Guest mobile</label>
                      <input type="tel" id="guest_phone_<?= (int) $item['id'] ?>" name="items[<?= (int) $item['id'] ?>][guest_phone]" value="<?= e($meta['guest_phone'] ?? '') ?>">
                    </div>
                  </div>
                  <div class="field-row field-row--2">
                    <div class="field">
                      <label class="field__label" for="roommate_<?= (int) $item['id'] ?>">Roommate request</label>
                      <input type="text" id="roommate_<?= (int) $item['id'] ?>" name="items[<?= (int) $item['id'] ?>][roommate_request]" value="<?= e($meta['roommate_request'] ?? '') ?>">
                    </div>
                    <div class="field">
                      <label class="field__label" for="access_<?= (int) $item['id'] ?>">Accessibility needs</label>
                      <input type="text" id="access_<?= (int) $item['id'] ?>" name="items[<?= (int) $item['id'] ?>][accessibility_needs]" value="<?= e($meta['accessibility_needs'] ?? '') ?>">
                    </div>
                  </div>
                  <div class="field" style="margin-bottom:0">
                    <label class="field__label" for="notes_<?= (int) $item['id'] ?>">Notes</label>
                    <input type="text" id="notes_<?= (int) $item['id'] ?>" name="items[<?= (int) $item['id'] ?>][notes]" value="<?= e($meta['notes'] ?? '') ?>">
                  </div>
                </fieldset>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <?php if ($transport !== []): ?>
            <div class="form-panel" style="margin-top:1.5rem">
              <h2 style="font-size:var(--step-2)">Passenger details</h2>
              <p class="muted" style="font-size:var(--step--1)">The transport co-ordinator uses these to build the manifest.</p>
              <?php foreach ($transport as $item): $meta = $item['meta']; ?>
                <fieldset>
                  <legend style="font-size:var(--step--1)"><?= e($item['description']) ?><?= (int) $item['quantity'] > 1 ? ' × ' . (int) $item['quantity'] . ' seats' : '' ?></legend>
                  <div class="field-row field-row--2">
                    <div class="field">
                      <label class="field__label" for="passenger_<?= (int) $item['id'] ?>">Lead passenger</label>
                      <input type="text" id="passenger_<?= (int) $item['id'] ?>" name="items[<?= (int) $item['id'] ?>][passenger_name]" value="<?= e($meta['passenger_name'] ?? '') ?>">
                    </div>
                    <div class="field">
                      <label class="field__label" for="flight_<?= (int) $item['id'] ?>">Flight number</label>
                      <input type="text" id="flight_<?= (int) $item['id'] ?>" name="items[<?= (int) $item['id'] ?>][flight_number]" value="<?= e($meta['flight_number'] ?? '') ?>">
                    </div>
                  </div>
                  <div class="field" style="margin-bottom:0">
                    <label class="field__label" for="tphone_<?= (int) $item['id'] ?>">Mobile on the day</label>
                    <input type="tel" id="tphone_<?= (int) $item['id'] ?>" name="items[<?= (int) $item['id'] ?>][phone]" value="<?= e($meta['phone'] ?? '') ?>">
                  </div>
                </fieldset>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <div class="form-panel" style="margin-top:1.5rem">
            <div class="field">
              <label class="field__label" for="customer_note">Anything else the committee should know?</label>
              <textarea id="customer_note" name="customer_note" rows="3" style="min-height:90px"><?= e(old('customer_note')) ?></textarea>
            </div>

            <label class="checkbox">
              <input type="checkbox" name="terms" value="1" required>
              <span>I have read and accept the <a href="<?= e(url('/terms')) ?>" target="_blank">terms and conditions</a>,
                the <a href="<?= e(url('/refund-policy')) ?>" target="_blank">refund policy</a>,
                the <a href="<?= e(url('/privacy-policy')) ?>" target="_blank">privacy policy</a> and the
                <a href="<?= e(url('/code-of-conduct')) ?>" target="_blank">code of conduct</a>.</span>
            </label>
            <?php if ($m = error_for('terms')): ?><p class="field__error"><?= e($m) ?></p><?php endif; ?>
          </div>
        </div>

        <aside class="summary">
          <h2 style="font-size:var(--step-2)">Your order</h2>
          <?php foreach ($items as $item): ?>
            <div class="summary__row">
              <span><?= e(excerpt($item['description'], 46)) ?><?= (int) $item['quantity'] > 1 ? ' × ' . (int) $item['quantity'] : '' ?></span>
              <span><?= e(money((int) $item['total_cents'])) ?></span>
            </div>
          <?php endforeach; ?>

          <div class="summary__row"><span>Subtotal</span><span><?= e(money($totals['subtotal_cents'])) ?></span></div>
          <?php if ($totals['discount_cents'] > 0): ?>
            <div class="summary__row" style="color:var(--success)"><span>Discount</span><span>&minus;<?= e(money($totals['discount_cents'])) ?></span></div>
          <?php endif; ?>
          <div class="summary__row summary__row--total"><span>Total</span><span><?= e(money($totals['total_cents'])) ?></span></div>

          <button class="btn btn--block btn--lg" type="submit" data-busy-label="Preparing payment…">Pay with PayFast</button>

          <?php if ($termsNote !== ''): ?>
            <p class="muted" style="font-size:var(--step--1);margin-top:.75rem"><?= e($termsNote) ?></p>
          <?php endif; ?>

          <p class="muted" style="font-size:var(--step--1);margin-top:.75rem">
            You will be taken to PayFast to pay by card, Instant EFT, SnapScan or Mobicred. We never see your card details.
          </p>
          <a class="link-arrow" href="<?= e(url('/cart')) ?>" style="margin-top:.75rem;display:inline-flex">&larr; Back to cart</a>

          <?php View::include('partials.travel-buttons'); ?>
        </aside>
      </div>
    </form>
  </div>
</section>
<?php View::stop(); ?>

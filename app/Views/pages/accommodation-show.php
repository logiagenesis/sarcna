<?php
use App\Core\View;
View::layout('layouts.public');
View::start('content');
$totalFree = array_sum($availability);
?>
<?php View::include('partials.page-hero', [
  'eyebrow' => 'Accommodation',
  'title'   => $roomType['name'],
  'lede'    => $roomType['summary'],
  'image'   => $roomType['hero_image'] ?? 'img/backgrounds/placeholder.jpg',
  'crumbs'  => ['Accommodation' => '/accommodation', $roomType['name'] => null],
]); ?>

<section class="section">
  <div class="container grid grid--sidebar">
    <div>
      <?php if ($images !== []): ?>
        <div class="room-gallery <?= count($images) > 2 ? 'room-gallery--lead' : (count($images) === 1 ? 'room-gallery--single' : '') ?>">
          <?php foreach ($images as $index => $image): ?>
            <figure class="gallery-item<?= $index === 0 ? ' room-gallery__lead' : '' ?>"
                    data-lightbox="<?= e(uploaded($image['file_path'])) ?>"
                    data-lightbox-alt="<?= e($image['alt_text']) ?>" data-lightbox-caption="<?= e($roomType['name']) ?>">
              <?= picture($image['file_path'], $image['alt_text'], ['loading' => $index === 0 ? 'eager' : 'lazy']) ?>
            </figure>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="prose"><?= $roomType['description'] ?></div>

      <?php if ($amenities !== []): ?>
        <h2 style="margin-top:2rem">What is in the unit</h2>
        <div class="grid grid--3" style="gap:.5rem">
          <?php foreach ($amenities as $amenity): ?>
            <div class="cluster" style="gap:.5rem">
              <span style="color:var(--success)">✓</span><span style="font-size:var(--step--1)"><?= e($amenity) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <h2 style="margin-top:2rem">How this room type is sold</h2>
      <p>Each <?= e($roomType['name']) ?> has <strong><?= (int) $roomType['beds_per_unit'] ?> beds</strong>. Every bed is sold on its own, so booking one bed leaves the others on sale for other attendees. If you would rather not share, choose the whole unit and every bed in that cottage is held for you.</p>

      <div class="table-wrap" style="margin-top:1rem">
        <table>
          <thead><tr><th>Night</th><th>Per bed</th><th>Whole unit</th><th class="numeric">Beds free</th><th class="numeric">Whole units free</th></tr></thead>
          <tbody>
            <?php foreach ($nights as $night): ?>
              <tr>
                <td><strong><?= e(za_date($night, 'D j M Y')) ?></strong><?php if ($rates[$night]['label']): ?><br><span class="muted"><?= e($rates[$night]['label']) ?></span><?php endif; ?></td>
                <td><?= e(money($rates[$night]['bed'])) ?></td>
                <td><?= $rates[$night]['unit'] === null ? '—' : e(money($rates[$night]['unit'])) ?></td>
                <td class="numeric"><?= (int) $availability[$night] ?></td>
                <td class="numeric"><?= (int) $freeUnits[$night] ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- =================================================== booking form -->
    <aside class="summary">
      <?php if (!$isOpen): ?>
        <div class="alert alert--warning" style="margin:0"><div><p>Accommodation booking is closed at the moment.</p></div></div>
      <?php elseif ($totalFree === 0): ?>
        <div class="empty-state" style="padding:1.5rem">
          <div class="empty-state__icon">🛏</div>
          <h3 style="font-size:var(--step-1)">Fully booked</h3>
          <p style="font-size:var(--step--1)">Every bed in this room type is taken. Try another room type — beds are released when unpaid carts expire, so it is worth checking again.</p>
        </div>
      <?php else: ?>
        <h2 style="font-size:var(--step-2);margin-bottom:.25rem">Book your bed</h2>
        <p class="muted" style="font-size:var(--step--1)">Held for <?= (int) $holdMinutes ?> minutes once it is in your cart.</p>

        <form method="post" action="<?= e(url('/accommodation/' . $roomType['slug'] . '/book')) ?>" data-booking-form data-once
              data-track-submit="add_to_cart" data-track-params='{"item_category":"accommodation"}'>
          <?= csrf_field() ?>

          <div class="booking-mode" role="radiogroup" aria-label="Booking type">
            <label>
              <input type="radio" name="mode" value="bed" checked>
              <strong>Beds</strong>
              <span>Book individual beds and share the unit.</span>
            </label>
            <?php if ((int) $roomType['allows_private_buyout'] === 1): ?>
              <label>
                <input type="radio" name="mode" value="unit">
                <strong>Whole unit</strong>
                <span>Reserve all <?= (int) $roomType['beds_per_unit'] ?> beds privately.</span>
              </label>
            <?php endif; ?>
          </div>

          <div class="field">
            <label class="field__label" for="beds">How many beds?</label>
            <select id="beds" name="beds">
              <?php for ($i = 1; $i <= min(4, (int) $roomType['beds_per_unit']); $i++): ?>
                <option value="<?= $i ?>"><?= $i ?> bed<?= $i === 1 ? '' : 's' ?></option>
              <?php endfor; ?>
            </select>
            <p class="field__hint">Ignored when you book the whole unit.</p>
          </div>

          <div class="field">
            <span class="field__label">Which nights?</span>
            <div class="night-picker">
              <?php foreach ($nights as $night):
                $free      = (int) $availability[$night];
                $unitsFree = (int) $freeUnits[$night];
                $sellable  = $rates[$night]['available'] && $free > 0; ?>
                <label class="night-option<?= $sellable ? '' : ' is-unavailable' ?>">
                  <input type="checkbox" name="nights[]" value="<?= e($night) ?>"
                         data-bed-price="<?= (int) $rates[$night]['bed'] ?>"
                         data-unit-price="<?= (int) ($rates[$night]['unit'] ?? $rates[$night]['bed'] * (int) $roomType['beds_per_unit']) ?>"
                         <?= $sellable ? '' : 'disabled' ?>>
                  <span class="night-option__label">
                    <strong><?= e(za_date($night, 'D j M')) ?></strong>
                    <span>
                      <?= $free === 0 ? 'Fully booked' : $free . ' bed' . ($free === 1 ? '' : 's') . ' free' ?>
                      <?php if ($unitsFree > 0): ?> &middot; <?= $unitsFree ?> whole unit<?= $unitsFree === 1 ? '' : 's' ?><?php endif; ?>
                      <?php if ($rates[$night]['label']): ?> &middot; <?= e($rates[$night]['label']) ?><?php endif; ?>
                    </span>
                  </span>
                  <span class="night-option__price" data-night-price><?= e(money($rates[$night]['bed'])) ?></span>
                </label>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="field">
            <label class="field__label" for="guest_name">Guest name</label>
            <input type="text" id="guest_name" name="guest_name" value="<?= e(auth() ? trim(auth()['first_name'] . ' ' . auth()['last_name']) : '') ?>" placeholder="Who is sleeping in this bed?">
          </div>

          <div class="field">
            <label class="field__label" for="roommate_request">Roommate request</label>
            <input type="text" id="roommate_request" name="roommate_request" placeholder="Name of the person you would like to share with">
            <p class="field__hint">We honour requests wherever we can, if you both book the same room type and nights.</p>
          </div>

          <div class="field">
            <label class="field__label" for="accessibility_needs">Accessibility needs</label>
            <input type="text" id="accessibility_needs" name="accessibility_needs" placeholder="Step-free access, ground floor, etc.">
          </div>

          <div class="field">
            <label class="field__label" for="notes">Anything else</label>
            <textarea id="notes" name="notes" rows="3" style="min-height:80px" placeholder="Arrival time, dietary notes, anything the team should know"></textarea>
          </div>

          <div class="summary__row summary__row--total">
            <span>Total</span>
            <span data-booking-total>R0.00</span>
          </div>

          <button class="btn btn--block btn--lg" type="submit" data-busy-label="Holding your bed…">Add to cart</button>
          <p class="muted" style="font-size:var(--step--1);margin-top:.75rem">
            You can add registration, transport and merchandise to the same cart and pay once.
          </p>
        </form>
      <?php endif; ?>
    </aside>
  </div>
</section>

<?php if ($others !== []): ?>
<section class="section section--sunk">
  <div class="container">
    <div class="section-head"><div class="rule"></div><h2>Other room types</h2></div>
    <div class="grid grid--3">
      <?php foreach ($others as $other): ?>
        <a class="card card--link" href="<?= e(url('/accommodation/' . $other['slug'])) ?>">
          <div class="card__media"><?= picture($other['hero_image'] ?? 'img/backgrounds/placeholder.jpg', $other['name']) ?></div>
          <div class="card__body">
            <h3 class="card__title"><?= e($other['name']) ?></h3>
            <p class="card__text"><?= e($other['summary']) ?></p>
            <div class="card__foot">
              <span class="card__price">from <?= e(money((int) $other['bed_rate_cents'])) ?><small>per bed, per night</small></span>
              <span class="link-arrow">View <span>&rarr;</span></span>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<div class="lightbox" id="lightbox" role="dialog" aria-modal="true" aria-label="Image viewer">
  <button class="lightbox__close" type="button" aria-label="Close">&times;</button>
  <div><img src="" alt=""><p class="lightbox__caption"></p></div>
</div>
<?php View::stop(); ?>

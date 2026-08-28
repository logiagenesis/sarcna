<?php
use App\Core\View;
View::layout('layouts.public');
View::start('content');
?>
<?php View::include('partials.page-hero', [
  'eyebrow' => 'Accommodation',
  'title'   => 'Book a bed, not a whole room',
  'lede'    => 'Beds are sold one at a time. Take a single bed in a shared cottage and the rest stay on sale for other attendees — or book a whole unit privately.',
  'image'   => 'img/rooms/retreat-twin-cottage.jpg',
  'crumbs'  => ['Accommodation' => null],
]); ?>

<section class="section">
  <div class="container">
    <?php if (!$isOpen): ?>
      <div class="alert alert--warning">
        <div><div class="alert__title">Accommodation booking is closed</div>
        <p>The committee has paused bookings. Register for the weekend now and check back, or ask us on WhatsApp.</p></div>
      </div>
    <?php endif; ?>

    <div class="grid grid--2" style="margin-bottom:2.5rem">
      <div>
        <div class="rule"></div>
        <h2>How the booking works</h2>
        <ol>
          <li><strong>Choose a room type</strong> and see how many beds are free on each night.</li>
          <li><strong>Pick your nights</strong> — Thursday is an optional early-arrival night.</li>
          <li><strong>Choose one bed, several beds, or the whole unit.</strong> A private unit reserves every bed in that cottage for you.</li>
          <li><strong>Your beds are held while you check out.</strong> Nobody else can take them for 15 minutes.</li>
          <li><strong>Payment confirms the booking</strong> and the bed becomes yours.</li>
        </ol>
      </div>
      <div class="summary" style="position:static">
        <h3 style="font-size:var(--step-1)">Availability right now</h3>
        <?php foreach ($occupancy['by_night'] as $night => $data): ?>
          <div class="summary__row">
            <span><?= e($data['label']) ?></span>
            <strong><?= (int) $data['available'] ?> of <?= (int) $data['total'] ?> beds free</strong>
          </div>
        <?php endforeach; ?>
        <p class="muted" style="font-size:var(--step--1);margin-top:.75rem">Counts include beds currently held in other people&rsquo;s carts, so they update as people check out.</p>
      </div>
    </div>

    <div class="stack-l">
      <?php foreach ($roomTypes as $roomType):
        $totalFree = array_sum($roomType['availability']); ?>
        <article class="card reveal" style="flex-direction:row;flex-wrap:wrap">
          <div class="card__media" style="flex:1 1 340px;aspect-ratio:auto;min-height:260px">
            <?= picture($roomType['hero_image'] ?? 'img/backgrounds/placeholder.jpg', $roomType['name']) ?>
          </div>
          <div class="card__body" style="flex:1 1 420px">
            <div class="cluster">
              <span class="badge"><?= (int) $roomType['beds_per_unit'] ?> beds per unit</span>
              <span class="badge"><?= (int) $roomType['unit_count'] ?> units</span>
              <?php if ((int) $roomType['is_accessible'] === 1): ?><span class="badge badge--success">Step-free access</span><?php endif; ?>
              <?php if ((int) $roomType['is_offsite'] === 1): ?><span class="badge badge--plum">Off the estate</span><?php endif; ?>
              <?php if ($totalFree === 0): ?><span class="badge badge--error">Fully booked</span>
              <?php elseif ($totalFree < 10): ?><span class="badge badge--warning">Only <?= $totalFree ?> bed-nights left</span><?php endif; ?>
            </div>

            <h2 class="card__title" style="font-size:var(--step-2)"><?= e($roomType['name']) ?></h2>
            <p class="card__text" style="font-size:var(--step-0)"><?= e($roomType['summary']) ?></p>

            <div class="availability" style="margin:.75rem 0">
              <?php foreach ($roomType['availability'] as $night => $free): ?>
                <span class="availability__night <?= $free === 0 ? 'is-out' : ($free < 6 ? 'is-low' : '') ?>">
                  <strong><?= e($nightLabels[$night] ?? $night) ?></strong>
                  <?= $free === 0 ? 'Fully booked' : $free . ' bed' . ($free === 1 ? '' : 's') ?>
                  <?php if (isset($roomType['rates'][$night])): ?>
                    <span class="muted"><?= e(money($roomType['rates'][$night]['bed'])) ?></span>
                  <?php endif; ?>
                </span>
              <?php endforeach; ?>
            </div>

            <div class="card__foot">
              <span class="card__price">
                from <?= e(money((int) $roomType['bed_rate_cents'])) ?>
                <small>per bed, per night<?php if ($roomType['private_unit_rate_cents'] !== null): ?> &middot; whole unit from <?= e(money((int) $roomType['private_unit_rate_cents'])) ?><?php endif; ?></small>
              </span>
              <a class="btn <?= $totalFree === 0 ? 'btn--ghost' : '' ?>" href="<?= e(url('/accommodation/' . $roomType['slug'])) ?>">
                <?= $totalFree === 0 ? 'View room type' : 'Choose nights' ?>
              </a>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section--sunk">
  <div class="container grid grid--2">
    <div>
      <div class="rule"></div>
      <h2>Sharing, roommates and accessibility</h2>
      <p><strong>Sharing.</strong> Shared cottages are single-sex by default unless a group books the whole unit. Tell us who you would like to share with when you book and we will do our best.</p>
      <p><strong>Accessibility.</strong> Our Accessible Twin Cottages have step-free access, widened doorways, grab rails and a roll-in shower, and are closest to the conference barn. Describe what you need in the booking notes.</p>
      <p><strong>Not staying over?</strong> Day passes are on sale in the <a href="<?= e(url('/shop/registration')) ?>">shop</a>, and day visitors can book a free parking pass on the <a href="<?= e(url('/transport')) ?>">transport page</a>.</p>
    </div>
    <div>
      <div class="rule"></div>
      <h2>Before you book</h2>
      <ul>
        <li>Check-in from 16:00 on your first night; checkout by 10:00 on the day you leave.</li>
        <li>Linen, towels and heating are included in every room type.</li>
        <li>Cancellation terms follow the venue contract — see the <a href="<?= e(url('/refund-policy')) ?>">refund policy</a> and <a href="<?= e(url('/accommodation-terms')) ?>">accommodation terms</a>.</li>
        <li>August nights in the valley are cold. Bring layers.</li>
      </ul>
      <a class="btn btn--ghost" href="<?= e(url('/faq#accommodation')) ?>">Accommodation questions</a>
    </div>
  </div>
</section>
<?php View::stop(); ?>

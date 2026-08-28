<?php
use App\Core\View;
use App\Services\TransportService;
View::layout('layouts.public');
View::start('content');
?>
<?php View::include('partials.page-hero', [
  'eyebrow' => 'Transport',
  'title'   => 'Getting to Boschendal',
  'lede'    => 'Shuttle seats are sold per passenger and per departure, so every seat has a name on it.',
  'image'   => 'img/transport/winelands-road.jpg',
  'crumbs'  => ['Transport' => null],
]); ?>

<section class="section">
  <div class="container">
    <?php if (!$isOpen): ?>
      <div class="alert alert--warning"><div><div class="alert__title">Transport booking is closed</div><p>Please check back or contact the transport co-ordinator.</p></div></div>
    <?php endif; ?>

    <div class="grid grid--2" style="margin-bottom:2.5rem">
      <div>
        <div class="rule"></div>
        <h2>How the shuttles work</h2>
        <ol>
          <li>Choose your route and the departure time that suits your flight or your day.</li>
          <li>Give us the passenger name, phone number and flight number where it applies.</li>
          <li>Seats are confirmed when your payment clears, and the transport co-ordinator WhatsApps the meeting point the week before.</li>
          <li>Be at the pick-up point 15 minutes early — shuttles run to a schedule and cannot wait.</li>
        </ol>
        <p class="muted" style="font-size:var(--step--1)">See the <a href="<?= e(url('/transport-terms')) ?>">transport terms</a> for luggage, delays and changes.</p>
      </div>
      <div class="summary" style="position:static">
        <h3 style="font-size:var(--step-1)">Seats across all routes</h3>
        <div class="summary__row"><span>Total seats</span><strong><?= (int) $summary['capacity'] ?></strong></div>
        <div class="summary__row"><span>Booked</span><strong><?= (int) $summary['taken'] ?></strong></div>
        <div class="summary__row summary__row--total"><span>Available</span><span><?= (int) $summary['available'] ?></span></div>
        <p class="muted" style="font-size:var(--step--1);margin-top:.75rem">Vehicles are booked in advance, so seats are genuinely limited.</p>
      </div>
    </div>

    <div class="stack-l">
      <?php foreach ($routes as $route):
        $seatsLeft = (int) $route['seats_available']; ?>
        <article class="card reveal">
          <div class="card__body">
            <div class="cluster cluster--between">
              <div class="cluster">
                <span class="badge"><?= e(match ($route['direction']) {
                  'to_venue' => 'To the venue', 'from_venue' => 'From the venue',
                  'return' => 'Return trip', default => 'On site' }) ?></span>
                <?php if ((int) $route['requires_flight_number'] === 1): ?><span class="badge badge--plum">Flight number needed</span><?php endif; ?>
                <?php if ($seatsLeft === 0): ?><span class="badge badge--error">Fully booked</span>
                <?php elseif ($seatsLeft < 8): ?><span class="badge badge--warning">Only <?= $seatsLeft ?> seats left</span><?php endif; ?>
              </div>
              <span class="card__price"><?= (int) $route['price_cents'] === 0 ? 'Free' : e(money((int) $route['price_cents'])) ?></span>
            </div>

            <h2 class="card__title" style="font-size:var(--step-2);margin-top:.5rem"><?= e($route['name']) ?></h2>
            <div class="card__text" style="font-size:var(--step-0)"><?= $route['description'] ?></div>

            <?php if ($route['slots'] !== []): ?>
              <div class="table-wrap" style="margin-top:1rem">
                <table>
                  <thead><tr><th>Departs</th><th>Pick-up</th><th>Drop-off</th><th class="numeric">Seats left</th></tr></thead>
                  <tbody>
                    <?php foreach ($route['slots'] as $slot):
                      $left = max(0, (int) $slot['capacity'] - (int) $slot['seats_taken']); ?>
                      <tr>
                        <td><strong><?= e(za_date((string) $slot['departs_at'], 'D j M, H:i')) ?></strong><?php if ($slot['notes']): ?><br><span class="muted"><?= e($slot['notes']) ?></span><?php endif; ?></td>
                        <td><?= e($slot['pickup_point']) ?></td>
                        <td><?= e($slot['dropoff_point']) ?></td>
                        <td class="numeric"><?= $left === 0 ? '<span class="badge badge--error">Full</span>' : $left ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>

            <div class="card__foot">
              <span class="muted" style="font-size:var(--step--1)"><?= (int) $route['slot_count'] ?> departure<?= (int) $route['slot_count'] === 1 ? '' : 's' ?></span>
              <a class="btn" href="<?= e(url('/transport/' . $route['slug'])) ?>">Book a seat</a>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php View::stop(); ?>

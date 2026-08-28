<?php
use App\Core\View;
View::layout('layouts.public');
View::start('content');
$event = config('event');
?>
<section class="section">
  <div class="container">
    <div class="rule"></div>
    <h1 style="font-size:var(--step-3)">Hello, <?= e($user['first_name']) ?></h1>
    <p class="muted">Everything you have booked for <?= e($event['dates_label']) ?> at <?= e($event['venue_name']) ?>.</p>

    <?php View::include('partials.account-nav'); ?>

    <?php if ($showVerify): ?>
      <div class="alert alert--warning">
        <div style="flex:1">
          <div class="alert__title">Please confirm your email address</div>
          <p>We sent a link to <?= e($user['email']) ?>. Confirmations and your check-in code go to this address.</p>
          <form method="post" action="<?= e(url('/verify-email/resend')) ?>" style="margin-top:.6rem">
            <?= csrf_field() ?>
            <button class="btn btn--sm" type="submit">Send it again</button>
          </form>
        </div>
      </div>
    <?php endif; ?>

    <div class="stat-row" style="margin-bottom:2rem">
      <div class="stat"><div class="stat__value"><?= count($orders) ?></div><div class="stat__label">Recent orders</div></div>
      <div class="stat"><div class="stat__value"><?= count($bookings) ?></div><div class="stat__label">Bed-nights booked</div></div>
      <div class="stat"><div class="stat__value"><?= count($transport) ?></div><div class="stat__label">Shuttle seats</div></div>
      <div class="stat"><div class="stat__value"><?= e(money($spent)) ?></div><div class="stat__label">Paid to date</div></div>
    </div>

    <div class="grid grid--sidebar">
      <div>
        <h2 style="font-size:var(--step-2)">Recent orders</h2>
        <?php if ($orders === []): ?>
          <div class="empty-state">
            <div class="empty-state__icon">🎟</div>
            <h3>Nothing booked yet</h3>
            <p>Start with registration, then add a bed and a shuttle seat.</p>
            <div class="cluster cluster--center" style="margin-top:1.25rem">
              <a class="btn" href="<?= e(url('/shop/registration')) ?>">Register</a>
              <a class="btn btn--ghost" href="<?= e(url('/accommodation')) ?>">Book a bed</a>
            </div>
          </div>
        <?php else: ?>
          <div class="table-wrap">
            <table>
              <thead><tr><th>Reference</th><th>Date</th><th>Status</th><th class="numeric">Total</th><th></th></tr></thead>
              <tbody>
                <?php foreach ($orders as $order): ?>
                  <tr>
                    <td><strong><?= e($order['reference']) ?></strong></td>
                    <td><?= e(za_date((string) $order['created_at'], 'j M Y')) ?></td>
                    <td><?php View::include('partials.order-status', ['status' => $order['status']]); ?></td>
                    <td class="numeric"><?= e(money((int) $order['total_cents'])) ?></td>
                    <td><a class="link-arrow" href="<?= e(url('/account/orders/' . $order['reference'])) ?>">View <span>&rarr;</span></a></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <a class="btn btn--ghost btn--sm" style="margin-top:1rem" href="<?= e(url('/account/orders')) ?>">All orders</a>
        <?php endif; ?>

        <?php if ($bookings !== []): ?>
          <h2 style="font-size:var(--step-2);margin-top:2.5rem">Your accommodation</h2>
          <div class="table-wrap">
            <table>
              <thead><tr><th>Night</th><th>Room type</th><th>Unit &amp; bed</th><th>Reference</th></tr></thead>
              <tbody>
                <?php foreach ($bookings as $booking): ?>
                  <tr>
                    <td><?= e(za_date((string) $booking['night'], 'D j M')) ?></td>
                    <td><?= e($booking['room_type_name']) ?><?= (int) $booking['is_private_unit'] === 1 ? ' <span class="badge badge--gold">Private</span>' : '' ?></td>
                    <td><?= e($booking['unit_name']) ?> &middot; <?= e($booking['bed_label']) ?></td>
                    <td><?= e($booking['reference']) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>

        <?php if ($transport !== []): ?>
          <h2 style="font-size:var(--step-2);margin-top:2.5rem">Your transport</h2>
          <div class="table-wrap">
            <table>
              <thead><tr><th>Departs</th><th>Route</th><th>Passenger</th><th>Reference</th></tr></thead>
              <tbody>
                <?php foreach ($transport as $trip): ?>
                  <tr>
                    <td><?= e(za_date((string) $trip['departs_at'], 'D j M, H:i')) ?></td>
                    <td><?= e($trip['route_name']) ?></td>
                    <td><?= e($trip['passenger_name']) ?></td>
                    <td><?= e($trip['reference']) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

      <aside class="summary">
        <h3 style="font-size:var(--step-1)">Still to book?</h3>
        <a class="btn btn--block btn--sm" href="<?= e(url('/shop/registration')) ?>">Registration</a>
        <a class="btn btn--ghost btn--block btn--sm" style="margin-top:.5rem" href="<?= e(url('/accommodation')) ?>">Accommodation</a>
        <a class="btn btn--ghost btn--block btn--sm" style="margin-top:.5rem" href="<?= e(url('/transport')) ?>">Transport</a>
        <a class="btn btn--ghost btn--block btn--sm" style="margin-top:.5rem" href="<?= e(url('/shop/merchandise')) ?>">Merchandise</a>
        <hr>
        <h3 style="font-size:var(--step-1)">The weekend</h3>
        <div class="summary__row"><span>Dates</span><strong><?= e($event['dates_label']) ?></strong></div>
        <div class="summary__row"><span>Check-in from</span><strong>Fri 16:00</strong></div>
        <div class="summary__row"><span>Checkout by</span><strong>Sun 10:00</strong></div>
        <a class="btn btn--ghost btn--block btn--sm" style="margin-top:1rem" href="<?= e(url('/programme')) ?>">Programme</a>
      </aside>
    </div>
  </div>
</section>
<?php View::stop(); ?>

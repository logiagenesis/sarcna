<?php
use App\Core\View;
View::layout('layouts.public');
View::start('content');
?>
<section class="section">
  <div class="container container--narrow">
    <a class="link-arrow" href="<?= e(url('/account/orders')) ?>">&larr; All orders</a>

    <div class="receipt" style="margin-top:1.5rem">
      <div class="receipt__head">
        <div>
          <span class="eyebrow">Order</span>
          <div class="receipt__ref"><?= e($order['reference']) ?></div>
          <p class="muted" style="font-size:var(--step--1)">Placed <?= e(za_date((string) $order['created_at'], 'j F Y, H:i')) ?></p>
          <?php View::include('partials.order-status', ['status' => $order['status']]); ?>
        </div>
        <?php if ($order['status'] === 'paid'): ?>
          <div style="text-align:right">
            <span class="eyebrow">Check-in code</span>
            <div class="receipt__ref"><?= e($order['checkin_code']) ?></div>
          </div>
        <?php endif; ?>
      </div>

      <div class="table-wrap">
        <table>
          <tbody>
            <?php foreach ($items as $item): ?>
              <tr>
                <td><?= e($item['description']) ?><?= (int) $item['quantity'] > 1 ? ' × ' . (int) $item['quantity'] : '' ?></td>
                <td class="numeric"><?= e(money((int) $item['total_cents'])) ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if ((int) $order['discount_cents'] > 0): ?>
              <tr><td>Discount <?= $order['coupon_code'] ? '(' . e($order['coupon_code']) . ')' : '' ?></td>
                  <td class="numeric">&minus;<?= e(money((int) $order['discount_cents'])) ?></td></tr>
            <?php endif; ?>
            <tr><th>Total</th><th class="numeric"><?= e(money((int) $order['total_cents'])) ?></th></tr>
          </tbody>
        </table>
      </div>

      <?php if ($bookings !== []): ?>
        <h2 style="font-size:var(--step-1);margin-top:2rem">Accommodation</h2>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Night</th><th>Room</th><th>Unit &amp; bed</th><th>Guest</th><th>Reference</th></tr></thead>
            <tbody>
              <?php foreach ($bookings as $booking): ?>
                <tr>
                  <td><?= e(za_date((string) $booking['night'], 'D j M')) ?></td>
                  <td><?= e($booking['room_type_name']) ?></td>
                  <td><?= e($booking['unit_name']) ?> &middot; <?= e($booking['bed_label']) ?></td>
                  <td><?= e($booking['guest_name']) ?></td>
                  <td><?= e($booking['reference']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

      <?php if ($transportBookings !== []): ?>
        <h2 style="font-size:var(--step-1);margin-top:2rem">Transport</h2>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Departs</th><th>Route</th><th>Passenger</th><th>Reference</th></tr></thead>
            <tbody>
              <?php foreach ($transportBookings as $trip): ?>
                <tr>
                  <td><?= e(za_date((string) $trip['departs_at'], 'D j M, H:i')) ?></td>
                  <td><?= e($trip['route_name']) ?><br><span class="muted"><?= e($trip['pickup_point']) ?> &rarr; <?= e($trip['dropoff_point']) ?></span></td>
                  <td><?= e($trip['passenger_name']) ?></td>
                  <td><?= e($trip['reference']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

      <div class="cluster" style="margin-top:2rem">
        <a class="btn btn--ghost" href="<?= e(url('/account/invoice/' . $order['reference'])) ?>" target="_blank">View invoice</a>
        <?php if ($order['status'] === 'pending_payment'): ?>
          <a class="btn" href="<?= e(url('/checkout/pay/' . $order['reference'])) ?>">Complete payment</a>
        <?php endif; ?>
        <a class="btn btn--ghost" href="<?= e(url('/contact')) ?>">Query this order</a>
      </div>
    </div>
  </div>
</section>
<?php View::stop(); ?>

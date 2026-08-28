<?php
use App\Core\View;
View::layout('layouts.public');
View::start('content');
$paid = $order !== null && $order['status'] === 'paid';
?>
<section class="section">
  <div class="container container--narrow">
    <ol class="steps">
      <li class="is-done">Cart</li><li class="is-done">Details</li><li class="is-done">Payment</li><li class="is-current">Confirmed</li>
    </ol>

    <?php if ($order === null): ?>
      <div class="empty-state">
        <div class="empty-state__icon">🔍</div>
        <h3>We could not find that order</h3>
        <p>If you have just paid, the confirmation email will arrive shortly. Check your account for the order, or contact the committee with your PayFast reference.</p>
        <div class="cluster cluster--center" style="margin-top:1.5rem">
          <a class="btn" href="<?= e(url('/account/orders')) ?>">My orders</a>
          <a class="btn btn--ghost" href="<?= e(url('/contact')) ?>">Contact us</a>
        </div>
      </div>

    <?php elseif (!$paid): ?>
      <div class="success-hero">
        <div class="success-hero__mark" style="background:rgba(224,161,47,.16);border-color:var(--warning);color:var(--warning)">⏳</div>
        <h1>Thank you — we are confirming your payment</h1>
        <p class="muted" style="margin-inline:auto">
          Order <strong><?= e($order['reference']) ?></strong> is with PayFast. Confirmations arrive by email as soon as PayFast
          tells us the payment succeeded, usually within a minute or two.
        </p>
        <div class="alert alert--info" style="text-align:left;margin-top:1.5rem">
          <div><div class="alert__title">Why the wait?</div>
          <p>We never mark an order as paid just because you landed on this page — only PayFast&rsquo;s own notification does that. It is slower by a minute, and it is the reason nobody can fake a booking.</p></div>
        </div>
        <div class="cluster cluster--center" style="margin-top:1.5rem">
          <a class="btn" href="<?= e(url('/account/orders')) ?>">Check my orders</a>
          <a class="btn btn--ghost" href="<?= e(url('/')) ?>">Back to the home page</a>
        </div>
      </div>

    <?php else: ?>
      <div class="success-hero">
        <div class="success-hero__mark">✓</div>
        <h1>You are booked</h1>
        <p class="muted" style="margin-inline:auto">A confirmation is on its way to <strong><?= e($order['email']) ?></strong>.</p>
      </div>

      <div class="receipt">
        <div class="receipt__head">
          <div>
            <span class="eyebrow">Order</span>
            <div class="receipt__ref"><?= e($order['reference']) ?></div>
            <p class="muted" style="font-size:var(--step--1)"><?= e(za_date((string) $order['paid_at'], 'j F Y, H:i')) ?></p>
          </div>
          <div style="text-align:right">
            <span class="eyebrow">Check-in code</span>
            <div class="receipt__ref"><?= e($order['checkin_code']) ?></div>
            <p class="muted" style="font-size:var(--step--1)">Bring this to the registration desk</p>
          </div>
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
              <tr><th>Paid</th><th class="numeric"><?= e(money((int) $order['total_cents'])) ?></th></tr>
            </tbody>
          </table>
        </div>

        <?php if ($bookings !== []): ?>
          <h2 style="font-size:var(--step-2);margin-top:2rem">Your accommodation</h2>
          <div class="table-wrap">
            <table>
              <thead><tr><th>Night</th><th>Room</th><th>Unit &amp; bed</th><th>Reference</th></tr></thead>
              <tbody>
                <?php foreach ($bookings as $booking): ?>
                  <tr>
                    <td><?= e(za_date((string) $booking['night'], 'D j M Y')) ?></td>
                    <td><?= e($booking['room_type_name']) ?><?= (int) $booking['is_private_unit'] === 1 ? ' <span class="badge badge--gold">Private unit</span>' : '' ?></td>
                    <td><?= e($booking['unit_name']) ?> &middot; <?= e($booking['bed_label']) ?></td>
                    <td><?= e($booking['reference']) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>

        <?php if ($transportBookings !== []): ?>
          <h2 style="font-size:var(--step-2);margin-top:2rem">Your transport</h2>
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
      </div>

      <div class="cluster cluster--center" style="margin-top:2rem">
        <a class="btn" href="<?= e(url('/account/orders/' . $order['reference'])) ?>">View in my account</a>
        <a class="btn btn--ghost" href="<?= e(url('/programme')) ?>">See the programme</a>
        <a class="btn btn--ghost" href="<?= e(url('/venue')) ?>">Plan your trip</a>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php View::stop(); ?>

<?php if ($order !== null && $order['status'] === 'paid'): ?>
<?php View::start('scripts'); ?>
<script>
  if (window.sarcnaTrack) {
    window.sarcnaTrack('purchase', {
      transaction_id: <?= json_encode($order['reference']) ?>,
      value: <?= (int) $order['total_cents'] / 100 ?>,
      currency: 'ZAR'
    });
  }
</script>
<?php View::stop(); ?>
<?php endif; ?>

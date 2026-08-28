<?php
use App\Core\View;
use App\Services\CartService;
View::layout('layouts.admin');
View::start('content');
?>
<div class="admin-head">
  <div>
    <a class="link-arrow" href="<?= e(url('/admin/orders')) ?>">&larr; Orders</a>
    <h1 style="margin-top:.5rem">Order <?= e($order['reference']) ?></h1>
    <p><?= e(za_date((string) $order['created_at'], 'j F Y, H:i')) ?> &middot; <?php View::include('partials.order-status', ['status' => $order['status']]); ?></p>
  </div>
  <div class="cluster">
    <form method="post" action="<?= e(url('/admin/orders/' . $order['id'] . '/resend')) ?>">
      <?= csrf_field() ?>
      <button class="btn btn--sm btn--ghost" type="submit">Resend confirmation</button>
    </form>
  </div>
</div>

<?php if (str_starts_with((string) $order['admin_note'], 'NEEDS ATTENTION')): ?>
  <div class="alert alert--error"><div><div class="alert__title">This order needs a human</div><p><?= e($order['admin_note']) ?></p></div></div>
<?php endif; ?>

<div class="admin-grid admin-grid--sidebar">
  <div>
    <div class="admin-panel">
      <h2>Items</h2>
      <div class="table-wrap">
        <table class="admin-table">
          <thead><tr><th>Type</th><th>Description</th><th class="numeric">Qty</th><th class="numeric">Unit</th><th class="numeric">Total</th></tr></thead>
          <tbody>
            <?php foreach ($items as $item): $meta = CartService::decodeMeta($item['meta']); ?>
              <tr>
                <td><span class="badge"><?= e($item['item_type']) ?></span></td>
                <td>
                  <?= e($item['description']) ?>
                  <?php if ($meta !== []): ?>
                    <br><span class="muted" style="font-size:.75rem">
                      <?php foreach ($meta as $key => $value):
                        if ($value === '' || $value === null || is_array($value)) continue; ?>
                        <?= e(str_replace('_', ' ', (string) $key)) ?>: <?= e((string) $value) ?><br>
                      <?php endforeach; ?>
                    </span>
                  <?php endif; ?>
                </td>
                <td class="numeric"><?= (int) $item['quantity'] ?></td>
                <td class="numeric"><?= e(money((int) $item['unit_price_cents'])) ?></td>
                <td class="numeric"><?= e(money((int) $item['total_cents'])) ?></td>
              </tr>
            <?php endforeach; ?>
            <tr><td colspan="4">Subtotal</td><td class="numeric"><?= e(money((int) $order['subtotal_cents'])) ?></td></tr>
            <?php if ((int) $order['discount_cents'] > 0): ?>
              <tr><td colspan="4">Discount <?= $order['coupon_code'] ? '(' . e($order['coupon_code']) . ')' : '' ?></td>
                  <td class="numeric">&minus;<?= e(money((int) $order['discount_cents'])) ?></td></tr>
            <?php endif; ?>
            <tr><th colspan="4">Total</th><th class="numeric"><?= e(money((int) $order['total_cents'])) ?></th></tr>
          </tbody>
        </table>
      </div>
    </div>

    <?php if ($bookings !== []): ?>
      <div class="admin-panel">
        <h2>Accommodation</h2>
        <div class="table-wrap">
          <table class="admin-table">
            <thead><tr><th>Night</th><th>Room</th><th>Unit &amp; bed</th><th>Guest</th><th>Status</th></tr></thead>
            <tbody>
              <?php foreach ($bookings as $booking): ?>
                <tr>
                  <td><?= e(za_date((string) $booking['night'], 'D j M')) ?></td>
                  <td><?= e($booking['room_type_name']) ?></td>
                  <td><?= e($booking['unit_name']) ?> &middot; <?= e($booking['bed_label']) ?></td>
                  <td><?= e((string) $booking['guest_name']) ?>
                      <?php if ($booking['roommate_request']): ?><br><span class="muted">Wants: <?= e($booking['roommate_request']) ?></span><?php endif; ?>
                      <?php if ($booking['accessibility_needs']): ?><br><span class="badge badge--warning">Access: <?= e($booking['accessibility_needs']) ?></span><?php endif; ?></td>
                  <td>
                    <form method="post" action="<?= e(url('/admin/bookings/' . $booking['id'] . '/status')) ?>" class="inline-form">
                      <?= csrf_field() ?>
                      <select name="status" onchange="this.form.submit()">
                        <?php foreach (['confirmed', 'checked_in', 'cancelled', 'refunded'] as $option): ?>
                          <option value="<?= $option ?>" <?= $booking['status'] === $option ? 'selected' : '' ?>><?= e(ucfirst(str_replace('_', ' ', $option))) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($transportBookings !== []): ?>
      <div class="admin-panel">
        <h2>Transport</h2>
        <div class="table-wrap">
          <table class="admin-table">
            <thead><tr><th>Departs</th><th>Route</th><th>Passenger</th><th>Flight</th><th>Status</th></tr></thead>
            <tbody>
              <?php foreach ($transportBookings as $trip): ?>
                <tr>
                  <td><?= e(za_date((string) $trip['departs_at'], 'D j M, H:i')) ?></td>
                  <td><?= e($trip['route_name']) ?></td>
                  <td><?= e($trip['passenger_name']) ?><br><span class="muted"><?= e($trip['phone']) ?></span></td>
                  <td><?= e((string) $trip['flight_number']) ?></td>
                  <td><span class="badge <?= $trip['status'] === 'confirmed' ? 'badge--success' : '' ?>"><?= e($trip['status']) ?></span></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>

    <div class="admin-panel">
      <h2>Payment history</h2>
      <div class="table-wrap">
        <table class="admin-table">
          <thead><tr><th>When</th><th>Provider ref</th><th class="numeric">Amount</th><th class="numeric">Fee</th><th>Status</th><th>Signature</th><th>Source</th></tr></thead>
          <tbody>
            <?php foreach ($payments as $payment): ?>
              <tr>
                <td><?= e(za_date((string) $payment['created_at'], 'j M, H:i')) ?></td>
                <td><?= e((string) $payment['provider_reference']) ?></td>
                <td class="numeric"><?= e(money((int) $payment['amount_cents'])) ?></td>
                <td class="numeric"><?= e(money((int) $payment['fee_cents'])) ?></td>
                <td><span class="badge <?= $payment['status'] === 'complete' ? 'badge--success' : ($payment['status'] === 'initiated' ? 'badge--warning' : 'badge--error') ?>"><?= e($payment['status']) ?></span></td>
                <td><?= (int) $payment['signature_valid'] === 1 ? '<span class="badge badge--success">Valid</span>' : '<span class="badge badge--error">Invalid</span>' ?></td>
                <td><?= e((string) $payment['source_ip']) ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if ($payments === []): ?><tr><td colspan="7" class="muted">No payment attempts recorded.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="admin-panel">
      <h2>Event log</h2>
      <?php foreach ($logs as $log): ?>
        <p style="font-size:var(--step--1);margin-bottom:.35rem">
          <span class="badge"><?= e($log['event']) ?></span>
          <?= e((string) $log['message']) ?>
          <span class="muted"><?= e(za_date((string) $log['created_at'], 'j M, H:i:s')) ?></span>
        </p>
      <?php endforeach; ?>
      <?php if ($logs === []): ?><p class="muted">Nothing logged yet.</p><?php endif; ?>
    </div>
  </div>

  <div>
    <div class="admin-panel">
      <h2>Customer</h2>
      <p><strong><?= e(trim(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? ''))) ?></strong><br>
         <a href="mailto:<?= e($order['email']) ?>"><?= e($order['email']) ?></a><br>
         <?= e((string) $order['phone']) ?></p>
      <?php if ($customer !== null): ?>
        <a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/customers/' . $customer['id'])) ?>">Open customer record</a>
      <?php else: ?>
        <p class="muted" style="font-size:var(--step--1)">Guest checkout — no account attached.</p>
      <?php endif; ?>
      <?php if ($order['checkin_code']): ?>
        <p style="margin-top:1rem"><span class="eyebrow">Check-in code</span><br>
          <strong style="font-family:var(--font-display);font-size:var(--step-1)"><?= e($order['checkin_code']) ?></strong></p>
      <?php endif; ?>
      <?php if ($order['customer_note']): ?>
        <p style="margin-top:1rem"><span class="eyebrow">Customer note</span><br><?= nl2br(e($order['customer_note'])) ?></p>
      <?php endif; ?>
    </div>

    <div class="admin-panel">
      <h2>Change status</h2>
      <div class="admin-note">
        Marking an order <strong>paid</strong> here runs the same fulfilment as a PayFast notification: it allocates beds,
        writes passenger records and reduces stock. Use it only for payments taken outside the website.
      </div>
      <form method="post" action="<?= e(url('/admin/orders/' . $order['id'] . '/status')) ?>"
            data-confirm="Change the status of this order? This runs the matching fulfilment or release.">
        <?= csrf_field() ?>
        <div class="field">
          <select name="status">
            <?php foreach (['paid' => 'Paid (fulfil now)', 'failed' => 'Failed', 'cancelled' => 'Cancelled', 'refunded' => 'Refunded'] as $value => $label): ?>
              <option value="<?= $value ?>" <?= $order['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button class="btn btn--block btn--sm" type="submit">Apply status</button>
      </form>
    </div>

    <div class="admin-panel">
      <h2>Internal note</h2>
      <form method="post" action="<?= e(url('/admin/orders/' . $order['id'] . '/note')) ?>">
        <?= csrf_field() ?>
        <div class="field">
          <textarea name="admin_note" rows="4" style="min-height:100px"><?= e((string) $order['admin_note']) ?></textarea>
        </div>
        <button class="btn btn--block btn--sm btn--ghost" type="submit">Save note</button>
      </form>
    </div>
  </div>
</div>
<?php View::stop(); ?>

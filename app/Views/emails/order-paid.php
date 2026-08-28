<?php /** @var array $order @var array $items @var array $bookings @var array $transportBookings */ ?>
<h2 style="font-family:Georgia,serif;color:#173D2F;margin:0 0 12px;">Payment received — you are booked</h2>
<p>Thank you, <?= e($order['first_name'] ?: 'friend') ?>. We have received your payment for order <strong><?= e($order['reference']) ?></strong>.</p>
<p style="background:#F3E8D3;border-left:4px solid #D9A441;padding:12px 16px;margin:20px 0;">
  <strong>Your check-in code:</strong> <span style="font-family:monospace;font-size:16px;"><?= e($order['checkin_code']) ?></span><br>
  <span style="font-size:13px;color:#6b7d74;">Bring this code (or your ID) to the registration desk.</span>
</p>
<?php require __DIR__ . '/_items-table.php'; ?>

<?php if ($bookings !== []): ?>
<h3 style="font-family:Georgia,serif;color:#173D2F;margin:28px 0 8px;">Your accommodation</h3>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;border-collapse:collapse;">
  <?php foreach ($bookings as $booking): ?>
  <tr>
    <td style="padding:8px 0;border-bottom:1px solid #eee;">
      <strong><?= e($booking['room_type_name']) ?></strong><br>
      <span style="color:#6b7d74;"><?= e($booking['unit_name']) ?> &middot; <?= e($booking['bed_label']) ?> &middot; <?= e(za_date($booking['night'], 'D j M Y')) ?><?= (int) $booking['is_private_unit'] === 1 ? ' &middot; private unit' : '' ?></span>
    </td>
    <td align="right" style="padding:8px 0;border-bottom:1px solid #eee;white-space:nowrap;"><?= e($booking['reference']) ?></td>
  </tr>
  <?php endforeach; ?>
</table>
<?php endif; ?>

<?php if ($transportBookings !== []): ?>
<h3 style="font-family:Georgia,serif;color:#173D2F;margin:28px 0 8px;">Your transport</h3>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;border-collapse:collapse;">
  <?php foreach ($transportBookings as $trip): ?>
  <tr>
    <td style="padding:8px 0;border-bottom:1px solid #eee;">
      <strong><?= e($trip['route_name']) ?></strong><br>
      <span style="color:#6b7d74;"><?= e(za_date($trip['departs_at'], 'D j M, H:i')) ?> &middot; <?= e($trip['pickup_point']) ?> &rarr; <?= e($trip['dropoff_point']) ?><br>Passenger: <?= e($trip['passenger_name']) ?></span>
    </td>
    <td align="right" style="padding:8px 0;border-bottom:1px solid #eee;white-space:nowrap;"><?= e($trip['reference']) ?></td>
  </tr>
  <?php endforeach; ?>
</table>
<?php endif; ?>

<p style="text-align:center;margin:28px 0;">
  <a href="<?= e(url('/account/orders/' . $order['reference'])) ?>" style="display:inline-block;background:#D9A441;color:#0E241C;padding:14px 28px;border-radius:999px;text-decoration:none;font-weight:bold;">View my booking</a>
</p>

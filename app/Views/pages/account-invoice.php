<?php
use App\Services\SettingsService;
$event = config('event');
?><!DOCTYPE html>
<html lang="en-ZA">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Invoice <?= e($order['reference']) ?> — SARCNA 2027 Convention</title>
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
<style>@media print { .no-print { display:none !important; } body { background:#fff; } }</style>
</head>
<body>
<div class="container container--narrow" style="padding-block:2.5rem">
  <div class="cluster cluster--between no-print" style="margin-bottom:1.5rem">
    <a class="link-arrow" href="<?= e(url('/account/orders/' . $order['reference'])) ?>">&larr; Back to the order</a>
    <button class="btn btn--sm" type="button" onclick="window.print()">Print or save as PDF</button>
  </div>

  <div class="receipt">
    <div class="receipt__head">
      <div>
        <img src="<?= e(asset('brand/logo.svg')) ?>" alt="SARCNA 2027 Convention" style="height:60px;margin-bottom:.75rem">
        <p class="muted" style="font-size:var(--step--1)">
          <?= e($event['venue_name']) ?><br><?= e($event['venue_region']) ?><br>
          <?= e(SettingsService::get('contact_email', config('contact.email'))) ?>
        </p>
      </div>
      <div style="text-align:right">
        <span class="eyebrow">Invoice</span>
        <div class="receipt__ref"><?= e($order['reference']) ?></div>
        <p class="muted" style="font-size:var(--step--1)">
          <?= e(za_date((string) $order['created_at'], 'j F Y')) ?><br>
          Status: <?= e(ucfirst(str_replace('_', ' ', (string) $order['status']))) ?>
        </p>
      </div>
    </div>

    <p><strong>Billed to</strong><br>
      <?= e(trim($order['first_name'] . ' ' . $order['last_name'])) ?><br>
      <?= e($order['email']) ?><?= $order['phone'] ? '<br>' . e($order['phone']) : '' ?>
    </p>

    <div class="table-wrap" style="margin-top:1.5rem">
      <table>
        <thead><tr><th>Description</th><th class="numeric">Qty</th><th class="numeric">Unit</th><th class="numeric">Total</th></tr></thead>
        <tbody>
          <?php foreach ($items as $item): ?>
            <tr>
              <td><?= e($item['description']) ?></td>
              <td class="numeric"><?= (int) $item['quantity'] ?></td>
              <td class="numeric"><?= e(money((int) $item['unit_price_cents'])) ?></td>
              <td class="numeric"><?= e(money((int) $item['total_cents'])) ?></td>
            </tr>
          <?php endforeach; ?>
          <tr><td colspan="3">Subtotal</td><td class="numeric"><?= e(money((int) $order['subtotal_cents'])) ?></td></tr>
          <?php if ((int) $order['discount_cents'] > 0): ?>
            <tr><td colspan="3">Discount <?= $order['coupon_code'] ? '(' . e($order['coupon_code']) . ')' : '' ?></td>
                <td class="numeric">&minus;<?= e(money((int) $order['discount_cents'])) ?></td></tr>
          <?php endif; ?>
          <tr><th colspan="3">Total (<?= e($order['currency']) ?>)</th><th class="numeric"><?= e(money((int) $order['total_cents'])) ?></th></tr>
        </tbody>
      </table>
    </div>

    <?php if ($bookings !== []): ?>
      <h2 style="font-size:var(--step-1);margin-top:1.5rem">Accommodation allocated</h2>
      <ul style="font-size:var(--step--1)">
        <?php foreach ($bookings as $booking): ?>
          <li><?= e(za_date((string) $booking['night'], 'D j M Y')) ?> — <?= e($booking['room_type_name']) ?>, <?= e($booking['unit_name']) ?>, <?= e($booking['bed_label']) ?> (<?= e($booking['reference']) ?>)</li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <?php if ($transportBookings !== []): ?>
      <h2 style="font-size:var(--step-1);margin-top:1.5rem">Transport booked</h2>
      <ul style="font-size:var(--step--1)">
        <?php foreach ($transportBookings as $trip): ?>
          <li><?= e(za_date((string) $trip['departs_at'], 'D j M Y, H:i')) ?> — <?= e($trip['route_name']) ?>, <?= e($trip['passenger_name']) ?> (<?= e($trip['reference']) ?>)</li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <p class="muted" style="font-size:var(--step--1);margin-top:2rem">
      Payment processed by PayFast<?= $order['paid_at'] ? ' on ' . e(za_date((string) $order['paid_at'], 'j F Y')) : '' ?>.
      This document is a record of your convention booking. The SARCNA 2027 Convention is a self-supporting, not-for-profit event.
    </p>
  </div>
</div>
</body>
</html>

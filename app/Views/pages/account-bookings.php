<?php
use App\Core\View;
View::layout('layouts.public');
View::start('content');
?>
<section class="section">
  <div class="container">
    <div class="rule"></div>
    <h1 style="font-size:var(--step-3)">Accommodation bookings</h1>
    <?php View::include('partials.account-nav'); ?>

    <?php if ($bookings === []): ?>
      <div class="empty-state"><div class="empty-state__icon">🛏</div><h3>No beds booked yet</h3>
      <p>Beds are sold one at a time, so you can book just one for yourself.</p>
      <a class="btn" style="margin-top:1.25rem" href="<?= e(url('/accommodation')) ?>">Browse room types</a></div>
    <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Night</th><th>Room type</th><th>Unit &amp; bed</th><th>Guest</th><th>Status</th><th>Reference</th></tr></thead>
          <tbody>
            <?php foreach ($bookings as $booking): ?>
              <tr>
                <td><strong><?= e(za_date((string) $booking['night'], 'D j M Y')) ?></strong></td>
                <td><a href="<?= e(url('/accommodation/' . $booking['room_type_slug'])) ?>"><?= e($booking['room_type_name']) ?></a>
                    <?= (int) $booking['is_private_unit'] === 1 ? ' <span class="badge badge--gold">Private unit</span>' : '' ?></td>
                <td><?= e($booking['unit_name']) ?> &middot; <?= e($booking['bed_label']) ?></td>
                <td><?= e($booking['guest_name']) ?><?php if ($booking['roommate_request']): ?><br><span class="muted">Requested: <?= e($booking['roommate_request']) ?></span><?php endif; ?></td>
                <td><span class="badge <?= $booking['status'] === 'confirmed' ? 'badge--success' : '' ?>"><?= e(ucfirst(str_replace('_', ' ', (string) $booking['status']))) ?></span></td>
                <td><?= e($booking['reference']) ?><br><span class="muted"><?= e((string) $booking['order_reference']) ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <p class="muted" style="margin-top:1rem;font-size:var(--step--1)">
        Need to change a booking? <a href="<?= e(url('/contact')) ?>">Contact the accommodation team</a> — see the <a href="<?= e(url('/refund-policy')) ?>">refund policy</a> first.
      </p>
    <?php endif; ?>
  </div>
</section>
<?php View::stop(); ?>

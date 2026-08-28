<?php
use App\Core\View;
View::layout('layouts.public');
View::start('content');
?>
<section class="section">
  <div class="container">
    <div class="rule"></div>
    <h1 style="font-size:var(--step-3)">Transport bookings</h1>
    <?php View::include('partials.account-nav'); ?>

    <?php if ($bookings === []): ?>
      <div class="empty-state"><div class="empty-state__icon">🚐</div><h3>No shuttle seats booked</h3>
      <p>Shuttles run from the airport, the city centre, Stellenbosch and around the Winelands.</p>
      <a class="btn" style="margin-top:1.25rem" href="<?= e(url('/transport')) ?>">See the routes</a></div>
    <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Departs</th><th>Route</th><th>Pick-up &rarr; drop-off</th><th>Passenger</th><th>Status</th><th>Reference</th></tr></thead>
          <tbody>
            <?php foreach ($bookings as $trip): ?>
              <tr>
                <td><strong><?= e(za_date((string) $trip['departs_at'], 'D j M, H:i')) ?></strong></td>
                <td><?= e($trip['route_name']) ?></td>
                <td><?= e($trip['pickup_point']) ?><br>&rarr; <?= e($trip['dropoff_point']) ?></td>
                <td><?= e($trip['passenger_name']) ?><?php if ($trip['flight_number']): ?><br><span class="muted">Flight <?= e($trip['flight_number']) ?></span><?php endif; ?></td>
                <td><span class="badge <?= $trip['status'] === 'confirmed' ? 'badge--success' : '' ?>"><?= e(ucfirst(str_replace('_', ' ', (string) $trip['status']))) ?></span></td>
                <td><?= e($trip['reference']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="alert alert--info" style="margin-top:1.5rem">
        <div><div class="alert__title">On the day</div>
        <p>Be at the pick-up point 15 minutes before departure. The transport co-ordinator WhatsApps the exact meeting point the week before.</p></div>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php View::stop(); ?>

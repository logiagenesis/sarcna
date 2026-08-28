<?php
use App\Core\View;
View::layout('layouts.admin');
View::start('content');
?>
<div class="admin-head">
  <div>
    <a class="link-arrow" href="<?= e(url('/admin/transport')) ?>">&larr; Transport</a>
    <h1 style="margin-top:.5rem">Passenger manifest</h1>
    <p><?= e($slot['route_name']) ?> &middot; <?= e(za_date((string) $slot['departs_at'], 'l j F Y, H:i')) ?></p>
    <p><?= e($slot['pickup_point']) ?> &rarr; <?= e($slot['dropoff_point']) ?> &middot; <?= count($passengers) ?> of <?= (int) $slot['capacity'] ?> seats</p>
  </div>
  <div class="cluster">
    <a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/export/transport')) ?>">Export all transport</a>
    <button class="btn btn--sm" type="button" onclick="window.print()">Print</button>
  </div>
</div>

<div class="admin-panel" style="padding:0">
  <div class="table-wrap" style="border:0">
    <table class="admin-table">
      <thead><tr><th>#</th><th>Passenger</th><th>Contact</th><th>Flight</th><th class="numeric">Bags</th><th>Notes</th><th>Order</th><th>Check in</th></tr></thead>
      <tbody>
        <?php foreach ($passengers as $index => $passenger): ?>
          <tr>
            <td><?= $index + 1 ?></td>
            <td><strong><?= e($passenger['passenger_name']) ?></strong><br><span class="muted"><?= e($passenger['reference']) ?></span></td>
            <td><?= e($passenger['phone']) ?><br><span class="muted"><?= e($passenger['email']) ?></span></td>
            <td><?= e((string) $passenger['flight_number']) ?></td>
            <td class="numeric"><?= (int) $passenger['luggage_count'] ?></td>
            <td>
              <?php if ($passenger['accessibility_needs']): ?><span class="badge badge--warning"><?= e($passenger['accessibility_needs']) ?></span><br><?php endif; ?>
              <?= e((string) $passenger['notes']) ?>
            </td>
            <td><?php if ($passenger['order_id']): ?><a href="<?= e(url('/admin/orders/' . $passenger['order_id'])) ?>"><?= e((string) $passenger['order_reference']) ?></a><?php endif; ?></td>
            <td>
              <form method="post" action="<?= e(url('/admin/transport/passenger/' . $passenger['id'] . '/checkin')) ?>">
                <?= csrf_field() ?>
                <button class="btn btn--sm <?= $passenger['checked_in_at'] ? 'btn--ghost' : '' ?>" type="submit">
                  <?= $passenger['checked_in_at'] ? 'Undo' : 'Aboard' ?>
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if ($passengers === []): ?><tr><td colspan="8" class="muted">No passengers booked on this departure yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php View::stop(); ?>

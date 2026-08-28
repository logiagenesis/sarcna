<?php
use App\Core\View;
View::layout('layouts.admin');
View::start('content');
?>
<div class="admin-head">
  <div>
    <h1>Live bed holds</h1>
    <p>Beds currently reserved in someone's cart. Holds last <?= (int) $holdMinutes ?> minutes and release themselves.</p>
  </div>
  <a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/bookings')) ?>">Bookings</a>
</div>

<div class="admin-panel" style="padding:0">
  <div class="table-wrap" style="border:0">
    <table class="admin-table">
      <thead><tr><th>Night</th><th>Room type</th><th>Unit &amp; bed</th><th>Held by</th><th>Expires</th><th class="numeric">Price</th></tr></thead>
      <tbody>
        <?php foreach ($holds as $hold): ?>
          <tr>
            <td><?= e(za_date((string) $hold['night'], 'D j M')) ?></td>
            <td><?= e($hold['room_type_name']) ?><?= (int) $hold['is_private_unit'] === 1 ? ' <span class="badge badge--gold">Private</span>' : '' ?></td>
            <td><?= e($hold['unit_name']) ?> &middot; <?= e($hold['bed_label']) ?></td>
            <td><?= e((string) ($hold['user_email'] ?? 'Guest cart')) ?></td>
            <td><?= e(za_date((string) $hold['expires_at'], 'H:i:s')) ?></td>
            <td class="numeric"><?= e(money((int) $hold['price_cents'])) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if ($holds === []): ?><tr><td colspan="6" class="muted">Nothing is being held right now.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php View::stop(); ?>

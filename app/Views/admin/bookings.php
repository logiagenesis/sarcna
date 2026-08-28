<?php
use App\Core\View;
View::layout('layouts.admin');
View::start('content');
?>
<div class="admin-head">
  <div><h1>Accommodation bookings</h1><p><?= (int) $result['total'] ?> bed-nights match</p></div>
  <div class="cluster">
    <a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/bookings/holds')) ?>">Live holds</a>
    <a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/bookings/board')) ?>">Bed board</a>
    <a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/export/rooming-list')) ?>">Rooming list CSV</a>
    <a class="btn btn--sm" href="<?= e(url('/admin/export/bookings')) ?>">Export bookings</a>
  </div>
</div>

<div class="admin-toolbar">
  <form method="get" action="<?= e(url('/admin/bookings')) ?>" data-autosubmit>
    <input type="search" name="q" value="<?= e($search) ?>" placeholder="Guest, reference or email">
    <select name="night">
      <option value="">Any night</option>
      <?php foreach ($nights as $value): ?>
        <option value="<?= e($value) ?>" <?= $night === $value ? 'selected' : '' ?>><?= e(za_date($value, 'D j M Y')) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="room_type_id">
      <option value="">Any room type</option>
      <?php foreach ($roomTypes as $type): ?>
        <option value="<?= (int) $type['id'] ?>" <?= $room === (int) $type['id'] ? 'selected' : '' ?>><?= e($type['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn--sm" type="submit">Filter</button>
    <a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/bookings')) ?>">Clear</a>
  </form>
</div>

<div class="admin-panel" style="padding:0">
  <div class="table-wrap" style="border:0">
    <table class="admin-table">
      <thead><tr><th>Night</th><th>Room type</th><th>Unit &amp; bed</th><th>Guest</th><th>Order</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($result['rows'] as $booking): ?>
          <tr>
            <td><strong><?= e(za_date((string) $booking['night'], 'D j M')) ?></strong></td>
            <td><?= e($booking['room_type_name']) ?><?= (int) $booking['is_private_unit'] === 1 ? '<br><span class="badge badge--gold">Private unit</span>' : '' ?></td>
            <td><?= e($booking['unit_name']) ?><br><span class="muted"><?= e($booking['bed_label']) ?></span></td>
            <td><?= e((string) $booking['guest_name']) ?><br><span class="muted"><?= e((string) $booking['guest_email']) ?></span>
                <?php if ($booking['roommate_request']): ?><br><span class="badge">Wants: <?= e($booking['roommate_request']) ?></span><?php endif; ?>
                <?php if ($booking['accessibility_needs']): ?><br><span class="badge badge--warning">Access: <?= e($booking['accessibility_needs']) ?></span><?php endif; ?></td>
            <td><?php if ($booking['order_id']): ?><a href="<?= e(url('/admin/orders/' . $booking['order_id'])) ?>"><?= e((string) $booking['order_reference']) ?></a><?php else: ?><span class="muted">—</span><?php endif; ?>
                <br><span class="muted"><?= e($booking['reference']) ?></span></td>
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
        <?php if ($result['rows'] === []): ?><tr><td colspan="6" class="muted">No bookings match that filter.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php View::include('partials.pagination', ['result' => $result, 'query' => ['q' => $search, 'night' => $night, 'room_type_id' => $room]]); ?>
<?php View::stop(); ?>

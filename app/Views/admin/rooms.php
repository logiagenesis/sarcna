<?php
use App\Core\View;
View::layout('layouts.admin');
View::start('content');
?>
<div class="admin-head">
  <div>
    <h1>Room types &amp; beds</h1>
    <p><?= (int) $occupancy['total_beds'] ?> beds across all active units &middot; <?= (int) $occupancy['booked'] ?> bed-nights booked</p>
  </div>
  <div class="cluster">
    <a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/bookings/board')) ?>">Bed board</a>
    <a class="btn btn--sm" href="<?= e(url('/admin/rooms/create')) ?>">New room type</a>
  </div>
</div>

<div class="admin-note">
  Inventory is tracked <strong>per bed, per night</strong>. Selling one bed in a two-bed cottage leaves the other bed on sale.
  A private-unit booking simply reserves every bed in that unit.
</div>

<?php foreach ($roomTypes as $roomType): ?>
  <div class="admin-panel">
    <div class="cluster cluster--between" style="align-items:flex-start">
      <div>
        <h2 style="margin-bottom:.25rem"><?= e($roomType['name']) ?>
          <?php if ((int) $roomType['is_active'] !== 1): ?><span class="badge badge--error">Inactive</span><?php endif; ?>
          <?php if ((int) $roomType['is_accessible'] === 1): ?><span class="badge badge--success">Accessible</span><?php endif; ?>
          <?php if ((int) $roomType['is_offsite'] === 1): ?><span class="badge badge--plum">Off site</span><?php endif; ?>
          <?= mock_badge((int) $roomType['is_mock'] === 1) ?>
        </h2>
        <p class="muted" style="font-size:var(--step--1);margin:0"><?= e((string) $roomType['summary']) ?></p>
      </div>
      <a class="btn btn--sm" href="<?= e(url('/admin/rooms/' . $roomType['id'])) ?>">Edit</a>
    </div>

    <div class="tiles" style="margin:1rem 0 .5rem">
      <div class="tile"><div class="tile__label">Units</div><div class="tile__value"><?= (int) $roomType['unit_count'] ?></div></div>
      <div class="tile"><div class="tile__label">Beds</div><div class="tile__value"><?= (int) $roomType['bed_count'] ?></div></div>
      <div class="tile tile--gold"><div class="tile__label">Per bed / night</div><div class="tile__value" style="font-size:var(--step-2)"><?= e(money((int) $roomType['bed_rate_cents'])) ?></div></div>
      <div class="tile tile--clay"><div class="tile__label">Whole unit / night</div><div class="tile__value" style="font-size:var(--step-2)"><?= $roomType['private_unit_rate_cents'] === null ? '—' : e(money((int) $roomType['private_unit_rate_cents'])) ?></div></div>
    </div>

    <div class="availability">
      <?php foreach ($roomType['availability'] as $night => $free): ?>
        <span class="availability__night <?= $free === 0 ? 'is-out' : ($free < 6 ? 'is-low' : '') ?>">
          <strong><?= e($nightLabels[$night] ?? $night) ?></strong>
          <?= (int) $free ?> free of <?= (int) $roomType['bed_count'] ?>
        </span>
      <?php endforeach; ?>
    </div>
  </div>
<?php endforeach; ?>

<?php if ($roomTypes === []): ?>
  <div class="empty-state"><div class="empty-state__icon">🛏</div><h3>No room types yet</h3>
  <p>Create a room type, then generate its units and beds.</p>
  <a class="btn" style="margin-top:1rem" href="<?= e(url('/admin/rooms/create')) ?>">New room type</a></div>
<?php endif; ?>
<?php View::stop(); ?>

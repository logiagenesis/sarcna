<div class="admin-tabs">
  <a href="<?= e(url('/admin/bookings/operations')) ?>" class="<?= is_active('/admin/bookings/operations') ? 'is-active' : '' ?>">Operations</a>
  <a href="<?= e(url('/admin/bookings')) ?>" class="<?= is_active('/admin/bookings', true) ? 'is-active' : '' ?>">All bookings</a>
  <a href="<?= e(url('/admin/bookings/board')) ?>" class="<?= is_active('/admin/bookings/board') ? 'is-active' : '' ?>">Bed board</a>
  <a href="<?= e(url('/admin/bookings/run-sheet')) ?>" class="<?= is_active('/admin/bookings/run-sheet') ? 'is-active' : '' ?>">Run sheet</a>
  <a href="<?= e(url('/admin/bookings/holds')) ?>" class="<?= is_active('/admin/bookings/holds') ? 'is-active' : '' ?>">Live holds</a>
</div>

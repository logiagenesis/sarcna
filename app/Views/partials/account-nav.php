<nav class="cluster" style="margin-bottom:2rem" aria-label="Account">
  <a class="btn btn--sm <?= is_active('/account', true) ? '' : 'btn--ghost' ?>" href="<?= e(url('/account')) ?>">Dashboard</a>
  <a class="btn btn--sm <?= is_active('/account/orders') ? '' : 'btn--ghost' ?>" href="<?= e(url('/account/orders')) ?>">Orders</a>
  <a class="btn btn--sm <?= is_active('/account/bookings') ? '' : 'btn--ghost' ?>" href="<?= e(url('/account/bookings')) ?>">Accommodation</a>
  <a class="btn btn--sm <?= is_active('/account/transport') ? '' : 'btn--ghost' ?>" href="<?= e(url('/account/transport')) ?>">Transport</a>
  <a class="btn btn--sm <?= is_active('/account/profile') ? '' : 'btn--ghost' ?>" href="<?= e(url('/account/profile')) ?>">Profile</a>
  <form method="post" action="<?= e(url('/logout')) ?>" style="display:inline">
    <?= csrf_field() ?>
    <button class="btn btn--sm btn--ghost" type="submit">Sign out</button>
  </form>
</nav>

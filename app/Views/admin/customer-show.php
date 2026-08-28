<?php
use App\Core\View;
use App\Services\AuthService;
View::layout('layouts.admin');
View::start('content');
?>
<div class="admin-head">
  <div>
    <a class="link-arrow" href="<?= e(url('/admin/customers')) ?>">&larr; Customers</a>
    <h1 style="margin-top:.5rem"><?= e($customer['first_name'] . ' ' . $customer['last_name']) ?></h1>
    <p><?= e($customer['email']) ?> &middot; <?= e((string) $customer['phone']) ?> &middot; joined <?= e(za_date((string) $customer['created_at'], 'j M Y')) ?></p>
  </div>
</div>

<div class="admin-grid admin-grid--sidebar">
  <div>
    <div class="admin-panel">
      <h2>Orders</h2>
      <div class="table-wrap">
        <table class="admin-table">
          <thead><tr><th>Reference</th><th>Placed</th><th>Status</th><th class="numeric">Total</th></tr></thead>
          <tbody>
            <?php foreach ($orders as $order): ?>
              <tr>
                <td><a href="<?= e(url('/admin/orders/' . $order['id'])) ?>"><?= e($order['reference']) ?></a></td>
                <td><?= e(za_date((string) $order['created_at'], 'j M Y')) ?></td>
                <td><?php View::include('partials.order-status', ['status' => $order['status']]); ?></td>
                <td class="numeric"><?= e(money((int) $order['total_cents'])) ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if ($orders === []): ?><tr><td colspan="4" class="muted">No orders.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <?php if ($bookings !== []): ?>
      <div class="admin-panel">
        <h2>Accommodation</h2>
        <div class="table-wrap">
          <table class="admin-table">
            <thead><tr><th>Night</th><th>Room</th><th>Unit &amp; bed</th><th>Status</th></tr></thead>
            <tbody>
              <?php foreach ($bookings as $booking): ?>
                <tr>
                  <td><?= e(za_date((string) $booking['night'], 'D j M')) ?></td>
                  <td><?= e($booking['room_type_name']) ?></td>
                  <td><?= e($booking['unit_name']) ?> &middot; <?= e($booking['bed_label']) ?></td>
                  <td><span class="badge"><?= e($booking['status']) ?></span></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($transport !== []): ?>
      <div class="admin-panel">
        <h2>Transport</h2>
        <div class="table-wrap">
          <table class="admin-table">
            <thead><tr><th>Departs</th><th>Route</th><th>Passenger</th></tr></thead>
            <tbody>
              <?php foreach ($transport as $trip): ?>
                <tr>
                  <td><?= e(za_date((string) $trip['departs_at'], 'D j M, H:i')) ?></td>
                  <td><?= e($trip['route_name']) ?></td>
                  <td><?= e($trip['passenger_name']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <div>
    <div class="admin-panel">
      <h2>Profile</h2>
      <div class="summary__row"><span>Home group</span><strong><?= e((string) $customer['home_group']) ?: '—' ?></strong></div>
      <div class="summary__row"><span>Region</span><strong><?= e((string) $customer['region']) ?: '—' ?></strong></div>
      <div class="summary__row"><span>Email confirmed</span><strong><?= $customer['email_verified_at'] ? 'Yes' : 'No' ?></strong></div>
      <div class="summary__row"><span>Last sign-in</span><strong><?= e(za_date((string) $customer['last_login_at'], 'j M Y')) ?: '—' ?></strong></div>
      <?php if ($customer['dietary_notes']): ?><div class="summary__row"><span>Dietary</span><strong style="text-align:right"><?= e($customer['dietary_notes']) ?></strong></div><?php endif; ?>
      <?php if ($customer['accessibility_notes']): ?><div class="summary__row"><span>Accessibility</span><strong style="text-align:right"><?= e($customer['accessibility_notes']) ?></strong></div><?php endif; ?>
    </div>

    <?php if (can('*')): ?>
      <div class="admin-panel">
        <h2>Admin roles</h2>
        <form method="post" action="<?= e(url('/admin/customers/' . $customer['id'] . '/roles')) ?>">
          <?= csrf_field() ?>
          <?php foreach (AuthService::ROLE_LABELS as $role => $label): ?>
            <label class="checkbox">
              <input type="checkbox" name="roles[]" value="<?= e($role) ?>" <?= in_array($role, $roles, true) ? 'checked' : '' ?>>
              <span><?= e($label) ?><br><span class="muted" style="font-size:.72rem"><?= e(implode(', ', AuthService::ROLE_PERMISSIONS[$role])) ?></span></span>
            </label>
          <?php endforeach; ?>
          <button class="btn btn--block btn--sm" type="submit">Save roles</button>
        </form>
      </div>
    <?php endif; ?>

    <div class="admin-panel">
      <h2>Account status</h2>
      <form method="post" action="<?= e(url('/admin/customers/' . $customer['id'] . '/status')) ?>" data-confirm="Change this account's status?">
        <?= csrf_field() ?>
        <div class="field">
          <select name="status">
            <option value="active" <?= $customer['status'] === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="suspended" <?= $customer['status'] === 'suspended' ? 'selected' : '' ?>>Suspended</option>
          </select>
        </div>
        <button class="btn btn--block btn--sm btn--ghost" type="submit">Apply</button>
      </form>
    </div>
  </div>
</div>
<?php View::stop(); ?>

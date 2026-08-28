<?php
use App\Core\View;
View::layout('layouts.admin');
View::start('content');
?>
<div class="admin-head">
  <div><h1>Customers &amp; admins</h1><p><?= (int) $result['total'] ?> accounts</p></div>
  <a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/export/customers')) ?>">Export customers</a>
</div>

<div class="admin-toolbar">
  <form method="get" action="<?= e(url('/admin/customers')) ?>" data-autosubmit>
    <input type="search" name="q" value="<?= e($search) ?>" placeholder="Name, email or phone">
    <select name="type">
      <option value="">Everyone</option>
      <option value="customers" <?= $filter === 'customers' ? 'selected' : '' ?>>Customers only</option>
      <option value="admins" <?= $filter === 'admins' ? 'selected' : '' ?>>Admins only</option>
    </select>
    <button class="btn btn--sm" type="submit">Filter</button>
  </form>
</div>

<div class="admin-panel" style="padding:0">
  <div class="table-wrap" style="border:0">
    <table class="admin-table">
      <thead><tr><th>Name</th><th>Contact</th><th>Group / region</th><th class="numeric">Orders</th><th class="numeric">Spent</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($result['rows'] as $customer): ?>
          <tr>
            <td><a href="<?= e(url('/admin/customers/' . $customer['id'])) ?>"><strong><?= e($customer['first_name'] . ' ' . $customer['last_name']) ?></strong></a>
                <?php if ((int) $customer['is_admin'] === 1): ?><br><span class="badge badge--gold">Admin</span><?php endif; ?>
                <?= mock_badge((int) $customer['is_mock'] === 1, 'Demo') ?></td>
            <td><?= e($customer['email']) ?><br><span class="muted"><?= e((string) $customer['phone']) ?></span>
                <?php if ($customer['email_verified_at'] === null): ?><br><span class="badge badge--warning">Unverified</span><?php endif; ?></td>
            <td><?= e((string) $customer['home_group']) ?><br><span class="muted"><?= e((string) $customer['region']) ?></span></td>
            <td class="numeric"><?= (int) $customer['paid_orders'] ?></td>
            <td class="numeric"><?= e(money((int) $customer['spent'])) ?></td>
            <td><?= $customer['status'] === 'active' ? '<span class="badge badge--success">Active</span>' : '<span class="badge badge--error">Suspended</span>' ?></td>
            <td><a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/customers/' . $customer['id'])) ?>">Open</a></td>
          </tr>
        <?php endforeach; ?>
        <?php if ($result['rows'] === []): ?><tr><td colspan="7" class="muted">No accounts match.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php View::include('partials.pagination', ['result' => $result, 'query' => ['q' => $search, 'type' => $filter]]); ?>
<?php View::stop(); ?>

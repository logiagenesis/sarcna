<?php
use App\Core\View;
View::layout('layouts.admin');
View::start('content');
?>
<div class="admin-head">
  <div>
    <h1>Orders</h1>
    <p><?= (int) $totals['count'] ?> matching &middot; <?= e(money((int) $totals['value'])) ?> in value</p>
  </div>
  <a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/export/orders')) ?>">Export CSV</a>
</div>

<div class="admin-toolbar">
  <form method="get" action="<?= e(url('/admin/orders')) ?>" data-autosubmit>
    <input type="search" name="q" value="<?= e($search) ?>" placeholder="Reference, name or email">
    <select name="status">
      <option value="">Any status</option>
      <?php foreach (['pending_payment' => 'Awaiting payment', 'paid' => 'Paid', 'failed' => 'Failed', 'cancelled' => 'Cancelled', 'refunded' => 'Refunded'] as $value => $label): ?>
        <option value="<?= e($value) ?>" <?= $status === $value ? 'selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn--sm" type="submit">Filter</button>
    <a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/orders')) ?>">Clear</a>
  </form>
</div>

<div class="admin-panel" style="padding:0">
  <div class="table-wrap" style="border:0">
    <table class="admin-table">
      <thead><tr><th>Reference</th><th>Customer</th><th>Contents</th><th>Status</th><th class="numeric">Total</th><th>Placed</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($result['rows'] as $order): ?>
          <tr>
            <td><a href="<?= e(url('/admin/orders/' . $order['id'])) ?>"><strong><?= e($order['reference']) ?></strong></a>
                <?php if ($order['checked_in_at'] !== null): ?><br><span class="badge badge--success">Checked in</span><?php endif; ?></td>
            <td><?= e(trim(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? ''))) ?><br><span class="muted"><?= e($order['email']) ?></span></td>
            <td><?= (int) $order['item_count'] ?> line<?= (int) $order['item_count'] === 1 ? '' : 's' ?></td>
            <td><?php View::include('partials.order-status', ['status' => $order['status']]); ?>
                <?php if (str_starts_with((string) $order['admin_note'], 'NEEDS ATTENTION')): ?><br><span class="badge badge--error">Needs attention</span><?php endif; ?></td>
            <td class="numeric"><?= e(money((int) $order['total_cents'])) ?></td>
            <td><?= e(za_date((string) $order['created_at'], 'j M Y, H:i')) ?></td>
            <td><a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/orders/' . $order['id'])) ?>">Open</a></td>
          </tr>
        <?php endforeach; ?>
        <?php if ($result['rows'] === []): ?><tr><td colspan="7" class="muted">No orders match that filter.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php View::include('partials.pagination', ['result' => $result, 'query' => ['q' => $search, 'status' => $status]]); ?>
<?php View::stop(); ?>

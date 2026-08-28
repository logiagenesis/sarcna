<?php
use App\Core\View;
View::layout('layouts.admin');
View::start('content');
?>
<div class="admin-head">
  <div><h1>Donations</h1><p><?= e(money($total)) ?> received in total</p></div>
  <a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/export/donations')) ?>">Export CSV</a>
</div>

<?php if ($summary !== []): ?>
  <div class="tiles">
    <?php foreach ($summary as $row): ?>
      <div class="tile tile--plum">
        <div class="tile__label"><?= e($row['donation_type']) ?></div>
        <div class="tile__value" style="font-size:var(--step-2)"><?= e(money((int) $row['total'])) ?></div>
        <div class="tile__meta"><?= (int) $row['count'] ?> donation(s)</div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="admin-toolbar">
  <form method="get" action="<?= e(url('/admin/donations')) ?>" data-autosubmit>
    <select name="status">
      <option value="">Any status</option>
      <?php foreach (['pending', 'paid', 'failed', 'refunded'] as $value): ?>
        <option value="<?= $value ?>" <?= $status === $value ? 'selected' : '' ?>><?= e(ucfirst($value)) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn--sm" type="submit">Filter</button>
  </form>
</div>

<div class="admin-panel" style="padding:0">
  <div class="table-wrap" style="border:0">
    <table class="admin-table">
      <thead><tr><th>Reference</th><th>Date</th><th>Type</th><th>Donor</th><th class="numeric">Amount</th><th>Status</th><th>Order</th></tr></thead>
      <tbody>
        <?php foreach ($result['rows'] as $donation): ?>
          <tr>
            <td><strong><?= e($donation['reference']) ?></strong></td>
            <td><?= e(za_date((string) $donation['created_at'], 'j M Y')) ?></td>
            <td><?= e($donation['donation_type']) ?></td>
            <td><?= (int) $donation['is_anonymous'] === 1 ? '<em class="muted">Anonymous</em>' : e((string) $donation['name']) ?>
                <?php if ($donation['message']): ?><br><span class="muted"><?= e(excerpt($donation['message'], 70)) ?></span><?php endif; ?></td>
            <td class="numeric"><?= e(money((int) $donation['amount_cents'])) ?></td>
            <td><span class="badge <?= $donation['status'] === 'paid' ? 'badge--success' : '' ?>"><?= e($donation['status']) ?></span></td>
            <td><?php if ($donation['order_id']): ?><a href="<?= e(url('/admin/orders/' . $donation['order_id'])) ?>"><?= e((string) $donation['order_reference']) ?></a><?php endif; ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if ($result['rows'] === []): ?><tr><td colspan="7" class="muted">No donations yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php View::include('partials.pagination', ['result' => $result, 'query' => ['status' => $status]]); ?>
<?php View::stop(); ?>

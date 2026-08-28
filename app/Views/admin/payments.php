<?php
use App\Core\View;
View::layout('layouts.admin');
View::start('content');
?>
<div class="admin-head">
  <div>
    <h1>Payments</h1>
    <p><?= (int) $summary['count'] ?> completed &middot; <?= e(money((int) $summary['gross'])) ?> gross &middot; <?= e(money((int) $summary['fees'])) ?> in PayFast fees</p>
  </div>
  <div class="cluster">
    <a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/payments/logs')) ?>">Notification log</a>
    <a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/export/payments')) ?>">Export CSV</a>
  </div>
</div>

<?php if (!$configured): ?>
  <div class="alert alert--error"><div><div class="alert__title">PayFast is not configured</div><p>Add the merchant ID and key to <code>.env</code>.</p></div></div>
<?php elseif ($sandbox): ?>
  <div class="alert alert--warning"><div><div class="alert__title">Sandbox mode</div><p>These are test payments. No real money has moved.</p></div></div>
<?php endif; ?>

<div class="admin-toolbar">
  <form method="get" action="<?= e(url('/admin/payments')) ?>" data-autosubmit>
    <select name="status">
      <option value="">Any status</option>
      <?php foreach (['initiated', 'complete', 'failed', 'cancelled', 'refunded'] as $value): ?>
        <option value="<?= $value ?>" <?= $status === $value ? 'selected' : '' ?>><?= e(ucfirst($value)) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn--sm" type="submit">Filter</button>
  </form>
</div>

<div class="admin-panel" style="padding:0">
  <div class="table-wrap" style="border:0">
    <table class="admin-table">
      <thead><tr><th>When</th><th>Order</th><th>PayFast ID</th><th class="numeric">Amount</th><th class="numeric">Fee</th><th>Status</th><th>Signature</th><th>Source IP</th></tr></thead>
      <tbody>
        <?php foreach ($result['rows'] as $payment): ?>
          <tr>
            <td><?= e(za_date((string) $payment['created_at'], 'j M Y, H:i')) ?></td>
            <td><?php if ($payment['order_id']): ?><a href="<?= e(url('/admin/orders/' . $payment['order_id'])) ?>"><?= e((string) $payment['reference']) ?></a><br><span class="muted"><?= e((string) $payment['email']) ?></span><?php else: ?><span class="muted">—</span><?php endif; ?></td>
            <td><?= e((string) $payment['provider_reference']) ?></td>
            <td class="numeric"><?= e(money((int) $payment['amount_cents'])) ?></td>
            <td class="numeric"><?= e(money((int) $payment['fee_cents'])) ?></td>
            <td><span class="badge <?= $payment['status'] === 'complete' ? 'badge--success' : ($payment['status'] === 'initiated' ? 'badge--warning' : 'badge--error') ?>"><?= e($payment['status']) ?></span></td>
            <td><?= (int) $payment['signature_valid'] === 1 ? '<span class="badge badge--success">Valid</span>' : '<span class="badge badge--error">Invalid</span>' ?></td>
            <td><?= e((string) $payment['source_ip']) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if ($result['rows'] === []): ?><tr><td colspan="8" class="muted">No payments recorded.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php View::include('partials.pagination', ['result' => $result, 'query' => ['status' => $status]]); ?>
<?php View::stop(); ?>

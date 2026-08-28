<?php
use App\Core\View;
View::layout('layouts.admin');
View::start('content');
?>
<div class="admin-head">
  <div>
    <h1>PayFast notification log</h1>
    <p>Every notification we receive is logged here, whether it was accepted or rejected.</p>
  </div>
  <a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/payments')) ?>">Payments</a>
</div>

<div class="admin-note">
  <strong>What the events mean.</strong>
  <code>itn_received</code> a notification arrived &middot;
  <code>itn_bad_signature</code> the signature did not match, so it was ignored &middot;
  <code>itn_bad_source</code> it did not come from a PayFast server &middot;
  <code>itn_amount_mismatch</code> the amount did not match the order &middot;
  <code>itn_not_confirmed</code> PayFast would not confirm the payload &middot;
  <code>order_paid</code> the order was fulfilled.
</div>

<div class="admin-toolbar">
  <form method="get" action="<?= e(url('/admin/payments/logs')) ?>" data-autosubmit>
    <select name="event">
      <option value="">Any event</option>
      <?php foreach ($events as $value): ?>
        <option value="<?= e($value) ?>" <?= $event === $value ? 'selected' : '' ?>><?= e($value) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn--sm" type="submit">Filter</button>
  </form>
</div>

<div class="admin-panel" style="padding:0">
  <div class="table-wrap" style="border:0">
    <table class="admin-table">
      <thead><tr><th>When</th><th>Event</th><th>Order</th><th>Message</th><th>Source</th></tr></thead>
      <tbody>
        <?php foreach ($result['rows'] as $log): ?>
          <tr>
            <td style="white-space:nowrap"><?= e(za_date((string) $log['created_at'], 'j M, H:i:s')) ?></td>
            <td><span class="badge <?= str_contains((string) $log['event'], 'bad') || str_contains((string) $log['event'], 'mismatch') || str_contains((string) $log['event'], 'not_confirmed') ? 'badge--error' : ($log['event'] === 'order_paid' ? 'badge--success' : '') ?>"><?= e($log['event']) ?></span></td>
            <td><?php if ($log['order_id']): ?><a href="<?= e(url('/admin/orders/' . $log['order_id'])) ?>"><?= e((string) $log['reference']) ?></a><?php endif; ?></td>
            <td><?= e((string) $log['message']) ?></td>
            <td><?= e((string) $log['source_ip']) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if ($result['rows'] === []): ?><tr><td colspan="5" class="muted">Nothing logged yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php View::include('partials.pagination', ['result' => $result, 'query' => ['event' => $event]]); ?>
<?php View::stop(); ?>

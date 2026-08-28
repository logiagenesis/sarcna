<?php
use App\Core\View;
View::layout('layouts.admin');
View::start('content');

$byCategory = [];
$byMethod   = [];

foreach ($refunds as $refund) {
    $category = (string) ($refund['category'] ?: 'mixed');
    $method   = (string) ($refund['method'] ?: 'payfast');

    $byCategory[$category] = ($byCategory[$category] ?? 0) + (int) $refund['amount_cents'];
    $byMethod[$method]     = ($byMethod[$method] ?? 0) + (int) $refund['amount_cents'];
}

arsort($byCategory);
arsort($byMethod);
?>
<div class="admin-head">
  <div>
    <h1>Refunds</h1>
    <p>Every rand that went back to a delegate, and why. <?= e($period['label']) ?>.</p>
  </div>
  <a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/export/refunds')) ?>">Export CSV</a>
</div>

<?php View::include('partials.finance-tabs'); ?>
<?php View::include('partials.finance-period', ['period' => $period, 'action' => '/admin/finance/refunds']); ?>

<div class="tiles">
  <div class="tile tile--error">
    <div class="tile__label">Refunded in this period</div>
    <div class="tile__value money"><?= e(money((int) $total)) ?></div>
    <div class="tile__meta"><?= count($refunds) ?> refund<?= count($refunds) === 1 ? '' : 's' ?></div>
  </div>
  <div class="tile">
    <div class="tile__label">Average refund</div>
    <div class="tile__value money"><?= e(money(count($refunds) > 0 ? (int) round($total / count($refunds)) : 0)) ?></div>
  </div>
  <div class="tile tile--clay">
    <div class="tile__label">Largest single refund</div>
    <?php $largest = 0; foreach ($refunds as $r) { $largest = max($largest, (int) $r['amount_cents']); } ?>
    <div class="tile__value money"><?= e(money($largest)) ?></div>
  </div>
  <div class="tile tile--plum">
    <div class="tile__label">Most refunded category</div>
    <div class="tile__value"><?= e($byCategory === [] ? '—' : (string) array_key_first($byCategory)) ?></div>
    <div class="tile__meta"><?= $byCategory === [] ? 'Nothing refunded yet' : e(money((int) reset($byCategory))) ?></div>
  </div>
</div>

<p class="finance-note">
  <strong>What this page is and is not.</strong> Recording a refund here writes it into the ledger and subtracts it from
  net income everywhere in this section. It does <em>not</em> move any money — the actual refund is processed in the
  PayFast dashboard or by EFT, by whoever has the banking mandate. Record it here after the money has left, and put the
  PayFast or bank reference in the provider reference field so the two records can always be tied together.
  Refunds are recorded from the order itself: open the order and use the refund panel.
</p>

<div class="admin-grid admin-grid--sidebar">
  <div class="admin-panel" style="padding:0">
    <div class="admin-panel__head" style="padding:1rem 1.1rem">
      <h2>Refund ledger</h2>
      <p class="muted">Newest first. Every line names the person who recorded it.</p>
    </div>
    <div class="table-wrap" style="border:0">
      <table class="ledger">
        <thead>
          <tr><th>Date</th><th>Reference</th><th>Order</th><th>Delegate</th><th>Reason</th><th>Category</th><th>Method</th><th class="numeric">Amount</th></tr>
        </thead>
        <tbody>
          <?php foreach ($refunds as $refund): ?>
            <tr>
              <td><?= e(za_date((string) ($refund['refunded_on'] ?: $refund['created_at']), 'j M Y')) ?></td>
              <td><code><?= e((string) $refund['reference']) ?></code>
                  <?php if ($refund['provider_reference']): ?><br><span class="muted"><?= e((string) $refund['provider_reference']) ?></span><?php endif; ?></td>
              <td><a href="<?= e(url('/admin/orders/' . $refund['order_id'])) ?>"><?= e((string) $refund['order_reference']) ?></a></td>
              <td><?= e((string) $refund['email']) ?></td>
              <td><?= e((string) $refund['reason']) ?>
                  <?php if ($refund['refunded_by']): ?><br><span class="muted">recorded by <?= e((string) $refund['refunded_by']) ?></span><?php endif; ?></td>
              <td><?= e((string) $refund['category']) ?></td>
              <td><?= e((string) $refund['method']) ?></td>
              <td class="numeric money">− <?= e(money((int) $refund['amount_cents'])) ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if ($refunds === []): ?>
            <tr><td colspan="8" class="muted">No refunds were recorded in this period. That is the outcome to aim for.</td></tr>
          <?php endif; ?>
        </tbody>
        <?php if ($refunds !== []): ?>
          <tfoot>
            <tr>
              <th colspan="7">Total refunded</th>
              <td class="numeric money">− <?= e(money((int) $total)) ?></td>
            </tr>
          </tfoot>
        <?php endif; ?>
      </table>
    </div>
  </div>

  <aside class="stack-m">
    <div class="admin-panel">
      <h2>By category</h2>
      <?php if ($byCategory === []): ?>
        <p class="muted">Nothing refunded in this period.</p>
      <?php else: ?>
        <div class="ledger-scroll">
        <table class="ledger">
          <tbody>
            <?php foreach ($byCategory as $category => $cents): ?>
              <tr>
                <td><?= e((string) $category) ?>
                  <div class="bar bar--error"><span style="width:<?= (int) $total > 0 ? (int) round(($cents / (int) $total) * 100) : 0 ?>%"></span></div>
                </td>
                <td class="numeric money"><?= e(money((int) $cents)) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        </div>
      <?php endif; ?>
    </div>

    <div class="admin-panel">
      <h2>By method</h2>
      <?php if ($byMethod === []): ?>
        <p class="muted">Nothing refunded in this period.</p>
      <?php else: ?>
        <div class="ledger-scroll">
        <table class="ledger">
          <tbody>
            <?php foreach ($byMethod as $method => $cents): ?>
              <tr><td><?= e((string) $method) ?></td><td class="numeric money"><?= e(money((int) $cents)) ?></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        </div>
      <?php endif; ?>
    </div>

    <div class="admin-panel">
      <h3>The refund policy, in practice</h3>
      <ol class="checklist">
        <li>Check the published cancellation terms on the registration page before agreeing to anything.</li>
        <li>Process the money first — PayFast dashboard for a card payment, EFT for anything else.</li>
        <li>Record it against the order here, with the provider reference.</li>
        <li>On a <strong>full</strong> refund, tick &ldquo;release inventory&rdquo; so the bed and the seat go back on sale. On a partial refund the booking still stands, so nothing is released.</li>
        <li>The system will never let you refund more than the order was paid.</li>
      </ol>
    </div>
  </aside>
</div>
<?php View::stop(); ?>

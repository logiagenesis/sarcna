<?php
use App\Core\View;
View::layout('layouts.admin');
View::start('content');

$statementTotal = 0;
$matchedTotal   = 0;

foreach ($entries as $entry) {
    $statementTotal += (int) $entry['amount_cents'];

    if ((int) $entry['is_reconciled'] === 1) {
        $matchedTotal += (int) $entry['amount_cents'];
    }
}

$gatewayNet   = $summary['gross_cents'] - $summary['fees_cents'];
$unexplained  = $gatewayNet - $statementTotal;
?>
<div class="admin-head">
  <div>
    <h1>Bank reconciliation</h1>
    <p>Tie every confirmed payment back to a line on the bank statement. <?= e($period['label']) ?>.</p>
  </div>
  <a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/export/payments')) ?>">Export payments CSV</a>
</div>

<?php View::include('partials.finance-tabs'); ?>
<?php View::include('partials.finance-period', ['period' => $period, 'action' => '/admin/finance/reconciliation']); ?>

<div class="tiles">
  <div class="tile tile--gold">
    <div class="tile__label">Gross taken by PayFast</div>
    <div class="tile__value money"><?= e(money((int) $summary['gross_cents'])) ?></div>
    <div class="tile__meta"><?= (int) $summary['orders_paid'] ?> paid <?= (int) $summary['orders_paid'] === 1 ? 'order' : 'orders' ?></div>
  </div>
  <div class="tile tile--clay">
    <div class="tile__label">Less gateway fees</div>
    <div class="tile__value money">− <?= e(money((int) $fees['total_cents'])) ?></div>
    <div class="tile__meta">
      <?= e(money((int) $fees['reported_cents'])) ?> reported by PayFast<?php if ($fees['estimated']): ?>,
      <?= e(money((int) $fees['estimated_cents'])) ?> estimated on <?= (int) $fees['without_fee'] ?> payment<?= (int) $fees['without_fee'] === 1 ? '' : 's' ?><?php endif; ?>
    </div>
  </div>
  <div class="tile tile--success">
    <div class="tile__label">Expected in the bank</div>
    <div class="tile__value money"><?= e(money($gatewayNet)) ?></div>
    <div class="tile__meta">Gross minus fees</div>
  </div>
  <div class="tile <?= $unexplained === 0 ? 'tile--success' : ($statementTotal === 0 ? '' : 'tile--error') ?>">
    <div class="tile__label">Captured off the statement</div>
    <div class="tile__value money"><?= e(money($statementTotal)) ?></div>
    <div class="tile__meta">
      <?php if ($statementTotal === 0): ?>
        No statement lines captured for this period yet
      <?php elseif ($unexplained === 0): ?>
        Balanced to the cent
      <?php else: ?>
        <?= e(money(abs($unexplained))) ?> <?= $unexplained > 0 ? 'still to appear' : 'more than expected' ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<p class="finance-note">
  <strong>Why the numbers differ, and that is normal.</strong> PayFast settles in batches, usually a couple of working
  days behind the payment, and it deducts its fee before paying out. So the bank statement will nearly always be a
  little behind the gross total on this page. What matters is that every payment below eventually appears on a
  statement line, and that the difference is explained by settlement timing and fees rather than by a payment nobody
  can account for. The exceptions panel is the one that needs a human.
</p>

<?php if ($exceptions !== []): ?>
  <div class="admin-panel" style="padding:0;border-left:4px solid var(--error)">
    <div class="admin-panel__head" style="padding:1rem 1.1rem">
      <h2>Exceptions — <?= count($exceptions) ?> to look at</h2>
      <p class="muted">A payment recorded as complete whose order is not paid, a payment with an invalid signature, or a payment that was started and never finished.</p>
    </div>
    <div class="table-wrap" style="border:0">
      <table class="ledger">
        <thead><tr><th>When</th><th>Order</th><th class="numeric">Amount</th><th>Payment status</th><th>Order status</th><th>What it means</th></tr></thead>
        <tbody>
          <?php foreach ($exceptions as $row): ?>
            <?php
              $why = 'Started and abandoned. The customer left the PayFast page. Nothing was taken.';

              if ((string) $row['status'] === 'complete' && (int) $row['signature_valid'] === 0) {
                  $why = 'The signature on this notification did not validate. Treat it as untrusted and check the PayFast dashboard directly.';
              } elseif ((string) $row['status'] === 'complete') {
                  $why = 'PayFast says this was paid but the order is not marked paid. Check it before the customer arrives.';
              }
            ?>
            <tr>
              <td><?= e(za_date((string) $row['created_at'], 'j M Y H:i')) ?></td>
              <td>
                <?php if ($row['order_id']): ?>
                  <a href="<?= e(url('/admin/orders/' . $row['order_id'])) ?>"><?= e((string) ($row['order_reference'] ?? '#' . $row['order_id'])) ?></a>
                <?php else: ?>
                  <span class="muted">no order</span>
                <?php endif; ?>
              </td>
              <td class="numeric money"><?= e(money((int) $row['amount_cents'])) ?></td>
              <td><span class="badge badge--warning"><?= e((string) $row['status']) ?></span></td>
              <td><?= e((string) ($row['order_status'] ?? '—')) ?></td>
              <td class="muted"><?= e($why) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php else: ?>
  <div class="admin-panel" style="border-left:4px solid var(--success)">
    <h2>No exceptions</h2>
    <p class="muted">Every completed payment has a matching paid order, every signature validated, and nothing has been left half-finished. Nothing here needs a human.</p>
  </div>
<?php endif; ?>

<div class="admin-grid admin-grid--sidebar" style="margin-top:var(--space-m)">
  <div class="stack-m">
    <div class="admin-panel" style="padding:0">
      <div class="admin-panel__head" style="padding:1rem 1.1rem">
        <h2>Payments to tick off</h2>
        <p class="muted">
          Every payment PayFast confirmed in this period, newest first —
          <?= (int) $rowTotals['payments'] ?> in total<?= $result['pages'] > 1 ? ', 50 to a page' : '' ?>.
          The CSV export has the lot in one file.
        </p>
      </div>
      <div class="table-wrap" style="border:0">
        <table class="ledger">
          <thead>
            <tr><th>Date</th><th>Order</th><th>Payer</th><th>PayFast ref</th><th class="numeric">Gross</th><th class="numeric">Fee</th><th class="numeric">Net</th></tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $row): ?>
              <tr>
                <td><?= e(za_date((string) $row['created_at'], 'j M Y H:i')) ?></td>
                <td><a href="<?= e(url('/admin/orders/' . $row['order_id'])) ?>"><?= e((string) $row['order_reference']) ?></a></td>
                <td><?= e((string) $row['email']) ?></td>
                <td><code><?= e((string) $row['provider_reference'] ?: '—') ?></code></td>
                <td class="numeric money"><?= e(money((int) $row['amount_cents'])) ?></td>
                <td class="numeric money"><?= (int) $row['fee_cents'] > 0 ? e(money((int) $row['fee_cents'])) : '<span class="muted">not reported</span>' ?></td>
                <td class="numeric money"><?= e(money((int) $row['net_cents'])) ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if ($rows === []): ?>
              <tr><td colspan="7" class="muted">No confirmed payments in this period.</td></tr>
            <?php endif; ?>
          </tbody>
          <?php if ($rows !== []): ?>
            <tfoot>
              <tr>
                <th colspan="4"><?= (int) $rowTotals['payments'] ?> payment<?= (int) $rowTotals['payments'] === 1 ? '' : 's' ?> in this period</th>
                <td class="numeric money"><?= e(money((int) $rowTotals['gross_cents'])) ?></td>
                <td class="numeric money"><?= e(money((int) $rowTotals['fee_cents'])) ?></td>
                <td class="numeric money"><?= e(money((int) $rowTotals['net_cents'])) ?></td>
              </tr>
            </tfoot>
          <?php endif; ?>
        </table>
      </div>
      <?php if ($result['pages'] > 1): ?>
        <div style="padding:0 1.1rem 1rem">
          <?php View::include('partials.pagination', ['result' => $result, 'query' => ['period' => $period['key'], 'from' => $period['from'], 'to' => $period['to']]]); ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="admin-panel" style="padding:0">
      <div class="admin-panel__head" style="padding:1rem 1.1rem">
        <h2>Statement lines captured</h2>
        <p class="muted"><?= e(money($matchedTotal)) ?> of <?= e(money($statementTotal)) ?> marked reconciled.</p>
      </div>
      <div class="table-wrap" style="border:0">
        <table class="ledger">
          <thead><tr><th>Statement date</th><th>Description</th><th class="numeric">Amount</th><th>Matched payments</th><th>Status</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($entries as $entry): ?>
              <tr>
                <td><?= e(za_date((string) $entry['statement_date'], 'j M Y')) ?></td>
                <td>
                  <strong><?= e((string) $entry['description'] ?: 'Statement line') ?></strong>
                  <?php if ($entry['notes']): ?><br><span class="muted"><?= e(excerpt((string) $entry['notes'], 90)) ?></span><?php endif; ?>
                  <?php if ($entry['created_by_email']): ?><br><span class="muted">captured by <?= e((string) $entry['created_by_email']) ?></span><?php endif; ?>
                </td>
                <td class="numeric money"><?= e(money((int) $entry['amount_cents'])) ?></td>
                <td><?= e((string) $entry['matched_payment_ids'] ?: '—') ?></td>
                <td>
                  <span class="badge <?= (int) $entry['is_reconciled'] === 1 ? 'badge--success' : 'badge--warning' ?>">
                    <?= (int) $entry['is_reconciled'] === 1 ? 'reconciled' : 'open' ?>
                  </span>
                </td>
                <td>
                  <details>
                    <summary style="cursor:pointer;font-size:var(--step--1)">Edit</summary>
                    <form method="post" action="<?= e(url('/admin/finance/reconciliation')) ?>" style="margin-top:.5rem;min-width:240px">
                      <?= csrf_field() ?>
                      <input type="hidden" name="id" value="<?= (int) $entry['id'] ?>">
                      <div class="field"><label class="field__label">Statement date</label><input type="date" name="statement_date" value="<?= e((string) $entry['statement_date']) ?>" required></div>
                      <div class="field"><label class="field__label">Description</label><input type="text" name="description" value="<?= e((string) $entry['description']) ?>"></div>
                      <div class="field"><label class="field__label">Amount (R)</label><input type="number" step="0.01" name="amount" value="<?= number_format(((int) $entry['amount_cents']) / 100, 2, '.', '') ?>" required></div>
                      <div class="field"><label class="field__label">Matched payment IDs</label><input type="text" name="matched_payment_ids" value="<?= e((string) $entry['matched_payment_ids']) ?>"></div>
                      <div class="field"><label class="field__label">Notes</label><textarea name="notes" rows="2"><?= e((string) $entry['notes']) ?></textarea></div>
                      <label class="checkbox"><input type="checkbox" name="is_reconciled" value="1" <?= (int) $entry['is_reconciled'] === 1 ? 'checked' : '' ?>> Reconciled</label>
                      <button class="btn btn--sm" type="submit" style="margin-top:.5rem">Save</button>
                    </form>
                    <form method="post" action="<?= e(url('/admin/finance/reconciliation/' . $entry['id'] . '/delete')) ?>" style="margin-top:.5rem" data-confirm="Delete this statement line?">
                      <?= csrf_field() ?>
                      <button class="btn btn--sm btn--ghost" type="submit">Delete</button>
                    </form>
                  </details>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if ($entries === []): ?>
              <tr><td colspan="6" class="muted">No statement lines captured for this period. Add the first one on the right as the PayFast payouts land.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <aside class="stack-m">
    <div class="admin-panel">
      <h2>Capture a statement line</h2>
      <p class="muted">One line per PayFast payout, or per direct deposit. List the payment IDs it covers so the trail is obvious next year.</p>
      <form method="post" action="<?= e(url('/admin/finance/reconciliation')) ?>">
        <?= csrf_field() ?>
        <div class="field">
          <label class="field__label" for="rec-date">Statement date</label>
          <input id="rec-date" type="date" name="statement_date" value="<?= e(date('Y-m-d')) ?>" required>
        </div>
        <div class="field">
          <label class="field__label" for="rec-desc">Description</label>
          <input id="rec-desc" type="text" name="description" placeholder="PayFast payout 12 Mar">
        </div>
        <div class="field">
          <label class="field__label" for="rec-amount">Amount (R)</label>
          <input id="rec-amount" type="number" step="0.01" name="amount" required>
          <p class="field__hint">Enter what actually hit the bank, after fees. A negative number is fine for a reversal.</p>
        </div>
        <div class="field">
          <label class="field__label" for="rec-ids">Matched payment IDs</label>
          <input id="rec-ids" type="text" name="matched_payment_ids" placeholder="14, 15, 18">
        </div>
        <div class="field">
          <label class="field__label" for="rec-notes">Notes</label>
          <textarea id="rec-notes" name="notes" rows="2"></textarea>
        </div>
        <label class="checkbox"><input type="checkbox" name="is_reconciled" value="1"> This line is fully reconciled</label>
        <button class="btn" type="submit" style="margin-top:.75rem">Save line</button>
      </form>
    </div>

    <div class="admin-panel">
      <h3>Month-end checklist</h3>
      <ol class="checklist">
        <li>Pull the PayFast statement for the month and the bank statement for the same dates.</li>
        <li>Capture one line per payout above, with the payment IDs it covers.</li>
        <li>Check that gross minus fees matches what landed. A difference is settlement timing until proven otherwise.</li>
        <li>Clear every exception in the red panel. None of them should still be open at month-end.</li>
        <li>Export the payments CSV and file it with the statements.</li>
        <li>Print this page for the committee pack — it prints clean, without the sidebar.</li>
      </ol>
    </div>
  </aside>
</div>
<?php View::stop(); ?>

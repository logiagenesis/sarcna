<?php
use App\Core\View;
use App\Services\SettingsService;
View::layout('layouts.admin');
View::start('content');
$s = $summary;
?>
<div class="admin-head">
  <div>
    <h1>Finance overview</h1>
    <p>Everything the treasurer is asked in a committee meeting, on one page. <?= e($period['label']) ?>.</p>
  </div>
  <div class="cluster">
    <a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/export/finance-pack?period=' . $period['key'] . '&from=' . $period['from'] . '&to=' . $period['to'])) ?>">Finance pack CSV</a>
    <button class="btn btn--sm" type="button" onclick="window.print()">Print for the meeting</button>
  </div>
</div>

<?php View::include('partials.finance-tabs'); ?>
<?php View::include('partials.finance-period', ['period' => $period, 'action' => '/admin/finance']); ?>

<div class="finance-note">
  <strong>What counts as income here.</strong> Only orders PayFast has confirmed as paid. Orders still awaiting payment are
  shown separately as pipeline and are never mixed into revenue, so nothing on this page is money the convention does not have.
</div>

<div class="tiles">
  <div class="tile tile--success">
    <div class="tile__label">Money received</div>
    <div class="tile__value money"><?= e(money($s['gross_cents'])) ?></div>
    <div class="tile__meta"><?= (int) $s['orders_paid'] ?> paid orders · average <?= e(money($s['average_order_cents'])) ?></div>
  </div>
  <div class="tile tile--clay">
    <div class="tile__label">Less fees &amp; refunds</div>
    <div class="tile__value money">&minus;<?= e(money($s['fees_cents'] + $s['refunded_cents'])) ?></div>
    <div class="tile__meta"><?= e(money($s['fees_cents'])) ?> fees<?= $s['fees_are_estimated'] ? ' (part estimated)' : '' ?> · <?= e(money($s['refunded_cents'])) ?> refunded</div>
  </div>
  <div class="tile tile--gold">
    <div class="tile__label">Net income</div>
    <div class="tile__value money"><?= e(money($s['net_income_cents'])) ?></div>
    <div class="tile__meta">What actually reaches the account</div>
  </div>
  <div class="tile tile--error">
    <div class="tile__label">Expenditure</div>
    <div class="tile__value money"><?= e(money($s['expenses_total_cents'])) ?></div>
    <div class="tile__meta"><?= e(money($s['expenses_paid_cents'])) ?> paid · <?= e(money($s['expenses_committed_cents'])) ?> committed</div>
  </div>
  <div class="tile <?= $s['surplus_cents'] >= 0 ? 'tile--success' : 'tile--error' ?>">
    <div class="tile__label">Surplus / deficit</div>
    <div class="tile__value money <?= $s['surplus_cents'] >= 0 ? 'money--in' : 'money--out' ?>"><?= e(money($s['surplus_cents'])) ?></div>
    <div class="tile__meta">Net income less paid and committed costs</div>
  </div>
  <div class="tile tile--plum">
    <div class="tile__label">In the pipeline</div>
    <div class="tile__value money"><?= e(money($s['pending_cents'])) ?></div>
    <div class="tile__meta"><?= (int) $s['pending_orders'] ?> orders awaiting payment</div>
  </div>
</div>

<div class="admin-grid admin-grid--sidebar">
  <div>
    <div class="admin-panel">
      <h2>Income by category</h2>
      <div class="ledger-scroll">
      <table class="ledger">
        <thead><tr><th>Category</th><th class="numeric">Units</th><th class="numeric">Orders</th><th class="numeric">Received</th><th class="numeric">Share</th></tr></thead>
        <tbody>
          <?php $total = max(1, array_sum(array_column($income, 'gross_cents'))); ?>
          <?php foreach ($income as $row): ?>
            <tr>
              <td><?= e(ucfirst($row['category'])) ?></td>
              <td class="numeric"><?= (int) $row['units'] ?></td>
              <td class="numeric"><?= (int) $row['orders'] ?></td>
              <td class="numeric money"><?= e(money((int) $row['gross_cents'])) ?></td>
              <td class="numeric progress-cell">
                <?= (int) round(((int) $row['gross_cents'] / $total) * 100) ?>%
                <div class="bar"><span style="width:<?= max(1, (int) round(((int) $row['gross_cents'] / $total) * 100)) ?>%"></span></div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr><th>Gross</th><th class="numeric"></th><th class="numeric"><?= (int) $s['orders_paid'] ?></th><th class="numeric money"><?= e(money($s['gross_cents'])) ?></th><th></th></tr>
        </tfoot>
      </table>
      </div>
      <p class="muted" style="font-size:var(--step--1);margin-top:.5rem">
        Category totals are per line item, so an order containing a registration and a bed appears in both. The gross figure is the sum of order totals, after discounts.
      </p>
    </div>

    <div class="admin-panel">
      <h2>Income, day by day</h2>
      <?php View::include('partials.finance-spark', ['daily' => $daily, 'label' => 'Income per day']); ?>
    </div>

    <div class="admin-panel">
      <h2>Budget against actual</h2>
      <div class="ledger-scroll">
      <table class="ledger">
        <thead><tr><th>Line</th><th class="numeric">Budget</th><th class="numeric">Actual</th><th class="numeric">Variance</th></tr></thead>
        <tbody>
          <tr class="is-total">
            <td>Total income</td>
            <td class="numeric money"><?= e(money($budget['totals']['income_budget'])) ?></td>
            <td class="numeric money"><?= e(money($budget['totals']['income_actual'])) ?></td>
            <td class="numeric variance <?= $budget['totals']['income_actual'] >= $budget['totals']['income_budget'] ? 'variance--good' : 'variance--bad' ?>">
              <?= e(money($budget['totals']['income_actual'] - $budget['totals']['income_budget'])) ?>
            </td>
          </tr>
          <tr class="is-total">
            <td>Total expenditure</td>
            <td class="numeric money"><?= e(money($budget['totals']['expense_budget'])) ?></td>
            <td class="numeric money"><?= e(money($budget['totals']['expense_actual'])) ?></td>
            <td class="numeric variance <?= $budget['totals']['expense_actual'] <= $budget['totals']['expense_budget'] ? 'variance--good' : 'variance--bad' ?>">
              <?= e(money($budget['totals']['expense_budget'] - $budget['totals']['expense_actual'])) ?>
            </td>
          </tr>
          <tr class="is-total">
            <td><strong>Surplus</strong></td>
            <td class="numeric money"><?= e(money($budget['totals']['budget_surplus'])) ?></td>
            <td class="numeric money"><?= e(money($budget['totals']['actual_surplus'])) ?></td>
            <td class="numeric"></td>
          </tr>
        </tbody>
      </table>
      </div>
      <a class="btn btn--sm btn--ghost" style="margin-top:.75rem" href="<?= e(url('/admin/finance/budget')) ?>">Line by line</a>
    </div>

    <div class="admin-panel">
      <h2>Expenditure by category</h2>
      <div class="ledger-scroll">
      <table class="ledger">
        <thead><tr><th>Category</th><th class="numeric">Paid</th><th class="numeric">Committed</th><th class="numeric">Planned</th></tr></thead>
        <tbody>
          <?php foreach ($expenses as $row): ?>
            <tr>
              <td><?= e($row['category']) ?></td>
              <td class="numeric money"><?= e(money((int) $row['paid_cents'])) ?></td>
              <td class="numeric money"><?= e(money((int) $row['committed_cents'])) ?></td>
              <td class="numeric money muted"><?= e(money((int) $row['planned_cents'])) ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if ($expenses === []): ?><tr><td colspan="4" class="muted">Nothing recorded in this period.</td></tr><?php endif; ?>
        </tbody>
      </table>
      </div>
      <a class="btn btn--sm btn--ghost" style="margin-top:.75rem" href="<?= e(url('/admin/finance/expenses')) ?>">Manage expenses</a>
    </div>
  </div>

  <div>
    <?php if ($exceptions !== []): ?>
      <div class="admin-panel" style="border-left:4px solid var(--error)">
        <h2>Needs a look</h2>
        <p class="muted" style="font-size:var(--step--1)"><?= count($exceptions) ?> payment(s) do not reconcile cleanly.</p>
        <a class="btn btn--sm" href="<?= e(url('/admin/finance/reconciliation')) ?>">Open reconciliation</a>
      </div>
    <?php endif; ?>

    <div class="admin-panel">
      <h2>Cash position</h2>
      <div class="summary__row"><span>Received</span><strong class="money"><?= e(money($s['gross_cents'])) ?></strong></div>
      <div class="summary__row"><span>PayFast fees</span><strong class="money money--out">&minus;<?= e(money($s['fees_cents'])) ?></strong></div>
      <div class="summary__row"><span>Refunds paid</span><strong class="money money--out">&minus;<?= e(money($s['refunded_cents'])) ?></strong></div>
      <div class="summary__row"><span>Expenses paid</span><strong class="money money--out">&minus;<?= e(money($s['expenses_paid_cents'])) ?></strong></div>
      <div class="summary__row summary__row--total">
        <span>Cash on hand</span>
        <span class="money <?= $s['cash_surplus_cents'] >= 0 ? 'money--in' : 'money--out' ?>"><?= e(money($s['cash_surplus_cents'])) ?></span>
      </div>
      <p class="muted" style="font-size:var(--step--1);margin-top:.6rem">
        Committed costs of <?= e(money($s['expenses_committed_cents'])) ?> are not deducted here — they are owed, not yet paid.
      </p>
      <?php if ($s['fees_are_estimated']): ?>
        <p class="muted" style="font-size:var(--step--1)">
          <strong>Note:</strong> <?= (int) $fees['without_fee'] ?> payment(s) have no fee reported by PayFast, so part of the fee figure is
          estimated at <?= e(SettingsService::get('payfast_fee_percent', '3.5')) ?>% + <?= e(money(rands(SettingsService::get('payfast_fee_fixed', '2.00')))) ?>.
          Reported <?= e(money($fees['reported_cents'])) ?>, estimated <?= e(money($fees['estimated_cents'])) ?>.
        </p>
      <?php endif; ?>
    </div>

    <?php if ($vat !== null): ?>
      <div class="admin-panel">
        <h2>VAT</h2>
        <div class="summary__row"><span>Gross (incl. VAT)</span><strong class="money"><?= e(money($vat['gross_cents'])) ?></strong></div>
        <div class="summary__row"><span>Excluding VAT</span><strong class="money"><?= e(money($vat['exclusive_cents'])) ?></strong></div>
        <div class="summary__row summary__row--total"><span>VAT at <?= e((string) $vat['rate']) ?>%</span><span class="money"><?= e(money($vat['vat_cents'])) ?></span></div>
        <p class="muted" style="font-size:var(--step--1);margin-top:.6rem">Prices are treated as VAT-inclusive. Turn this off in Settings if the committee is not registered.</p>
      </div>
    <?php endif; ?>

    <?php if ($upcoming !== []): ?>
      <div class="admin-panel">
        <h2>Bills coming up</h2>
        <?php foreach ($upcoming as $bill): ?>
          <div class="summary__row">
            <span><?= e($bill['description']) ?><br><span class="muted"><?= e((string) ($bill['supplier'] ?? '')) ?><?= $bill['due_on'] ? ' · due ' . e(za_date((string) $bill['due_on'], 'j M Y')) : '' ?></span></span>
            <strong class="money"><?= e(money((int) $bill['amount_cents'])) ?></strong>
          </div>
        <?php endforeach; ?>
        <a class="btn btn--sm btn--ghost" style="margin-top:.6rem" href="<?= e(url('/admin/finance/expenses')) ?>">All expenses</a>
      </div>
    <?php endif; ?>

    <?php if ($donations !== []): ?>
      <div class="admin-panel">
        <h2>Donations</h2>
        <?php foreach ($donations as $row): ?>
          <div class="summary__row">
            <span><?= e($row['donation_type']) ?><br><span class="muted"><?= (int) $row['count'] ?> gift(s)<?= (int) $row['anonymous'] > 0 ? ', ' . (int) $row['anonymous'] . ' anonymous' : '' ?></span></span>
            <strong class="money"><?= e(money((int) $row['gross_cents'])) ?></strong>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ($discounts !== []): ?>
      <div class="admin-panel">
        <h2>What discounts cost</h2>
        <?php foreach ($discounts as $row): ?>
          <div class="summary__row">
            <span><?= e($row['code']) ?><br><span class="muted"><?= (int) $row['orders'] ?> order(s)</span></span>
            <strong class="money money--out">&minus;<?= e(money((int) $row['cents'])) ?></strong>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="admin-panel">
      <h2>Stock still on hand</h2>
      <div class="summary__row summary__row--total"><span>Retail value</span><span class="money"><?= e(money($stock['value_cents'])) ?></span></div>
      <p class="muted" style="font-size:var(--step--1);margin-top:.6rem">
        Valued at selling price, not cost. Useful for knowing what is left to sell at the convention, not for the balance sheet.
      </p>
    </div>
  </div>
</div>
<?php View::stop(); ?>

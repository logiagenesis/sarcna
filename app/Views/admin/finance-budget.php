<?php
use App\Core\View;
View::layout('layouts.admin');
View::start('content');

$totals = $budget['totals'];

/** Renders one side of the budget: income lines or expense lines. */
$renderSide = static function (string $kind, array $rows, int $budgetTotal, int $actualTotal): void {
    $isIncome = $kind === 'income';
    ?>
    <div class="admin-panel" style="padding:0">
      <div class="admin-panel__head" style="padding:1rem 1.1rem">
        <h2><?= $isIncome ? 'Income' : 'Expenditure' ?></h2>
        <p class="muted"><?= $isIncome
            ? 'Budgeted against money PayFast has actually confirmed.'
            : 'Budgeted against expenses paid plus expenses committed.' ?></p>
      </div>
      <div class="table-wrap" style="border:0">
        <table class="ledger">
          <thead>
            <tr>
              <th>Category</th>
              <th class="numeric">Budget</th>
              <th class="numeric">Actual</th>
              <th class="numeric">Variance</th>
              <th class="progress-cell">Progress</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($rows === []): ?>
              <tr><td colspan="5" class="muted">No <?= e($kind) ?> lines have been budgeted yet. Add one on the right.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $row): ?>
              <?php
                $variance = (int) $row['variance_cents'];
                $percent  = $row['percent'];
                $good     = $variance >= 0;
              ?>
              <tr>
                <td>
                  <strong><?= e($row['category']) ?></strong>
                  <?php if ($row['description']): ?><br><span class="muted"><?= e($row['description']) ?></span><?php endif; ?>
                  <?php if ($row['notes']): ?><br><span class="muted"><?= e(excerpt((string) $row['notes'], 90)) ?></span><?php endif; ?>
                </td>
                <td class="numeric money"><?= e(money((int) $row['budgeted_cents'])) ?></td>
                <td class="numeric money"><?= e(money((int) $row['actual_cents'])) ?></td>
                <td class="numeric variance <?= $good ? 'variance--good' : 'variance--bad' ?>">
                  <?= $variance >= 0 ? '+' : '−' ?><?= e(money(abs($variance))) ?>
                </td>
                <td class="progress-cell">
                  <?php if ($percent === null): ?>
                    <span class="muted">no budget set</span>
                  <?php else: ?>
                    <span class="muted"><?= (int) $percent ?>%</span>
                    <div class="bar <?= (!$isIncome && $percent > 100) ? 'bar--error' : ((!$isIncome && $percent > 90) ? 'bar--warning' : '') ?>">
                      <span style="width:<?= max(0, min(100, (int) $percent)) ?>%"></span>
                    </div>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr>
              <th>Total <?= e($kind) ?></th>
              <td class="numeric money"><?= e(money($budgetTotal)) ?></td>
              <td class="numeric money"><?= e(money($actualTotal)) ?></td>
              <?php $t = $isIncome ? $actualTotal - $budgetTotal : $budgetTotal - $actualTotal; ?>
              <td class="numeric variance <?= $t >= 0 ? 'variance--good' : 'variance--bad' ?>"><?= $t >= 0 ? '+' : '−' ?><?= e(money(abs($t))) ?></td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
    <?php
};
?>
<div class="admin-head">
  <div>
    <h1>Budget vs actual</h1>
    <p>What the committee planned against what has actually happened. <?= e($period['label']) ?>.</p>
  </div>
  <a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/export/budget')) ?>">Export CSV</a>
</div>

<?php View::include('partials.finance-tabs'); ?>
<?php View::include('partials.finance-period', ['period' => $period, 'action' => '/admin/finance/budget']); ?>

<div class="tiles">
  <div class="tile tile--success">
    <div class="tile__label">Budgeted surplus</div>
    <div class="tile__value money"><?= e(money((int) $totals['budget_surplus'])) ?></div>
    <div class="tile__meta"><?= e(money((int) $totals['income_budget'])) ?> in, <?= e(money((int) $totals['expense_budget'])) ?> out</div>
  </div>
  <div class="tile <?= (int) $totals['actual_surplus'] >= 0 ? 'tile--success' : 'tile--error' ?>">
    <div class="tile__label">Actual surplus so far</div>
    <div class="tile__value money"><?= e(money((int) $totals['actual_surplus'])) ?></div>
    <div class="tile__meta"><?= e(money((int) $totals['income_actual'])) ?> in, <?= e(money((int) $totals['expense_actual'])) ?> out</div>
  </div>
  <div class="tile tile--gold">
    <div class="tile__label">Income collected</div>
    <?php $incomePct = (int) $totals['income_budget'] > 0 ? (int) round(((int) $totals['income_actual'] / (int) $totals['income_budget']) * 100) : 0; ?>
    <div class="tile__value"><?= $incomePct ?>%</div>
    <div class="bar"><span style="width:<?= max(0, min(100, $incomePct)) ?>%"></span></div>
    <div class="tile__meta">of the income budget</div>
  </div>
  <div class="tile tile--clay">
    <div class="tile__label">Budget spent</div>
    <?php $spendPct = (int) $totals['expense_budget'] > 0 ? (int) round(((int) $totals['expense_actual'] / (int) $totals['expense_budget']) * 100) : 0; ?>
    <div class="tile__value"><?= $spendPct ?>%</div>
    <div class="bar <?= $spendPct > 100 ? 'bar--error' : ($spendPct > 90 ? 'bar--warning' : '') ?>"><span style="width:<?= max(0, min(100, $spendPct)) ?>%"></span></div>
    <div class="tile__meta">of the expenditure budget</div>
  </div>
</div>

<p class="finance-note">
  <strong>How to read this page.</strong> Income actuals count only orders PayFast has confirmed as paid — nothing
  pending, nothing hoped for. Expense actuals count what has been paid <em>plus</em> what has been committed, because a
  signed venue quote is money the convention owes whether or not it has left the bank yet. A positive variance is
  always good news: more income than budgeted, or less spent than budgeted.
</p>

<div class="admin-grid admin-grid--sidebar">
  <div class="stack-m">
    <?php $renderSide('income', $budget['income'], (int) $totals['income_budget'], (int) $totals['income_actual']); ?>
    <?php $renderSide('expense', $budget['expense'], (int) $totals['expense_budget'], (int) $totals['expense_actual']); ?>
  </div>

  <aside class="stack-m">
    <div class="admin-panel">
      <h2>Add a budget line</h2>
      <form method="post" action="<?= e(url('/admin/finance/budget')) ?>">
        <?= csrf_field() ?>
        <div class="field">
          <label class="field__label" for="budget-kind">Side of the budget</label>
          <select id="budget-kind" name="kind" required>
            <option value="income">Income</option>
            <option value="expense">Expenditure</option>
          </select>
        </div>
        <div class="field">
          <label class="field__label" for="budget-category">Category</label>
          <input id="budget-category" type="text" name="category" required placeholder="Venue, Registration, Catering…">
          <p class="field__hint">For income lines, name it after the item type — registration, accommodation, transport, merchandise or donation — so actuals match automatically.</p>
        </div>
        <div class="field">
          <label class="field__label" for="budget-description">Description</label>
          <input id="budget-description" type="text" name="description" placeholder="Optional">
        </div>
        <div class="field">
          <label class="field__label" for="budget-amount">Budgeted amount (R)</label>
          <input id="budget-amount" type="number" step="0.01" min="0" name="budgeted" required>
        </div>
        <div class="field">
          <label class="field__label" for="budget-notes">Notes</label>
          <textarea id="budget-notes" name="notes" rows="2"></textarea>
        </div>
        <div class="field">
          <label class="field__label" for="budget-sort">Sort order</label>
          <input id="budget-sort" type="number" name="sort_order" value="0">
        </div>
        <button class="btn" type="submit">Add line</button>
      </form>
    </div>

    <div class="admin-panel">
      <h2>Edit existing lines</h2>
      <?php if ($lines === []): ?>
        <p class="muted">Nothing budgeted yet.</p>
      <?php endif; ?>
      <?php foreach ($lines as $line): ?>
        <details class="budget-line">
          <summary>
            <span class="badge"><?= e($line['kind']) ?></span>
            <span class="budget-line__name"><?= e($line['category']) ?></span>
            <span class="budget-line__amount money"><?= e(money((int) $line['budgeted_cents'])) ?></span>
          </summary>
          <form method="post" action="<?= e(url('/admin/finance/budget')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $line['id'] ?>">
            <input type="hidden" name="kind" value="<?= e($line['kind']) ?>">
            <div class="field"><label class="field__label">Category</label><input type="text" name="category" value="<?= e($line['category']) ?>" required></div>
            <div class="field"><label class="field__label">Description</label><input type="text" name="description" value="<?= e((string) $line['description']) ?>"></div>
            <div class="field"><label class="field__label">Budgeted (R)</label><input type="number" step="0.01" min="0" name="budgeted" value="<?= number_format(((int) $line['budgeted_cents']) / 100, 2, '.', '') ?>" required></div>
            <div class="field"><label class="field__label">Notes</label><textarea name="notes" rows="2"><?= e((string) $line['notes']) ?></textarea></div>
            <div class="field"><label class="field__label">Sort order</label><input type="number" name="sort_order" value="<?= (int) $line['sort_order'] ?>"></div>
            <button class="btn btn--sm" type="submit">Save</button>
          </form>
          <form method="post" action="<?= e(url('/admin/finance/budget/' . $line['id'] . '/delete')) ?>" style="margin-top:.5rem"
                data-confirm="Remove the budget line &ldquo;<?= e($line['category']) ?>&rdquo;?">
            <?= csrf_field() ?>
            <button class="btn btn--sm btn--ghost" type="submit">Delete this line</button>
          </form>
        </details>
      <?php endforeach; ?>
    </div>
  </aside>
</div>
<?php View::stop(); ?>

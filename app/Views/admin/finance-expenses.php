<?php
use App\Core\View;
View::layout('layouts.admin');
View::start('content');
?>
<div class="admin-head">
  <div><h1>Expenses</h1><p>What the convention has spent and committed. <?= e($period['label']) ?>.</p></div>
  <a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/export/expenses')) ?>">Export CSV</a>
</div>

<?php View::include('partials.finance-tabs'); ?>
<?php View::include('partials.finance-period', ['period' => $period, 'action' => '/admin/finance/expenses']); ?>

<div class="tiles">
  <div class="tile tile--error"><div class="tile__label">Paid</div><div class="tile__value money"><?= e(money($totals['paid_cents'])) ?></div></div>
  <div class="tile tile--clay"><div class="tile__label">Committed, not yet paid</div><div class="tile__value money"><?= e(money($totals['committed_cents'])) ?></div></div>
  <div class="tile"><div class="tile__label">Planned</div><div class="tile__value money"><?= e(money($totals['planned_cents'])) ?></div><div class="tile__meta">Not yet committed</div></div>
  <div class="tile tile--gold"><div class="tile__label">On the hook for</div><div class="tile__value money"><?= e(money($totals['total_cents'])) ?></div><div class="tile__meta">Paid plus committed</div></div>
</div>

<div class="admin-grid admin-grid--sidebar">
  <div>
    <div class="admin-panel" style="padding:0">
      <div class="table-wrap" style="border:0">
        <table class="ledger">
          <thead><tr><th>Date</th><th>Description</th><th>Category</th><th>Supplier</th><th class="numeric">Amount</th><th>Status</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($result['rows'] as $expense): ?>
              <tr>
                <td><?= e(za_date((string) $expense['incurred_on'], 'j M Y')) ?>
                    <?php if ($expense['due_on'] && !$expense['paid_on']): ?><br><span class="muted">due <?= e(za_date((string) $expense['due_on'], 'j M')) ?></span><?php endif; ?></td>
                <td><strong><?= e($expense['description']) ?></strong>
                    <?php if ($expense['invoice_number']): ?><br><span class="muted">inv <?= e($expense['invoice_number']) ?></span><?php endif; ?>
                    <?php if ($expense['notes']): ?><br><span class="muted"><?= e(excerpt($expense['notes'], 80)) ?></span><?php endif; ?></td>
                <td><?= e((string) ($expense['category_name'] ?? '—')) ?></td>
                <td><?= e((string) $expense['supplier']) ?></td>
                <td class="numeric money"><?= e(money((int) $expense['amount_cents'])) ?></td>
                <td><span class="badge <?= $expense['status'] === 'paid' ? 'badge--success' : ($expense['status'] === 'planned' ? '' : 'badge--warning') ?>"><?= e($expense['status']) ?></span></td>
                <td>
                  <details>
                    <summary style="cursor:pointer;font-size:var(--step--1)">Edit</summary>
                    <form method="post" action="<?= e(url('/admin/finance/expenses')) ?>" style="margin-top:.5rem;min-width:260px">
                      <?= csrf_field() ?>
                      <input type="hidden" name="id" value="<?= (int) $expense['id'] ?>">
                      <div class="field"><label class="field__label">Description</label><input type="text" name="description" value="<?= e($expense['description']) ?>" required></div>
                      <div class="field"><label class="field__label">Supplier</label><input type="text" name="supplier" value="<?= e((string) $expense['supplier']) ?>"></div>
                      <div class="field">
                        <label class="field__label">Category</label>
                        <select name="category_id">
                          <option value="">Uncategorised</option>
                          <?php foreach ($categories as $category): ?>
                            <option value="<?= (int) $category['id'] ?>" <?= (int) $expense['category_id'] === (int) $category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="field"><label class="field__label">Amount (R)</label><input type="number" step="0.01" min="0" name="amount" value="<?= number_format(((int) $expense['amount_cents']) / 100, 2, '.', '') ?>" required></div>
                      <div class="field"><label class="field__label">Date incurred</label><input type="date" name="incurred_on" value="<?= e((string) $expense['incurred_on']) ?>" required></div>
                      <div class="field"><label class="field__label">Due</label><input type="date" name="due_on" value="<?= e((string) $expense['due_on']) ?>"></div>
                      <div class="field"><label class="field__label">Paid on</label><input type="date" name="paid_on" value="<?= e((string) $expense['paid_on']) ?>"></div>
                      <div class="field">
                        <label class="field__label">Status</label>
                        <select name="status">
                          <?php foreach (['planned','committed','invoiced','paid','cancelled'] as $option): ?>
                            <option value="<?= $option ?>" <?= $expense['status'] === $option ? 'selected' : '' ?>><?= e(ucfirst($option)) ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="field"><label class="field__label">Invoice number</label><input type="text" name="invoice_number" value="<?= e((string) $expense['invoice_number']) ?>"></div>
                      <div class="field"><label class="field__label">Notes</label><textarea name="notes" rows="2" style="min-height:60px"><?= e((string) $expense['notes']) ?></textarea></div>
                      <button class="btn btn--sm btn--block" type="submit">Save</button>
                    </form>
                    <form method="post" action="<?= e(url('/admin/finance/expenses/' . $expense['id'] . '/delete')) ?>" data-confirm="Remove this expense?" style="margin-top:.4rem">
                      <?= csrf_field() ?>
                      <button class="btn btn--sm btn--ghost btn--block" type="submit">Remove</button>
                    </form>
                  </details>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if ($result['rows'] === []): ?><tr><td colspan="7" class="muted">No expenses recorded in this period.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php View::include('partials.pagination', ['result' => $result, 'query' => ['period' => $period['key'], 'from' => $period['from'], 'to' => $period['to'], 'status' => $status]]); ?>

    <div class="admin-panel">
      <h2>By category</h2>
      <div class="ledger-scroll">
      <table class="ledger">
        <thead><tr><th>Category</th><th class="numeric">Paid</th><th class="numeric">Committed</th><th class="numeric">Planned</th></tr></thead>
        <tbody>
          <?php foreach ($byCategory as $row): ?>
            <tr>
              <td><?= e($row['category']) ?></td>
              <td class="numeric money"><?= e(money((int) $row['paid_cents'])) ?></td>
              <td class="numeric money"><?= e(money((int) $row['committed_cents'])) ?></td>
              <td class="numeric money muted"><?= e(money((int) $row['planned_cents'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    </div>
  </div>

  <div>
    <div class="admin-panel">
      <h2>Record an expense</h2>
      <form method="post" action="<?= e(url('/admin/finance/expenses')) ?>">
        <?= csrf_field() ?>
        <div class="field"><label class="field__label" for="description">What was it for</label><input type="text" id="description" name="description" required placeholder="Venue deposit, second instalment"></div>
        <div class="field"><label class="field__label" for="supplier">Supplier</label><input type="text" id="supplier" name="supplier"></div>
        <div class="field">
          <label class="field__label" for="category_id">Category</label>
          <select id="category_id" name="category_id">
            <option value="">Uncategorised</option>
            <?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>"><?= e($category['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="field"><label class="field__label" for="amount">Amount (R)</label><input type="number" step="0.01" min="0" id="amount" name="amount" required></div>
        <div class="field"><label class="field__label" for="incurred_on">Date incurred</label><input type="date" id="incurred_on" name="incurred_on" value="<?= e(date('Y-m-d')) ?>" required></div>
        <div class="field"><label class="field__label" for="due_on">Due date</label><input type="date" id="due_on" name="due_on"></div>
        <div class="field">
          <label class="field__label" for="status">Status</label>
          <select id="status" name="status">
            <option value="committed">Committed — we owe it</option>
            <option value="planned">Planned — budgeted, not committed</option>
            <option value="invoiced">Invoiced</option>
            <option value="paid">Paid</option>
          </select>
        </div>
        <div class="field"><label class="field__label" for="paid_on">Paid on</label><input type="date" id="paid_on" name="paid_on"></div>
        <div class="field"><label class="field__label" for="payment_method">Paid by</label><input type="text" id="payment_method" name="payment_method" placeholder="EFT, card, cash"></div>
        <div class="field"><label class="field__label" for="invoice_number">Invoice number</label><input type="text" id="invoice_number" name="invoice_number"></div>
        <div class="field"><label class="field__label" for="notes">Notes</label><textarea id="notes" name="notes" rows="2" style="min-height:60px"></textarea></div>
        <button class="btn btn--block btn--sm" type="submit">Record expense</button>
      </form>
    </div>

    <?php if ($upcoming !== []): ?>
      <div class="admin-panel">
        <h2>Owed, not yet paid</h2>
        <?php foreach ($upcoming as $bill): ?>
          <div class="summary__row">
            <span><?= e($bill['description']) ?><br><span class="muted"><?= $bill['due_on'] ? 'due ' . e(za_date((string) $bill['due_on'], 'j M Y')) : 'no due date' ?></span></span>
            <strong class="money"><?= e(money((int) $bill['amount_cents'])) ?></strong>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php View::stop(); ?>

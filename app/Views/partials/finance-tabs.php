<div class="admin-tabs">
  <a href="<?= e(url('/admin/finance')) ?>" class="<?= is_active('/admin/finance', true) ? 'is-active' : '' ?>">Overview</a>
  <a href="<?= e(url('/admin/finance/income')) ?>" class="<?= is_active('/admin/finance/income') ? 'is-active' : '' ?>">Income</a>
  <a href="<?= e(url('/admin/finance/expenses')) ?>" class="<?= is_active('/admin/finance/expenses') ? 'is-active' : '' ?>">Expenses</a>
  <a href="<?= e(url('/admin/finance/budget')) ?>" class="<?= is_active('/admin/finance/budget') ? 'is-active' : '' ?>">Budget vs actual</a>
  <a href="<?= e(url('/admin/finance/reconciliation')) ?>" class="<?= is_active('/admin/finance/reconciliation') ? 'is-active' : '' ?>">Bank reconciliation</a>
  <a href="<?= e(url('/admin/finance/refunds')) ?>" class="<?= is_active('/admin/finance/refunds') ? 'is-active' : '' ?>">Refunds</a>
</div>

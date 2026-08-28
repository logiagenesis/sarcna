<?php
use App\Core\View;
View::layout('layouts.admin');
View::start('content');
?>
<div class="admin-head">
  <div><h1>Income</h1><p>Where every rand came from. <?= e($period['label']) ?>.</p></div>
  <div class="cluster">
    <a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/export/orders')) ?>">Orders CSV</a>
    <a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/export/order-items')) ?>">Line items CSV</a>
    <button class="btn btn--sm" type="button" onclick="window.print()">Print</button>
  </div>
</div>

<?php View::include('partials.finance-tabs'); ?>
<?php View::include('partials.finance-period', ['period' => $period, 'action' => '/admin/finance/income']); ?>

<div class="tiles">
  <div class="tile tile--success"><div class="tile__label">Received</div><div class="tile__value money"><?= e(money($summary['gross_cents'])) ?></div><div class="tile__meta"><?= (int) $summary['orders_paid'] ?> orders</div></div>
  <div class="tile"><div class="tile__label">Before discounts</div><div class="tile__value money"><?= e(money($summary['subtotal_cents'])) ?></div><div class="tile__meta"><?= e(money($summary['discount_cents'])) ?> given away</div></div>
  <div class="tile tile--gold"><div class="tile__label">Average order</div><div class="tile__value money"><?= e(money($summary['average_order_cents'])) ?></div></div>
  <div class="tile tile--plum"><div class="tile__label">Awaiting payment</div><div class="tile__value money"><?= e(money($summary['pending_cents'])) ?></div><div class="tile__meta"><?= (int) $summary['pending_orders'] ?> orders</div></div>
  <div class="tile tile--error"><div class="tile__label">Lost</div><div class="tile__value money"><?= e(money($summary['lost_cents'])) ?></div><div class="tile__meta"><?= (int) $summary['lost_orders'] ?> failed or cancelled</div></div>
</div>

<div class="admin-panel">
  <h2>Income, day by day</h2>
  <?php View::include('partials.finance-spark', ['daily' => array_slice($daily, 0, 45), 'label' => 'Income per day']); ?>
</div>

<div class="admin-grid admin-grid--2">
  <div class="admin-panel">
    <h2>By category</h2>
    <div class="ledger-scroll">
    <table class="ledger">
      <thead><tr><th>Category</th><th class="numeric">Units</th><th class="numeric">Received</th></tr></thead>
      <tbody>
        <?php foreach ($income as $row): ?>
          <tr><td><?= e(ucfirst($row['category'])) ?></td><td class="numeric"><?= (int) $row['units'] ?></td><td class="numeric money"><?= e(money((int) $row['gross_cents'])) ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  </div>

  <div class="admin-panel">
    <h2>Accommodation</h2>
    <div class="ledger-scroll">
    <table class="ledger">
      <thead><tr><th>Room type</th><th class="numeric">Bed-nights</th><th class="numeric">Received</th></tr></thead>
      <tbody>
        <?php foreach ($accommodation as $row): ?>
          <tr><td><?= e($row['room_type']) ?></td><td class="numeric"><?= (int) $row['bed_nights'] ?></td><td class="numeric money"><?= e(money((int) $row['gross_cents'])) ?></td></tr>
        <?php endforeach; ?>
        <?php if ($accommodation === []): ?><tr><td colspan="3" class="muted">No accommodation sold in this period.</td></tr><?php endif; ?>
      </tbody>
    </table>
    </div>
  </div>

  <div class="admin-panel">
    <h2>Transport</h2>
    <div class="ledger-scroll">
    <table class="ledger">
      <thead><tr><th>Route</th><th class="numeric">Passengers</th><th class="numeric">Received</th></tr></thead>
      <tbody>
        <?php foreach ($transport as $row): ?>
          <tr><td><?= e($row['route']) ?></td><td class="numeric"><?= (int) $row['passengers'] ?></td><td class="numeric money"><?= e(money((int) $row['gross_cents'])) ?></td></tr>
        <?php endforeach; ?>
        <?php if ($transport === []): ?><tr><td colspan="3" class="muted">No shuttle seats sold in this period.</td></tr><?php endif; ?>
      </tbody>
    </table>
    </div>
  </div>

  <div class="admin-panel">
    <h2>Donations</h2>
    <div class="ledger-scroll">
    <table class="ledger">
      <thead><tr><th>Type</th><th class="numeric">Gifts</th><th class="numeric">Received</th></tr></thead>
      <tbody>
        <?php foreach ($donations as $row): ?>
          <tr><td><?= e($row['donation_type']) ?></td><td class="numeric"><?= (int) $row['count'] ?></td><td class="numeric money"><?= e(money((int) $row['gross_cents'])) ?></td></tr>
        <?php endforeach; ?>
        <?php if ($donations === []): ?><tr><td colspan="3" class="muted">No donations in this period.</td></tr><?php endif; ?>
      </tbody>
    </table>
    </div>
  </div>
</div>

<div class="admin-panel">
  <h2>Every product</h2>
  <div class="table-wrap" style="border:0">
    <table class="ledger">
      <thead><tr><th>Product</th><th>Type</th><th class="numeric">Units sold</th><th class="numeric">Received</th><th class="numeric">Stock left</th></tr></thead>
      <tbody>
        <?php foreach ($products as $row): ?>
          <tr>
            <td><a href="<?= e(url('/admin/products/' . $row['id'])) ?>"><?= e($row['name']) ?></a></td>
            <td><span class="badge"><?= e(str_replace('_', ' ', (string) $row['type'])) ?></span></td>
            <td class="numeric"><?= (int) $row['units'] ?></td>
            <td class="numeric money"><?= e(money((int) $row['gross_cents'])) ?></td>
            <td class="numeric"><?= (int) $row['track_stock'] === 1 ? (int) $row['stock'] : '—' ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if ($products === []): ?><tr><td colspan="5" class="muted">Nothing sold in this period.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($discounts !== []): ?>
  <div class="admin-panel">
    <h2>Discounts given</h2>
    <div class="ledger-scroll">
    <table class="ledger">
      <thead><tr><th>Code</th><th class="numeric">Orders</th><th class="numeric">Cost</th></tr></thead>
      <tbody>
        <?php foreach ($discounts as $row): ?>
          <tr><td><?= e($row['code']) ?></td><td class="numeric"><?= (int) $row['orders'] ?></td><td class="numeric money money--out">&minus;<?= e(money((int) $row['cents'])) ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  </div>
<?php endif; ?>
<?php View::stop(); ?>

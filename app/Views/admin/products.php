<?php
use App\Core\View;
use App\Services\ProductService;
View::layout('layouts.admin');
View::start('content');
?>
<div class="admin-head">
  <div><h1>Products &amp; stock</h1><p><?= (int) $result['total'] ?> products</p></div>
  <div class="cluster">
    <a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/export/stock')) ?>">Export stock</a>
    <a class="btn btn--sm" href="<?= e(url('/admin/products/create')) ?>">New product</a>
  </div>
</div>

<?php if ($lowStock !== []): ?>
  <div class="alert alert--warning">
    <div><div class="alert__title">Low stock</div>
    <p><?php foreach ($lowStock as $i => $product): ?><?= $i ? ', ' : '' ?><a href="<?= e(url('/admin/products/' . $product['id'])) ?>"><?= e($product['name']) ?></a> (<?= (int) $product['stock'] ?>)<?php endforeach; ?></p></div>
  </div>
<?php endif; ?>

<div class="admin-toolbar">
  <form method="get" action="<?= e(url('/admin/products')) ?>" data-autosubmit>
    <input type="search" name="q" value="<?= e($search) ?>" placeholder="Name or SKU">
    <select name="type">
      <option value="">Any type</option>
      <?php foreach (['registration', 'day_pass', 'merchandise', 'transport', 'donation', 'other'] as $value): ?>
        <option value="<?= $value ?>" <?= $type === $value ? 'selected' : '' ?>><?= e(ucfirst(str_replace('_', ' ', $value))) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn--sm" type="submit">Filter</button>
    <a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/products')) ?>">Clear</a>
  </form>
</div>

<div class="admin-panel" style="padding:0">
  <div class="table-wrap" style="border:0">
    <table class="admin-table">
      <thead><tr><th>Product</th><th>Type</th><th class="numeric">Price</th><th class="numeric">Stock</th><th class="numeric">Sold</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($result['rows'] as $product): ?>
          <tr>
            <td><a href="<?= e(url('/admin/products/' . $product['id'])) ?>"><strong><?= e($product['name']) ?></strong></a>
                <?php if ((int) $product['variant_count'] > 0): ?><br><span class="muted"><?= (int) $product['variant_count'] ?> variants</span><?php endif; ?></td>
            <td><span class="badge"><?= e(str_replace('_', ' ', (string) $product['type'])) ?></span></td>
            <td class="numeric">
              <?php if ((int) $product['allows_custom_amount'] === 1): ?>Any
              <?php else: ?>
                <?php if (ProductService::isOnSale($product)): ?><s class="muted"><?= e(money((int) $product['price_cents'])) ?></s><br><?php endif; ?>
                <?= e(money(ProductService::priceFor($product))) ?>
              <?php endif; ?>
            </td>
            <td class="numeric">
              <?php if ((int) $product['track_stock'] === 0): ?><span class="muted">Not tracked</span>
              <?php else: ?>
                <span style="color:<?= (int) $product['stock'] <= 0 ? 'var(--error)' : ((int) $product['stock'] <= (int) $product['low_stock_threshold'] ? 'var(--warning)' : 'inherit') ?>">
                  <?= (int) $product['stock'] ?>
                </span>
              <?php endif; ?>
            </td>
            <td class="numeric"><?= (int) $product['sold'] ?></td>
            <td><?= (int) $product['is_active'] === 1 ? '<span class="badge badge--success">Live</span>' : '<span class="badge">Hidden</span>' ?>
                <?= mock_badge((int) $product['is_mock'] === 1) ?></td>
            <td><a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/products/' . $product['id'])) ?>">Edit</a></td>
          </tr>
        <?php endforeach; ?>
        <?php if ($result['rows'] === []): ?><tr><td colspan="7" class="muted">No products match.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php View::include('partials.pagination', ['result' => $result, 'query' => ['q' => $search, 'type' => $type]]); ?>
<?php View::stop(); ?>

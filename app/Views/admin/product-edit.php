<?php
use App\Core\View;
View::layout('layouts.admin');
View::start('content');
$isNew = $product === null;
$value = static fn (string $key, mixed $default = '') => e($product[$key] ?? $default);
$rand  = static fn (?int $cents): string => $cents === null ? '' : number_format($cents / 100, 2, '.', '');
?>
<div class="admin-head">
  <div>
    <a class="link-arrow" href="<?= e(url('/admin/products')) ?>">&larr; Products</a>
    <h1 style="margin-top:.5rem"><?= $isNew ? 'New product' : e($product['name']) ?></h1>
    <?php if (!$isNew): ?><p><?= (int) ($sold ?? 0) ?> sold to date</p><?php endif; ?>
  </div>
  <?php if (!$isNew): ?>
    <div class="cluster">
      <a class="btn btn--sm btn--ghost" href="<?= e(url('/shop/' . $product['slug'])) ?>" target="_blank">View on the site</a>
      <form method="post" action="<?= e(url('/admin/products/' . $product['id'] . '/delete')) ?>" data-confirm="Delete this product? If it has ever been sold it is deactivated instead.">
        <?= csrf_field() ?>
        <button class="btn btn--sm btn--ghost" type="submit">Delete</button>
      </form>
    </div>
  <?php endif; ?>
</div>

<div class="admin-grid admin-grid--sidebar">
  <div>
    <form method="post" action="<?= e(url($isNew ? '/admin/products' : '/admin/products/' . $product['id'])) ?>" enctype="multipart/form-data">
      <?= csrf_field() ?>

      <div class="admin-panel">
        <h2>Basics</h2>
        <div class="field">
          <label class="field__label" for="name">Name</label>
          <input type="text" id="name" name="name" required value="<?= $value('name') ?>" data-slug-source="slug">
        </div>
        <div class="field-row field-row--3">
          <div class="field">
            <label class="field__label" for="type">Type</label>
            <select id="type" name="type">
              <?php foreach (['registration' => 'Registration', 'day_pass' => 'Day pass', 'merchandise' => 'Merchandise', 'transport' => 'Transport', 'donation' => 'Donation', 'other' => 'Other'] as $key => $label): ?>
                <option value="<?= $key ?>" <?= ($product['type'] ?? 'merchandise') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label class="field__label" for="category_id">Category</label>
            <select id="category_id" name="category_id">
              <option value="">None</option>
              <?php foreach ($categories as $category): ?>
                <option value="<?= (int) $category['id'] ?>" <?= (int) ($product['category_id'] ?? 0) === (int) $category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label class="field__label" for="sku">SKU</label>
            <input type="text" id="sku" name="sku" value="<?= $value('sku') ?>">
          </div>
        </div>
        <div class="field">
          <label class="field__label" for="slug">URL slug</label>
          <input type="text" id="slug" name="slug" value="<?= $value('slug') ?>">
        </div>
        <div class="field">
          <label class="field__label" for="short_description">Short description</label>
          <input type="text" id="short_description" name="short_description" maxlength="255" value="<?= $value('short_description') ?>">
        </div>
        <div class="field">
          <label class="field__label" for="description">Full description (HTML allowed)</label>
          <textarea id="description" name="description" rows="8"><?= $value('description') ?></textarea>
        </div>
      </div>

      <div class="admin-panel">
        <h2>Price &amp; stock</h2>
        <div class="field-row field-row--3">
          <div class="field">
            <label class="field__label" for="price">Price (R)</label>
            <input type="number" id="price" name="price" step="0.01" min="0" required value="<?= $isNew ? '' : $rand((int) $product['price_cents']) ?>">
          </div>
          <div class="field">
            <label class="field__label" for="sale_price">Sale price (R)</label>
            <input type="number" id="sale_price" name="sale_price" step="0.01" min="0" value="<?= $isNew ? '' : $rand($product['sale_price_cents'] === null ? null : (int) $product['sale_price_cents']) ?>">
          </div>
          <div class="field">
            <label class="field__label" for="sale_ends_at">Sale ends</label>
            <input type="datetime-local" id="sale_ends_at" name="sale_ends_at"
                   value="<?= $isNew || $product['sale_ends_at'] === null ? '' : e(date('Y-m-d\TH:i', (int) strtotime((string) $product['sale_ends_at']))) ?>">
          </div>
        </div>

        <div class="field-row field-row--3">
          <div class="field">
            <label class="field__label" for="stock">Stock</label>
            <input type="number" id="stock" name="stock" value="<?= $value('stock', 0) ?>">
          </div>
          <div class="field">
            <label class="field__label" for="low_stock_threshold">Low-stock warning at</label>
            <input type="number" id="low_stock_threshold" name="low_stock_threshold" value="<?= $value('low_stock_threshold', 5) ?>">
          </div>
          <div class="field">
            <label class="field__label" for="max_per_order">Max per order</label>
            <input type="number" id="max_per_order" name="max_per_order" min="1" value="<?= $value('max_per_order', 10) ?>">
          </div>
        </div>

        <label class="checkbox"><input type="checkbox" name="track_stock" value="1" <?= $isNew || (int) $product['track_stock'] === 1 ? 'checked' : '' ?>><span>Track stock levels</span></label>
        <label class="checkbox"><input type="checkbox" name="allows_custom_amount" value="1" <?= !$isNew && (int) $product['allows_custom_amount'] === 1 ? 'checked' : '' ?>><span>Buyer chooses the amount (donations)</span></label>
        <div class="field">
          <label class="field__label" for="min_amount">Minimum amount (R)</label>
          <input type="number" id="min_amount" name="min_amount" step="0.01" min="0" value="<?= $isNew ? '' : $rand((int) $product['min_amount_cents']) ?>">
        </div>
        <label class="checkbox"><input type="checkbox" name="requires_attendee" value="1" <?= !$isNew && (int) $product['requires_attendee'] === 1 ? 'checked' : '' ?>><span>Ask for attendee details at checkout</span></label>
        <label class="checkbox"><input type="checkbox" name="pickup_only" value="1" <?= $isNew || (int) $product['pickup_only'] === 1 ? 'checked' : '' ?>><span>Collected at the convention</span></label>
        <label class="checkbox"><input type="checkbox" name="delivery_enabled" value="1" <?= !$isNew && (int) $product['delivery_enabled'] === 1 ? 'checked' : '' ?>><span>Delivery available (future use)</span></label>
      </div>

      <div class="admin-panel">
        <h2>Image, SEO and visibility</h2>
        <div class="field">
          <label class="field__label" for="image_file">Upload an image</label>
          <input type="file" id="image_file" name="image_file" accept="image/*">
        </div>
        <div class="field">
          <label class="field__label" for="image">Or the path of an existing image</label>
          <input type="text" id="image" name="image" value="<?= $value('image') ?>">
        </div>
        <div class="field">
          <label class="field__label" for="meta_title">Meta title</label>
          <input type="text" id="meta_title" name="meta_title" value="<?= $value('meta_title') ?>">
        </div>
        <div class="field">
          <label class="field__label" for="meta_description">Meta description</label>
          <textarea id="meta_description" name="meta_description" rows="2" style="min-height:70px" maxlength="255"><?= $value('meta_description') ?></textarea>
        </div>
        <div class="field">
          <label class="field__label" for="sort_order">Sort order</label>
          <input type="number" id="sort_order" name="sort_order" value="<?= $value('sort_order', 0) ?>">
        </div>
        <label class="checkbox"><input type="checkbox" name="is_active" value="1" <?= $isNew || (int) $product['is_active'] === 1 ? 'checked' : '' ?>><span>On sale</span></label>
        <label class="checkbox"><input type="checkbox" name="is_featured" value="1" <?= !$isNew && (int) $product['is_featured'] === 1 ? 'checked' : '' ?>><span>Feature on the home page</span></label>
        <label class="checkbox"><input type="checkbox" name="is_mock" value="1" <?= !$isNew && (int) $product['is_mock'] === 1 ? 'checked' : '' ?>><span>Still placeholder content</span></label>

        <button class="btn btn--block" type="submit"><?= $isNew ? 'Create product' : 'Save product' ?></button>
      </div>
    </form>

    <?php if (!$isNew): ?>
      <div class="admin-panel">
        <h2>Variants</h2>
        <?php if ($variants === []): ?>
          <p class="muted" style="font-size:var(--step--1)">No variants yet. Products without variants are sold as a single option.</p>
        <?php endif; ?>

        <?php foreach ($variants as $variant): ?>
          <div style="display:flex;gap:.4rem;align-items:center;flex-wrap:wrap;padding:.5rem 0;border-bottom:1px solid var(--line)">
            <form method="post" action="<?= e(url('/admin/products/' . $product['id'] . '/variants')) ?>" class="inline-form" style="flex:1">
              <?= csrf_field() ?>
              <input type="hidden" name="variant_id" value="<?= (int) $variant['id'] ?>">
              <input type="text" name="size" value="<?= e((string) $variant['size']) ?>" placeholder="Size" style="width:90px" aria-label="Size">
              <input type="text" name="colour" value="<?= e((string) $variant['colour']) ?>" placeholder="Colour" style="width:130px" aria-label="Colour">
              <input type="text" name="sku" value="<?= e((string) $variant['sku']) ?>" placeholder="SKU" style="width:120px" aria-label="SKU">
              <input type="number" step="0.01" name="price_delta" value="<?= $rand((int) $variant['price_delta_cents']) ?>" style="width:100px" aria-label="Price change in Rand">
              <input type="number" name="stock" value="<?= (int) $variant['stock'] ?>" style="width:85px" aria-label="Stock">
              <label class="checkbox" style="margin:0"><input type="checkbox" name="is_active" value="1" <?= (int) $variant['is_active'] === 1 ? 'checked' : '' ?>><span>Active</span></label>
              <button class="btn btn--sm" type="submit">Save</button>
            </form>
            <form method="post" action="<?= e(url('/admin/products/' . $product['id'] . '/variants/' . $variant['id'] . '/delete')) ?>" data-confirm="Delete this variant?">
              <?= csrf_field() ?>
              <button class="btn btn--sm btn--ghost" type="submit">Delete</button>
            </form>
          </div>
        <?php endforeach; ?>

        <h3 style="margin-top:1.25rem">Add a variant</h3>
        <form method="post" action="<?= e(url('/admin/products/' . $product['id'] . '/variants')) ?>" class="inline-form">
          <?= csrf_field() ?>
          <input type="text" name="size" placeholder="Size">
          <input type="text" name="colour" placeholder="Colour">
          <input type="text" name="sku" placeholder="SKU">
          <input type="number" step="0.01" name="price_delta" placeholder="+R" style="width:100px">
          <input type="number" name="stock" placeholder="Stock" style="width:90px">
          <label class="checkbox" style="margin:0"><input type="checkbox" name="is_active" value="1" checked><span>Active</span></label>
          <button class="btn btn--sm" type="submit">Add</button>
        </form>
      </div>

      <div class="admin-panel">
        <h2>Adjust stock</h2>
        <form method="post" action="<?= e(url('/admin/products/' . $product['id'] . '/stock')) ?>" class="inline-form">
          <?= csrf_field() ?>
          <input type="number" name="change" placeholder="e.g. 24 or -3" required style="width:140px">
          <select name="variant_id">
            <option value="">Whole product</option>
            <?php foreach ($variants as $variant): ?>
              <option value="<?= (int) $variant['id'] ?>"><?= e(trim(implode(' / ', array_filter([$variant['size'], $variant['colour']])))) ?></option>
            <?php endforeach; ?>
          </select>
          <input type="text" name="reason" placeholder="Reason" style="min-width:220px">
          <button class="btn btn--sm" type="submit">Apply</button>
        </form>

        <?php if (!empty($movements)): ?>
          <div class="table-wrap" style="margin-top:1rem">
            <table class="admin-table">
              <thead><tr><th>When</th><th class="numeric">Change</th><th>Reason</th></tr></thead>
              <tbody>
                <?php foreach ($movements as $movement): ?>
                  <tr>
                    <td><?= e(za_date((string) $movement['created_at'], 'j M, H:i')) ?></td>
                    <td class="numeric" style="color:<?= (int) $movement['change'] < 0 ? 'var(--error)' : 'var(--success)' ?>"><?= (int) $movement['change'] > 0 ? '+' : '' ?><?= (int) $movement['change'] ?></td>
                    <td><?= e($movement['reason']) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>

  <div>
    <?php if (!$isNew && $product['image']): ?>
      <div class="admin-panel">
        <h2>Current image</h2>
        <?= picture($product['image'], $product['name']) ?>
      </div>
    <?php endif; ?>

    <div class="admin-panel">
      <h2>Notes</h2>
      <p style="font-size:var(--step--1)">
        <strong>Registration</strong> and <strong>day pass</strong> products are counted as attendees on the dashboard.
        <strong>Donation</strong> products with "buyer chooses the amount" show quick-amount buttons on the shop page.
      </p>
      <p style="font-size:var(--step--1)">
        Deleting a product that has been sold would break order history, so it is deactivated instead.
      </p>
    </div>
  </div>
</div>
<?php View::stop(); ?>

<?php
use App\Core\View;
View::layout('layouts.admin');
View::start('content');
?>
<div class="admin-head"><div><h1>Coupons</h1><p>Discount codes applied in the cart.</p></div></div>

<div class="admin-grid admin-grid--sidebar">
  <div class="admin-panel" style="padding:0">
    <div class="table-wrap" style="border:0">
      <table class="admin-table">
        <thead><tr><th>Code</th><th>Discount</th><th>Applies to</th><th class="numeric">Used</th><th>Window</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($coupons as $coupon): ?>
            <tr>
              <td><strong><?= e($coupon['code']) ?></strong><br><span class="muted"><?= e((string) $coupon['description']) ?></span></td>
              <td><?= $coupon['discount_type'] === 'percent' ? (int) $coupon['discount_value'] . '%' : e(money((int) $coupon['discount_value'])) ?>
                  <?php if ((int) $coupon['min_subtotal_cents'] > 0): ?><br><span class="muted">min <?= e(money((int) $coupon['min_subtotal_cents'])) ?></span><?php endif; ?></td>
              <td><?= e(ucfirst((string) $coupon['applies_to'])) ?></td>
              <td class="numeric"><?= (int) $coupon['used_count'] ?><?= $coupon['max_uses'] !== null ? ' / ' . (int) $coupon['max_uses'] : '' ?></td>
              <td><?= $coupon['starts_at'] ? e(za_date((string) $coupon['starts_at'], 'j M')) : 'now' ?> &rarr; <?= $coupon['ends_at'] ? e(za_date((string) $coupon['ends_at'], 'j M Y')) : 'open' ?></td>
              <td><?= (int) $coupon['is_active'] === 1 ? '<span class="badge badge--success">Active</span>' : '<span class="badge">Off</span>' ?></td>
              <td>
                <form method="post" action="<?= e(url('/admin/coupons/' . $coupon['id'] . '/delete')) ?>" data-confirm="Remove this coupon?">
                  <?= csrf_field() ?>
                  <button class="btn btn--sm btn--ghost" type="submit">Remove</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if ($coupons === []): ?><tr><td colspan="7" class="muted">No coupons yet.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="admin-panel">
    <h2>New coupon</h2>
    <form method="post" action="<?= e(url('/admin/coupons')) ?>">
      <?= csrf_field() ?>
      <div class="field"><label class="field__label">Code</label><input type="text" name="code" required style="text-transform:uppercase" placeholder="EARLYBIRD"></div>
      <div class="field"><label class="field__label">Description</label><input type="text" name="description"></div>
      <div class="field">
        <label class="field__label">Type</label>
        <select name="discount_type"><option value="percent">Percentage</option><option value="fixed">Fixed amount</option></select>
      </div>
      <div class="field"><label class="field__label">Value (% or R)</label><input type="number" step="0.01" min="1" name="discount_value" required></div>
      <div class="field"><label class="field__label">Minimum order (R)</label><input type="number" step="0.01" min="0" name="min_subtotal" value="0"></div>
      <div class="field">
        <label class="field__label">Applies to</label>
        <select name="applies_to">
          <option value="all">Everything</option>
          <option value="registration">Registration only</option>
          <option value="accommodation">Accommodation only</option>
          <option value="merchandise">Merchandise only</option>
          <option value="transport">Transport only</option>
        </select>
      </div>
      <div class="field"><label class="field__label">Maximum uses</label><input type="number" min="0" name="max_uses" placeholder="Leave blank for unlimited"></div>
      <div class="field-row field-row--2">
        <div class="field"><label class="field__label">Starts</label><input type="datetime-local" name="starts_at"></div>
        <div class="field"><label class="field__label">Ends</label><input type="datetime-local" name="ends_at"></div>
      </div>
      <button class="btn btn--block btn--sm" type="submit">Create coupon</button>
    </form>
  </div>
</div>
<?php View::stop(); ?>

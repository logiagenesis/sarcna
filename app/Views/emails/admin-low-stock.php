<?php /** @var array $products */ ?>
<h2 style="font-family:Georgia,serif;color:#173D2F;margin:0 0 12px;">Low stock warning</h2>
<table role="presentation" width="100%" style="font-size:14px;border-collapse:collapse;">
  <?php foreach ($products as $product): ?>
  <tr>
    <td style="padding:8px 0;border-bottom:1px solid #eee;"><?= e($product['name']) ?></td>
    <td align="right" style="padding:8px 0;border-bottom:1px solid #eee;"><strong><?= (int) $product['stock'] ?></strong> left</td>
  </tr>
  <?php endforeach; ?>
</table>
<p><a href="<?= e(url('/admin/products')) ?>">Manage stock</a></p>

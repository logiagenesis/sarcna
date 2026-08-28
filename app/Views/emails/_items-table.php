<?php /** @var array $order @var array $items */ ?>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;border-collapse:collapse;margin:20px 0;">
  <?php foreach ($items as $item): ?>
  <tr>
    <td style="padding:8px 0;border-bottom:1px solid #eee;"><?= e($item['description']) ?><?= (int) $item['quantity'] > 1 ? ' &times; ' . (int) $item['quantity'] : '' ?></td>
    <td align="right" style="padding:8px 0;border-bottom:1px solid #eee;white-space:nowrap;"><?= e(money((int) $item['total_cents'])) ?></td>
  </tr>
  <?php endforeach; ?>
  <?php if ((int) $order['discount_cents'] > 0): ?>
  <tr>
    <td style="padding:8px 0;border-bottom:1px solid #eee;color:#2F7D4F;">Discount<?= $order['coupon_code'] ? ' (' . e($order['coupon_code']) . ')' : '' ?></td>
    <td align="right" style="padding:8px 0;border-bottom:1px solid #eee;color:#2F7D4F;">&minus;<?= e(money((int) $order['discount_cents'])) ?></td>
  </tr>
  <?php endif; ?>
  <tr>
    <td style="padding:12px 0;font-weight:bold;font-size:16px;">Total</td>
    <td align="right" style="padding:12px 0;font-weight:bold;font-size:16px;"><?= e(money((int) $order['total_cents'])) ?></td>
  </tr>
</table>

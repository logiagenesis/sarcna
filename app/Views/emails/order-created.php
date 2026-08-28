<?php /** @var array $order @var array $items */ ?>
<h2 style="font-family:Georgia,serif;color:#173D2F;margin:0 0 12px;">Order <?= e($order['reference']) ?> received</h2>
<p>Thank you. Your order has been created and is waiting for payment. Nothing is confirmed until PayFast tells us the payment went through.</p>
<?php require __DIR__ . '/_items-table.php'; ?>
<p style="text-align:center;margin:28px 0;">
  <a href="<?= e(url('/checkout/pay/' . $order['reference'])) ?>" style="display:inline-block;background:#173D2F;color:#FFF6E7;padding:14px 28px;border-radius:999px;text-decoration:none;font-weight:bold;">Complete payment</a>
</p>

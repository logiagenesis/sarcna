<?php /** @var array $order @var string $reason */ ?>
<h2 style="font-family:Georgia,serif;color:#B8403A;margin:0 0 12px;">Failed payment — <?= e($order['reference']) ?></h2>
<p><?= e($reason) ?></p>
<p>Customer: <?= e($order['email']) ?> &middot; Total: <?= e(money((int) $order['total_cents'])) ?></p>
<p><a href="<?= e(url('/admin/orders/' . $order['id'])) ?>">Review the order</a> &middot; <a href="<?= e(url('/admin/payments/logs')) ?>">Payment logs</a></p>

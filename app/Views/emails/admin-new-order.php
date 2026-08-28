<?php /** @var array $order @var array $items */ ?>
<h2 style="font-family:Georgia,serif;color:#173D2F;margin:0 0 12px;">New paid order <?= e($order['reference']) ?></h2>
<p><strong><?= e(trim($order['first_name'] . ' ' . $order['last_name'])) ?></strong> &middot; <?= e($order['email']) ?><?= $order['phone'] ? ' &middot; ' . e($order['phone']) : '' ?></p>
<?php require __DIR__ . '/_items-table.php'; ?>
<p><a href="<?= e(url('/admin/orders/' . $order['id'])) ?>">Open this order in the admin</a></p>

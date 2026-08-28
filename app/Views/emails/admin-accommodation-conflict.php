<?php /** @var array $order @var array $items */ ?>
<h2 style="font-family:Georgia,serif;color:#B8403A;margin:0 0 12px;">Action needed: bed allocation failed</h2>
<p>Order <strong><?= e($order['reference']) ?></strong> was paid, but these bed-nights could not be allocated automatically:</p>
<ul><?php foreach ($items as $item): ?><li><?= e($item) ?></li><?php endforeach; ?></ul>
<p>The customer has been charged. Allocate a bed manually or arrange a refund.</p>
<p><a href="<?= e(url('/admin/orders/' . $order['id'])) ?>">Open the order</a> &middot; <a href="<?= e(url('/admin/bookings')) ?>">Booking board</a></p>

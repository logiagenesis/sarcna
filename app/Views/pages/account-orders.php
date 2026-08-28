<?php
use App\Core\View;
View::layout('layouts.public');
View::start('content');
?>
<section class="section">
  <div class="container">
    <div class="rule"></div>
    <h1 style="font-size:var(--step-3)">Order history</h1>
    <?php View::include('partials.account-nav'); ?>

    <?php if ($orders === []): ?>
      <div class="empty-state"><div class="empty-state__icon">🧾</div><h3>No orders yet</h3>
      <p>When you book something it will appear here with its confirmations.</p>
      <a class="btn" style="margin-top:1.25rem" href="<?= e(url('/shop')) ?>">Visit the shop</a></div>
    <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Reference</th><th>Placed</th><th>Items</th><th>Status</th><th class="numeric">Total</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($orders as $order): ?>
              <tr>
                <td><strong><?= e($order['reference']) ?></strong></td>
                <td><?= e(za_date((string) $order['created_at'], 'j M Y, H:i')) ?></td>
                <td><?= (int) $order['item_count'] ?></td>
                <td><?php View::include('partials.order-status', ['status' => $order['status']]); ?></td>
                <td class="numeric"><?= e(money((int) $order['total_cents'])) ?></td>
                <td>
                  <a class="btn btn--sm btn--ghost" href="<?= e(url('/account/orders/' . $order['reference'])) ?>">View</a>
                  <?php if ($order['status'] === 'pending_payment'): ?>
                    <a class="btn btn--sm" href="<?= e(url('/checkout/pay/' . $order['reference'])) ?>">Pay</a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php View::stop(); ?>

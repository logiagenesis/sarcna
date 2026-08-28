<?php
use App\Core\View;
View::layout('layouts.admin');
View::start('content');
?>
<div class="admin-head">
  <div>
    <h1>Check-in desk</h1>
    <p><?= (int) $stats['checkedIn'] ?> of <?= (int) $stats['paid'] ?> paid orders checked in</p>
  </div>
</div>

<div class="admin-panel">
  <form method="post" action="<?= e(url('/admin/checkin')) ?>">
    <?= csrf_field() ?>
    <div class="field">
      <label class="field__label" for="q">Check-in code, order reference, name or email</label>
      <input type="search" id="q" name="q" value="<?= e($query) ?>" autofocus autocomplete="off" placeholder="CHK-XXXX-XXXX" style="font-size:var(--step-1)">
    </div>
    <button class="btn btn--block btn--lg" type="submit">Look up</button>
  </form>
</div>

<?php if ($query !== '' && $results === []): ?>
  <div class="empty-state">
    <div class="empty-state__icon">🔍</div>
    <h3>Nothing found for &ldquo;<?= e($query) ?>&rdquo;</h3>
    <p>Only paid orders can be checked in. Search by surname or email if the code is not to hand.</p>
  </div>
<?php endif; ?>

<?php foreach ($results as $result): $order = $result['order']; ?>
  <div class="admin-panel">
    <div class="cluster cluster--between" style="align-items:flex-start">
      <div>
        <h2 style="margin-bottom:.2rem"><?= e(trim(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? ''))) ?></h2>
        <p class="muted" style="font-size:var(--step--1);margin:0">
          <?= e($order['email']) ?> &middot; <?= e((string) $order['phone']) ?><br>
          Order <a href="<?= e(url('/admin/orders/' . $order['id'])) ?>"><?= e($order['reference']) ?></a> &middot;
          code <strong><?= e((string) $order['checkin_code']) ?></strong> &middot;
          <?= e(money((int) $order['total_cents'])) ?> paid
        </p>
      </div>
      <div style="text-align:right">
        <?php if ($order['checked_in_at'] !== null): ?>
          <span class="badge badge--success">Checked in <?= e(za_date((string) $order['checked_in_at'], 'j M, H:i')) ?></span>
        <?php endif; ?>
        <form method="post" action="<?= e(url('/admin/checkin/' . $order['id'] . '/confirm')) ?>" style="margin-top:.5rem">
          <?= csrf_field() ?>
          <button class="btn <?= $order['checked_in_at'] !== null ? 'btn--ghost' : 'btn--lg' ?>" type="submit">
            <?= $order['checked_in_at'] !== null ? 'Undo check-in' : 'Check in' ?>
          </button>
        </form>
      </div>
    </div>

    <div class="admin-grid admin-grid--2" style="margin-top:1.25rem">
      <div>
        <h3>What they bought</h3>
        <ul style="font-size:var(--step--1)">
          <?php foreach ($result['items'] as $item): ?>
            <li><?= e($item['description']) ?><?= (int) $item['quantity'] > 1 ? ' × ' . (int) $item['quantity'] : '' ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div>
        <?php if ($result['bookings'] !== []): ?>
          <h3>Room allocation</h3>
          <ul style="font-size:var(--step--1)">
            <?php foreach ($result['bookings'] as $booking): ?>
              <li><strong><?= e(za_date((string) $booking['night'], 'D j M')) ?></strong> — <?= e($booking['unit_name']) ?>, <?= e($booking['bed_label']) ?>
                  <?php if ($booking['accessibility_needs']): ?><br><span class="badge badge--warning">Access: <?= e($booking['accessibility_needs']) ?></span><?php endif; ?></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
        <?php if ($result['transport'] !== []): ?>
          <h3>Transport</h3>
          <ul style="font-size:var(--step--1)">
            <?php foreach ($result['transport'] as $trip): ?>
              <li><?= e(za_date((string) $trip['departs_at'], 'D j M, H:i')) ?> — <?= e($trip['route_name']) ?></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>
  </div>
<?php endforeach; ?>
<?php View::stop(); ?>

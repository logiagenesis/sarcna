<?php
use App\Core\View;
use App\Services\AuthService;
View::layout('layouts.admin');
View::start('content');
$event = config('event');
?>
<div class="admin-head">
  <div>
    <h1>Dashboard</h1>
    <p><?= e($event['title']) ?> &middot; <?= e($event['dates_label']) ?> &middot; <?= e($event['venue_name']) ?></p>
  </div>
  <div class="cluster">
    <a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/export/orders')) ?>">Export orders</a>
    <a class="btn btn--sm" href="<?= e(url('/admin/checkin')) ?>">Check-in desk</a>
  </div>
</div>

<?php if (!$payfastConfigured): ?>
  <div class="alert alert--error">
    <div><div class="alert__title">PayFast is not configured</div>
    <p>Nobody can pay until the merchant ID and key are set in <code>.env</code>. See <code>/docs/payfast-setup.md</code>.</p></div>
  </div>
<?php elseif ($sandbox): ?>
  <div class="alert alert--warning">
    <div><div class="alert__title">PayFast is in sandbox mode</div>
    <p>Payments are simulated. Switch <code>PAYFAST_MODE</code> to <code>live</code> in <code>.env</code> when the committee is ready.</p></div>
  </div>
<?php endif; ?>

<?php if ($needsAttention !== []): ?>
  <div class="alert alert--error">
    <div>
      <div class="alert__title">Orders that need a human</div>
      <ul style="margin:.4rem 0 0;padding-left:1.1rem">
        <?php foreach ($needsAttention as $order): ?>
          <li><a href="<?= e(url('/admin/orders/' . $order['id'])) ?>"><?= e($order['reference']) ?></a> — <?= e(excerpt($order['admin_note'], 120)) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
<?php endif; ?>

<div class="tiles">
  <a class="tile tile--success" href="<?= e(url('/admin/orders?status=paid')) ?>">
    <div class="tile__label">Revenue</div>
    <div class="tile__value"><?= e(money($revenue)) ?></div>
    <div class="tile__meta"><?= (int) $paidOrders ?> paid orders</div>
  </a>
  <a class="tile tile--gold" href="<?= e(url('/admin/orders?status=paid')) ?>">
    <div class="tile__label">Registrations</div>
    <div class="tile__value"><?= (int) $registrations ?></div>
    <div class="tile__meta">Weekend and day passes sold</div>
  </a>
  <a class="tile" href="<?= e(url('/admin/bookings')) ?>">
    <div class="tile__label">Bed-nights booked</div>
    <div class="tile__value"><?= (int) $occupancy['booked'] ?></div>
    <div class="tile__meta">of <?= (int) $occupancy['total_bed_nights'] ?> available &middot; <?= (int) $occupancy['held'] ?> held right now</div>
  </a>
  <a class="tile" href="<?= e(url('/admin/transport')) ?>">
    <div class="tile__label">Shuttle seats</div>
    <div class="tile__value"><?= (int) $transport['taken'] ?></div>
    <div class="tile__meta">of <?= (int) $transport['capacity'] ?> across all departures</div>
  </a>
  <a class="tile tile--plum" href="<?= e(url('/admin/donations')) ?>">
    <div class="tile__label">Donations</div>
    <div class="tile__value"><?= e(money($donationTotal)) ?></div>
    <div class="tile__meta">Seventh Tradition and sponsorships</div>
  </a>
  <a class="tile tile--clay" href="<?= e(url('/admin/orders?status=pending_payment')) ?>">
    <div class="tile__label">Pending payment</div>
    <div class="tile__value"><?= (int) $pending ?></div>
    <div class="tile__meta"><?= (int) $failed ?> failed payments</div>
  </a>
</div>

<div class="admin-grid admin-grid--sidebar">
  <div>
    <div class="admin-panel">
      <h2>Bed availability by night</h2>
      <?php foreach ($occupancy['by_night'] as $night => $data): ?>
        <div style="margin-bottom:.9rem">
          <div class="cluster cluster--between">
            <strong><?= e($data['label']) ?></strong>
            <span class="muted" style="font-size:var(--step--1)">
              <?= (int) $data['booked'] ?> booked &middot; <?= (int) $data['held'] ?> held &middot; <?= (int) $data['available'] ?> free of <?= (int) $data['total'] ?>
            </span>
          </div>
          <div class="bar <?= $data['percent'] > 90 ? 'bar--error' : ($data['percent'] > 70 ? 'bar--warning' : '') ?>">
            <span style="width:<?= max(2, (int) $data['percent']) ?>%"></span>
          </div>
        </div>
      <?php endforeach; ?>
      <a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/bookings/board')) ?>">Open the bed board</a>
    </div>

    <div class="admin-panel">
      <h2>Recent orders</h2>
      <div class="table-wrap">
        <table class="admin-table">
          <thead><tr><th>Reference</th><th>Customer</th><th>Items</th><th>Status</th><th class="numeric">Total</th><th>Placed</th></tr></thead>
          <tbody>
            <?php foreach ($recentOrders as $order): ?>
              <tr>
                <td><a href="<?= e(url('/admin/orders/' . $order['id'])) ?>"><strong><?= e($order['reference']) ?></strong></a></td>
                <td><?= e(trim(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? ''))) ?><br><span class="muted"><?= e($order['email']) ?></span></td>
                <td><?= (int) $order['item_count'] ?></td>
                <td><?php View::include('partials.order-status', ['status' => $order['status']]); ?></td>
                <td class="numeric"><?= e(money((int) $order['total_cents'])) ?></td>
                <td><?= e(za_date((string) $order['created_at'], 'j M, H:i')) ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if ($recentOrders === []): ?>
              <tr><td colspan="6" class="muted">No orders yet.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <a class="btn btn--sm btn--ghost" style="margin-top:.75rem" href="<?= e(url('/admin/orders')) ?>">All orders</a>
    </div>

    <div class="admin-panel">
      <h2>Where the money comes from</h2>
      <div class="table-wrap">
        <table class="admin-table">
          <thead><tr><th>Category</th><th class="numeric">Lines</th><th class="numeric">Revenue</th></tr></thead>
          <tbody>
            <?php foreach ($salesByType as $row): ?>
              <tr>
                <td><?= e(ucfirst($row['item_type'])) ?></td>
                <td class="numeric"><?= (int) $row['line_count'] ?></td>
                <td class="numeric"><?= e(money((int) $row['revenue'])) ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if ($salesByType === []): ?><tr><td colspan="3" class="muted">Nothing sold yet.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div>
    <div class="admin-panel">
      <h2>Needs your attention</h2>
      <div class="summary__row"><span>New service applications</span><strong><a href="<?= e(url('/admin/applications')) ?>"><?= (int) $applications ?></a></strong></div>
      <div class="summary__row"><span>Unread messages</span><strong><a href="<?= e(url('/admin/messages')) ?>"><?= (int) $messages ?></a></strong></div>
      <div class="summary__row"><span>Pending payments</span><strong><a href="<?= e(url('/admin/orders?status=pending_payment')) ?>"><?= (int) $pending ?></a></strong></div>
      <div class="summary__row"><span>Registered customers</span><strong><?= (int) $customers ?></strong></div>
    </div>

    <?php if ($lowStock !== []): ?>
      <div class="admin-panel">
        <h2>Low stock</h2>
        <?php foreach ($lowStock as $product): ?>
          <div class="summary__row">
            <span><a href="<?= e(url('/admin/products/' . $product['id'])) ?>"><?= e($product['name']) ?></a></span>
            <strong style="color:<?= (int) $product['stock'] === 0 ? 'var(--error)' : 'var(--warning)' ?>"><?= (int) $product['stock'] ?> left</strong>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="admin-panel">
      <h2>Transport load</h2>
      <?php foreach ($transport['routes'] as $route):
        $capacity = max(1, (int) $route['capacity']);
        $percent  = (int) round(((int) $route['taken'] / $capacity) * 100); ?>
        <div style="margin-bottom:.75rem">
          <div class="cluster cluster--between">
            <span style="font-size:var(--step--1)"><?= e($route['name']) ?></span>
            <span class="muted" style="font-size:var(--step--1)"><?= (int) $route['taken'] ?>/<?= (int) $route['capacity'] ?></span>
          </div>
          <div class="bar <?= $percent > 90 ? 'bar--error' : ($percent > 70 ? 'bar--warning' : '') ?>"><span style="width:<?= max(2, $percent) ?>%"></span></div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="admin-panel">
      <h2>Latest payments</h2>
      <?php foreach ($recentPayments as $payment): ?>
        <div class="summary__row">
          <span><?= e((string) ($payment['reference'] ?? '—')) ?><br><span class="muted"><?= e(za_date((string) $payment['created_at'], 'j M, H:i')) ?></span></span>
          <span style="text-align:right">
            <?= e(money((int) $payment['amount_cents'])) ?><br>
            <span class="badge <?= $payment['status'] === 'complete' ? 'badge--success' : ($payment['status'] === 'initiated' ? 'badge--warning' : 'badge--error') ?>"><?= e($payment['status']) ?></span>
          </span>
        </div>
      <?php endforeach; ?>
      <?php if ($recentPayments === []): ?><p class="muted">No payments recorded yet.</p><?php endif; ?>
      <a class="btn btn--sm btn--ghost" style="margin-top:.75rem" href="<?= e(url('/admin/payments')) ?>">Payment log</a>
    </div>

    <?php if ($audit !== []): ?>
      <div class="admin-panel">
        <h2>Recent admin activity</h2>
        <?php foreach ($audit as $entry): ?>
          <p style="font-size:var(--step--1);margin-bottom:.4rem">
            <strong><?= e((string) $entry['user_email']) ?></strong> <?= e($entry['action']) ?>
            <span class="muted"><?= e(za_date((string) $entry['created_at'], 'j M, H:i')) ?></span>
          </p>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php View::stop(); ?>

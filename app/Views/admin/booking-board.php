<?php
use App\Core\View;
View::layout('layouts.admin');
View::start('content');
$currentType = null;
?>
<div class="admin-head">
  <div>
    <h1>Bed board</h1>
    <p>Every bed against every night. <?= (int) $occupancy['booked'] ?> booked, <?= (int) $occupancy['held'] ?> held, <?= (int) $occupancy['available'] ?> free.</p>
  </div>
  <a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/export/rooming-list')) ?>">Rooming list CSV</a>
</div>

<div class="admin-note">
  <span class="board-cell board-cell--free" style="display:inline-block">Free</span>
  <span class="board-cell board-cell--held" style="display:inline-block">Held in a cart</span>
  <span class="board-cell board-cell--booked" style="display:inline-block">Booked</span>
  <span class="board-cell board-cell--off" style="display:inline-block">Out of service</span>
</div>

<div class="admin-panel board" style="padding:.75rem">
  <table>
    <thead>
      <tr>
        <th style="min-width:220px">Unit &amp; bed</th>
        <?php foreach ($nights as $night): ?><th><?= e(za_date($night, 'D j M')) ?></th><?php endforeach; ?>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($units as $unit): ?>
        <?php if ($currentType !== $unit['room_type_name']): $currentType = $unit['room_type_name']; ?>
          <tr><th colspan="<?= count($nights) + 1 ?>" style="background:var(--vineyard);color:var(--cream)"><?= e($currentType) ?></th></tr>
        <?php endif; ?>
        <?php foreach (($bedsByUnit[(int) $unit['id']] ?? []) as $bed): ?>
          <tr>
            <td style="font-size:.78rem"><strong><?= e($unit['name']) ?></strong> &middot; <?= e($bed['label']) ?></td>
            <?php foreach ($nights as $night):
              $booking = $booked[(int) $bed['id']][$night] ?? null;
              $isHeld  = isset($held[(int) $bed['id']][$night]);
              $offline = (int) $bed['is_active'] !== 1 || (int) $unit['is_active'] !== 1; ?>
              <td>
                <?php if ($offline): ?>
                  <span class="board-cell board-cell--off">out of service</span>
                <?php elseif ($booking !== null): ?>
                  <span class="board-cell board-cell--booked" title="<?= e((string) $booking['order_reference']) ?>">
                    <?= e(excerpt((string) ($booking['guest_name'] ?: 'Booked'), 22)) ?>
                  </span>
                <?php elseif ($isHeld): ?>
                  <span class="board-cell board-cell--held">held</span>
                <?php else: ?>
                  <span class="board-cell board-cell--free">free</span>
                <?php endif; ?>
              </td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php View::stop(); ?>

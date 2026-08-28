<?php
use App\Core\View;
View::layout('layouts.admin');
View::start('content');
?>
<div class="admin-head">
  <div>
    <h1>Run sheet</h1>
    <p>The door list. Every unit, every bed, who is in it. Print this and reception can work without a screen.</p>
  </div>
  <button class="btn btn--sm" type="button" onclick="window.print()">Print</button>
</div>

<?php View::include('partials.rooming-tabs'); ?>

<div class="admin-toolbar">
  <form method="get" action="<?= e(url('/admin/bookings/run-sheet')) ?>" data-autosubmit>
    <select name="night" aria-label="Night">
      <option value="">Every night</option>
      <?php foreach ($nights as $option): ?>
        <option value="<?= e($option) ?>" <?= $night === $option ? 'selected' : '' ?>><?= e(za_date($option, 'l j F Y')) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn--sm" type="submit">Show</button>
  </form>
</div>

<?php foreach ($sheet as $sheetNight => $units): ?>
  <?php
    $occupied = 0;
    $empty    = 0;

    foreach ($units as $unit) {
        foreach ($unit['beds'] as $bed) {
            $bed['booking_id'] === null ? $empty++ : $occupied++;
        }
    }
  ?>
  <div class="run-sheet">
    <h2 class="run-sheet__night">
      <?= e(za_date((string) $sheetNight, 'l j F Y')) ?>
      <span class="muted"><?= $occupied ?> occupied, <?= $empty ?> empty</span>
    </h2>

    <div class="run-sheet__grid">
      <?php $currentType = null; ?>
      <?php foreach ($units as $unit): ?>
        <?php
          $anyone = false;

          foreach ($unit['beds'] as $bed) {
              if ($bed['booking_id'] !== null) {
                  $anyone = true;
                  break;
              }
          }
        ?>
        <div class="run-card <?= $anyone ? '' : 'run-card--empty' ?>">
          <div class="run-card__head">
            <strong><?= e((string) $unit['name']) ?></strong>
            <span class="muted"><?= e((string) $unit['room_type']) ?></span>
          </div>
          <?php foreach ($unit['beds'] as $bed): ?>
            <div class="run-card__bed">
              <span class="run-card__label"><?= e((string) $bed['bed_label']) ?></span>
              <?php if ($bed['booking_id'] === null): ?>
                <span class="muted">— empty —</span>
              <?php else: ?>
                <span class="run-card__guest">
                  <strong><?= e((string) ($bed['guest_name'] ?: 'Name not captured')) ?></strong>
                  <?php if ($bed['checkin_code']): ?>
                    <span class="run-card__code"><?= e((string) $bed['checkin_code']) ?></span>
                  <?php endif; ?>
                  <?php if ($bed['guest_phone']): ?><br><span class="muted"><?= e((string) $bed['guest_phone']) ?></span><?php endif; ?>
                  <?php if ($bed['accessibility_needs']): ?>
                    <br><span class="run-card__flag">Access: <?= e((string) $bed['accessibility_needs']) ?></span>
                  <?php endif; ?>
                  <?php if ($bed['notes']): ?>
                    <br><span class="muted"><?= e((string) $bed['notes']) ?></span>
                  <?php endif; ?>
                  <?php if ($bed['status'] === 'checked_in'): ?>
                    <br><span class="badge badge--success">checked in</span>
                  <?php endif; ?>
                </span>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endforeach; ?>

<?php if ($sheet === []): ?>
  <div class="admin-panel"><p class="muted">No nights are configured yet.</p></div>
<?php endif; ?>
<?php View::stop(); ?>

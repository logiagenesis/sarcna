<?php
/** @var string|null $variant */
$startsAt = (string) config('event.starts_at');
?>
<div class="countdown <?= ($variant ?? '') === 'light' ? 'countdown--light' : '' ?>" data-countdown="<?= e($startsAt) ?>"
     aria-label="Countdown to the convention">
  <?php foreach (['days' => 'Days', 'hours' => 'Hours', 'minutes' => 'Minutes', 'seconds' => 'Seconds'] as $unit => $label): ?>
    <div class="countdown__unit">
      <span class="countdown__value" data-unit="<?= $unit ?>">--</span>
      <span class="countdown__label"><?= $label ?></span>
    </div>
  <?php endforeach; ?>
</div>

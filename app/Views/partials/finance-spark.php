<?php
/** @var array $daily rows of day/cents, newest first @var string $label */
$rows = array_reverse($daily);

if ($rows === []) {
    echo '<p class="muted" style="font-size:var(--step--1)">No income recorded in this period yet.</p>';
    return;
}

$max = max(array_map(static fn (array $r): int => (int) $r['cents'], $rows)) ?: 1;
?>
<div class="spark" role="img" aria-label="<?= e($label ?? 'Income per day') ?>">
  <?php foreach ($rows as $row):
    $cents  = (int) $row['cents'];
    $height = max(2, (int) round(($cents / $max) * 100)); ?>
    <span class="spark__bar" style="height:<?= $height ?>%" data-zero="<?= $cents === 0 ? '1' : '0' ?>"
          data-label="<?= e(za_date((string) $row['day'], 'j M')) ?>: <?= e(money($cents)) ?> (<?= (int) $row['orders'] ?>)"></span>
  <?php endforeach; ?>
</div>
<div class="spark-axis">
  <span><?= e(za_date((string) $rows[0]['day'], 'j M')) ?></span>
  <span>peak <?= e(money($max)) ?></span>
  <span><?= e(za_date((string) $rows[count($rows) - 1]['day'], 'j M')) ?></span>
</div>

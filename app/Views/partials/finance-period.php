<?php
/** @var array $period @var string $action */
$presets = [
    'all'        => 'All time',
    'today'      => 'Today',
    'week'       => 'This week',
    'month'      => 'This month',
    'last_month' => 'Last month',
    'quarter'    => 'Last 3 months',
    'year'       => 'Financial year',
];
$action = $action ?? '';
?>
<div class="admin-toolbar">
  <form method="get" action="<?= e(url($action)) ?>" data-autosubmit>
    <select name="period" aria-label="Reporting period">
      <?php foreach ($presets as $key => $label): ?>
        <option value="<?= e($key) ?>" <?= $period['key'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
      <option value="custom" <?= $period['key'] === 'custom' ? 'selected' : '' ?>>Custom range…</option>
    </select>
    <input type="date" name="from" value="<?= e($period['from']) ?>" aria-label="From">
    <input type="date" name="to" value="<?= e($period['to']) ?>" aria-label="To">
    <button class="btn btn--sm" type="submit">Apply</button>
    <span class="muted" style="font-size:var(--step--1)">
      Showing <strong><?= e($period['label']) ?></strong><?= $period['key'] === 'all' ? '' : ' · ' . e(za_date($period['from'], 'j M Y')) . ' to ' . e(za_date($period['to'], 'j M Y')) ?>
    </span>
  </form>
</div>

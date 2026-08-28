<?php
use App\Core\Session;

$messages = [
    'success' => ['class' => 'alert--success', 'title' => 'Done'],
    'error'   => ['class' => 'alert--error',   'title' => 'There was a problem'],
    'warning' => ['class' => 'alert--warning', 'title' => 'Please note'],
    'info'    => ['class' => 'alert--info',    'title' => 'Heads up'],
];

$shown = [];

foreach ($messages as $key => $meta) {
    $message = Session::getFlash($key);

    if ($message !== null && $message !== '') {
        $shown[] = ['message' => $message, 'meta' => $meta];
    }
}

if ($shown === []) {
    return;
}
?>
<div class="container" style="padding-top:1.25rem">
  <?php foreach ($shown as $item): ?>
    <div class="alert <?= e($item['meta']['class']) ?>" role="status">
      <div>
        <div class="alert__title"><?= e($item['meta']['title']) ?></div>
        <p><?= e($item['message']) ?></p>
      </div>
    </div>
  <?php endforeach; ?>
</div>

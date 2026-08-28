<?php /** @var string $body @var string $name */ ?>
<?php if (($name ?? '') !== ''): ?><p>Hello <?= e($name) ?>,</p><?php endif; ?>
<?= nl2br(e($body)) ?>

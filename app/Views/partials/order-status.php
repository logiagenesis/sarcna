<?php
/** @var string $status */
$map = [
    'paid'            => ['badge--success', 'Paid'],
    'pending_payment' => ['badge--warning', 'Awaiting payment'],
    'failed'          => ['badge--error', 'Payment failed'],
    'cancelled'       => ['badge--error', 'Cancelled'],
    'refunded'        => ['badge--plum', 'Refunded'],
];
[$class, $label] = $map[$status] ?? ['', ucfirst(str_replace('_', ' ', $status))];
?>
<span class="badge <?= e($class) ?>"><?= e($label) ?></span>

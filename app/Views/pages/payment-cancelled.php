<?php
use App\Core\View;
View::layout('layouts.public');
View::start('content');
?>
<section class="section">
  <div class="container container--narrow text-center">
    <div class="success-hero__mark" style="background:rgba(184,64,58,.12);border-color:var(--error);color:var(--error)">✕</div>
    <h1>Payment cancelled</h1>
    <p class="muted" style="margin-inline:auto">
      <?php if ($order !== null): ?>Order <strong><?= e($order['reference']) ?></strong> was not paid, so nothing has been charged.<?php else: ?>Nothing has been charged.<?php endif; ?>
      Any beds and shuttle seats you had selected have been released back to other attendees.
    </p>
    <div class="cluster cluster--center" style="margin-top:2rem">
      <a class="btn" href="<?= e(url('/shop')) ?>">Start again</a>
      <a class="btn btn--ghost" href="<?= e(url('/contact')) ?>">Something went wrong?</a>
    </div>
    <p class="muted" style="font-size:var(--step--1);margin-top:1.5rem">
      If you think the payment did go through, contact the committee with your order reference and we will check it against our PayFast records.
    </p>
  </div>
</section>
<?php View::stop(); ?>

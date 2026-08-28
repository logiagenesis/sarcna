<?php /** @var array $user */ ?>
<h2 style="font-family:Georgia,serif;color:#173D2F;margin:0 0 12px;">Welcome to SARCNA 2027, <?= e($user['first_name']) ?></h2>
<p>Your account is ready. From your dashboard you can register for the weekend, book a bed, reserve a shuttle seat and keep every confirmation in one place.</p>
<p style="text-align:center;margin:28px 0;">
  <a href="<?= e(url('/account')) ?>" style="display:inline-block;background:#D9A441;color:#0E241C;padding:14px 28px;border-radius:999px;text-decoration:none;font-weight:bold;">Go to my dashboard</a>
</p>
<p>We look forward to sharing the weekend with you in the Cape Winelands.</p>

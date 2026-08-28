<?php /** @var array $user @var string $link */ ?>
<h2 style="font-family:Georgia,serif;color:#173D2F;margin:0 0 12px;">Welcome, <?= e($user['first_name']) ?></h2>
<p>Please confirm your email address so we can send you your registration, accommodation and transport confirmations.</p>
<p style="text-align:center;margin:28px 0;">
  <a href="<?= e($link) ?>" style="display:inline-block;background:#173D2F;color:#FFF6E7;padding:14px 28px;border-radius:999px;text-decoration:none;font-weight:bold;">Confirm my email</a>
</p>
<p style="font-size:13px;color:#6b7d74;">If the button does not work, copy this link into your browser:<br><span style="word-break:break-all;"><?= e($link) ?></span></p>
<p style="font-size:13px;color:#6b7d74;">This link expires in 48 hours. If you did not create an account, you can ignore this email.</p>

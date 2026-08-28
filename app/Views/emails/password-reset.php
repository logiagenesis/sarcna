<?php /** @var array $user @var string $link */ ?>
<h2 style="font-family:Georgia,serif;color:#173D2F;margin:0 0 12px;">Reset your password</h2>
<p>Hello <?= e($user['first_name']) ?>, we received a request to reset the password on your SARCNA 2027 account.</p>
<p style="text-align:center;margin:28px 0;">
  <a href="<?= e($link) ?>" style="display:inline-block;background:#173D2F;color:#FFF6E7;padding:14px 28px;border-radius:999px;text-decoration:none;font-weight:bold;">Choose a new password</a>
</p>
<p style="font-size:13px;color:#6b7d74;">This link expires in 60 minutes and can only be used once. If you did not ask for a reset, no action is needed — your password has not changed.</p>

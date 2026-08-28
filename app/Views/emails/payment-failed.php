<?php /** @var array $order */ ?>
<h2 style="font-family:Georgia,serif;color:#B8403A;margin:0 0 12px;">We could not process your payment</h2>
<p>Order <strong><?= e($order['reference']) ?></strong> for <?= e(money((int) $order['total_cents'])) ?> was not completed, so nothing has been charged and nothing is reserved.</p>
<p>Any beds or shuttle seats you had selected have been released back to other attendees. You are welcome to try again.</p>
<p style="text-align:center;margin:28px 0;">
  <a href="<?= e(url('/shop')) ?>" style="display:inline-block;background:#173D2F;color:#FFF6E7;padding:14px 28px;border-radius:999px;text-decoration:none;font-weight:bold;">Start again</a>
</p>
<p style="font-size:13px;color:#6b7d74;">If you believe the payment did go through, reply to this email with your order reference and we will check it against our PayFast records.</p>

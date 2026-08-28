<?php /** @var array $donation */ ?>
<h2 style="font-family:Georgia,serif;color:#173D2F;margin:0 0 12px;">Thank you for your support</h2>
<p>Your donation of <strong><?= e(money((int) $donation['amount_cents'])) ?></strong> towards <em><?= e($donation['donation_type']) ?></em> has been received.</p>
<p>Reference: <strong><?= e($donation['reference']) ?></strong></p>
<p>Contributions like yours keep registration affordable and help newcomers get to the convention.</p>

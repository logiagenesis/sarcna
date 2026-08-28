<?php /** @var array $application */ ?>
<h2 style="font-family:Georgia,serif;color:#173D2F;margin:0 0 12px;">New service application</h2>
<p><strong><?= e($application['name']) ?></strong><br><?= e($application['email']) ?> &middot; <?= e($application['phone']) ?></p>
<p><strong>Areas:</strong> <?= e($application['service_areas']) ?><br>
   <strong>Availability:</strong> <?= e($application['availability']) ?></p>
<p><a href="<?= e(url('/admin/applications')) ?>">Review applications</a></p>

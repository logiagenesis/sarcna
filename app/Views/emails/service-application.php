<?php /** @var array $application */ ?>
<h2 style="font-family:Georgia,serif;color:#173D2F;margin:0 0 12px;">We received your service application</h2>
<p>Thank you, <?= e($application['name']) ?>. Your application reference is <strong><?= e($application['reference']) ?></strong>.</p>
<p>The service co-ordinator will be in touch as the weekend approaches to confirm your area and shift.</p>
<p><strong>Areas you offered:</strong><br><?= e($application['service_areas']) ?></p>

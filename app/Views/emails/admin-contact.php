<?php /** @var array $message */ ?>
<h2 style="font-family:Georgia,serif;color:#173D2F;margin:0 0 12px;">Website enquiry</h2>
<p><strong><?= e($message['name']) ?></strong><br><?= e($message['email']) ?><?= $message['phone'] ? ' &middot; ' . e($message['phone']) : '' ?></p>
<p><strong>Subject:</strong> <?= e($message['subject']) ?></p>
<blockquote style="border-left:3px solid #D9A441;padding-left:12px;color:#40514a;"><?= nl2br(e($message['message'])) ?></blockquote>
<p><a href="<?= e(url('/admin/messages')) ?>">Open in the admin</a></p>

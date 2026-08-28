<?php
/** @var string $contents @var string $path @var array $log */
use App\Core\View;
View::include('install._head', ['pageTitle' => 'One last step']);
?>
<h1>Almost there — one manual step</h1>
<div class="alert alert--warning">
  <div>
    <div class="alert__title">The configuration file could not be written</div>
    <p>The database is set up, but <code><?= e($path) ?></code> is not writable. Create that file with the cPanel File Manager and paste the contents below into it, then reload the site.</p>
  </div>
</div>

<h2>Paste this into <code>.env</code></h2>
<pre><?= e($contents) ?></pre>

<p class="muted">This file contains live credentials. Keep it outside <code>public_html</code>, never commit it to Git, and set its permissions to <code>600</code>.</p>

<h2 style="margin-top:2rem">Already done</h2>
<?php foreach ($log as $entry): ?>
  <div class="check"><span class="check__mark ok">✓</span><span><?= e($entry) ?></span></div>
<?php endforeach; ?>

<p style="margin-top:2rem"><a class="btn" href="/">Reload the website</a></p>
<?php View::include('install._foot'); ?>

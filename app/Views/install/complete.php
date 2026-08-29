<?php
/** @var array $log @var string $adminEmail @var string $appUrl @var bool $demoSeeded */
use App\Core\View;
View::include('install._head', ['pageTitle' => 'Installation complete']);
?>
<div class="success-hero" style="padding-block:1rem 2rem">
  <div class="success-hero__mark">✓</div>
  <h1 style="margin-bottom:.35rem">The website is live</h1>
  <p class="muted" style="margin-inline:auto">The installer has locked itself and cannot be run again.</p>
</div>

<?php if (!\App\Services\PayFastService::isConfigured()): ?>
  <div class="alert alert--warning">
    <div>
      <div class="alert__title">The site cannot take a payment yet</div>
      <p style="margin:.4rem 0 0">
        The PayFast merchant ID and merchant key were left blank, so a delegate who reaches the
        payment step is sent back to their basket with a message asking them to contact the
        committee. Everything else works — only the payment handoff is missing.
      </p>
      <p style="margin:.4rem 0 0">
        Enter them in <strong>Admin &rarr; Settings &rarr; Payments</strong>, then confirm
        <em>PayFast configured</em> is green under <strong>Settings &rarr; Diagnostics</strong>.
      </p>
    </div>
  </div>
<?php endif; ?>

<div class="alert alert--info">
  <div>
    <div class="alert__title">Do these three things next</div>
    <ol style="margin:.5rem 0 0;padding-left:1.2rem">
      <li>Sign in as <strong><?= e($adminEmail) ?></strong> and check <em>Admin &rarr; Settings &rarr; Diagnostics</em>.</li>
      <li>Run one full sandbox checkout end to end, including the PayFast notification.</li>
      <li>Delete <code>/public_html/install</code> if you uploaded a copy, and keep <code>app/Config/installed.lock</code> in place.</li>
    </ol>
  </div>
</div>

<h2>What was done</h2>
<div style="margin-bottom:2rem">
  <?php foreach ($log as $entry): ?>
    <div class="check"><span class="check__mark ok">✓</span><span><?= e($entry) ?></span></div>
  <?php endforeach; ?>
</div>

<?php if ($demoSeeded): ?>
<div class="alert alert--warning">
  <div>
    <div class="alert__title">Demo content is loaded</div>
    <p>Room types, products, transport routes, the programme, FAQs and the gallery are placeholder content marked as <strong>mock</strong> in the admin. Replace them with confirmed detail before launch, and switch off the preview banner in Admin &rarr; Settings.</p>
  </div>
</div>
<?php endif; ?>

<div class="cluster" style="margin-top:2rem">
  <a class="btn btn--lg" href="<?= e($appUrl) ?>/admin">Open the admin</a>
  <a class="btn btn--ghost btn--lg" href="<?= e($appUrl) ?>/">View the website</a>
</div>
<?php View::include('install._foot'); ?>

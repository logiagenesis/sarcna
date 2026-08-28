<?php
use App\Core\View;
View::layout('layouts.admin');
View::start('content');
$failing = array_filter($checks, static fn (array $check): bool => !$check[1]);
?>
<div class="admin-head">
  <div><h1>Diagnostics</h1><p>Run through this before the site goes live, and any time something looks wrong.</p></div>
  <a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/logs')) ?>">Logs</a>
</div>

<?php if ($failing === []): ?>
  <div class="alert alert--success"><div><div class="alert__title">Everything checks out</div><p>All <?= count($checks) ?> checks pass.</p></div></div>
<?php else: ?>
  <div class="alert alert--warning"><div><div class="alert__title"><?= count($failing) ?> check(s) need attention</div>
  <p>The site may still work, but these should be resolved before launch.</p></div></div>
<?php endif; ?>

<div class="admin-grid admin-grid--sidebar">
  <div>
    <div class="admin-panel">
      <h2>Checks</h2>
      <?php foreach ($checks as [$label, $passed, $note]): ?>
        <div class="check" style="display:flex;gap:.75rem;padding:.55rem 0;border-bottom:1px solid var(--line);font-size:var(--step--1)">
          <span style="width:22px;height:22px;border-radius:50%;display:grid;place-items:center;font-weight:700;flex-shrink:0;
                       background:<?= $passed ? 'rgba(47,125,79,.16)' : 'rgba(184,64,58,.14)' ?>;
                       color:<?= $passed ? 'var(--success)' : 'var(--error)' ?>"><?= $passed ? '✓' : '!' ?></span>
          <span><strong><?= e($label) ?></strong><br><span class="muted"><?= e((string) $note) ?></span></span>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="admin-panel">
      <h2>Send a test email</h2>
      <p class="muted" style="font-size:var(--step--1)">Confirms the SMTP settings in <code>.env</code> actually work.</p>
      <form method="post" action="<?= e(url('/admin/settings/test-email')) ?>" class="inline-form">
        <?= csrf_field() ?>
        <input type="email" name="email" value="<?= e($adminEmail) ?>" style="min-width:280px">
        <button class="btn btn--sm" type="submit">Send test email</button>
      </form>
    </div>
  </div>

  <div>
    <div class="admin-panel">
      <h2>PHP</h2>
      <?php foreach ($php as $key => $value): ?>
        <div class="summary__row"><span><?= e(str_replace('_', ' ', (string) $key)) ?></span><strong><?= e((string) $value) ?></strong></div>
      <?php endforeach; ?>
    </div>

    <div class="admin-panel">
      <h2>PayFast</h2>
      <?php foreach ($payfast as $key => $value): ?>
        <div class="summary__row"><span><?= e(str_replace('_', ' ', (string) $key)) ?></span><strong style="text-align:right;word-break:break-all"><?= e((string) $value) ?></strong></div>
      <?php endforeach; ?>
      <p class="muted" style="font-size:var(--step--1);margin-top:.75rem">
        The notify URL must be reachable from the public internet. PayFast posts to it server-to-server; it is the only
        thing that can mark an order as paid.
      </p>
    </div>

    <div class="admin-panel">
      <h2>Content counts</h2>
      <?php foreach ($counts as $key => $value): ?>
        <div class="summary__row"><span><?= e(str_replace('_', ' ', (string) $key)) ?></span><strong><?= (int) $value ?></strong></div>
      <?php endforeach; ?>
      <?php if ((int) $counts['mockRows'] > 0): ?>
        <p class="muted" style="font-size:var(--step--1);margin-top:.75rem">
          <?= (int) $counts['mockRows'] ?> record(s) are still flagged as placeholder content. Review them before launch
          and switch off the preview banner in Settings.
        </p>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php View::stop(); ?>

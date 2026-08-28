<?php
use App\Core\View;
View::layout('layouts.admin');
View::start('content');
?>
<div class="admin-head">
  <div><h1>Logs</h1><p>Application, payment and PHP logs from <code>/storage/logs</code>, newest first.</p></div>
  <a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/settings/diagnostics')) ?>">Diagnostics</a>
</div>

<div class="admin-toolbar">
  <form method="get" action="<?= e(url('/admin/logs')) ?>" data-autosubmit>
    <select name="file">
      <option value="">Choose a log file…</option>
      <?php foreach ($files as $file): ?>
        <option value="<?= e($file) ?>" <?= $selected === $file ? 'selected' : '' ?>><?= e($file) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn--sm" type="submit">Open</button>
  </form>
</div>

<?php if ($selected !== ''): ?>
  <div class="admin-panel" style="padding:0">
    <div style="max-height:60vh;overflow:auto">
      <?php foreach ($lines as $line): ?>
        <div class="log-line"><?= e($line) ?></div>
      <?php endforeach; ?>
      <?php if ($lines === []): ?><p class="muted" style="padding:1rem">That log file is empty.</p><?php endif; ?>
    </div>
  </div>
<?php elseif ($files === []): ?>
  <div class="empty-state"><div class="empty-state__icon">📄</div><h3>No log files yet</h3><p>Logs appear once the site records an error or a payment.</p></div>
<?php endif; ?>

<div class="admin-panel">
  <h2>Admin activity</h2>
  <div class="table-wrap">
    <table class="admin-table">
      <thead><tr><th>When</th><th>Who</th><th>Action</th><th>Entity</th><th>Source IP</th></tr></thead>
      <tbody>
        <?php foreach ($audit as $entry): ?>
          <tr>
            <td style="white-space:nowrap"><?= e(za_date((string) $entry['created_at'], 'j M, H:i')) ?></td>
            <td><?= e((string) $entry['user_email']) ?></td>
            <td><?= e($entry['action']) ?></td>
            <td><?= e((string) $entry['entity']) ?><?= $entry['entity_id'] ? ' #' . (int) $entry['entity_id'] : '' ?></td>
            <td><?= e((string) $entry['source_ip']) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if ($audit === []): ?><tr><td colspan="5" class="muted">No admin activity recorded yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php View::stop(); ?>

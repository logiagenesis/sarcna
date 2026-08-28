<?php
use App\Core\View;
View::layout('layouts.admin');
View::start('content');
?>
<div class="admin-head">
  <div><h1>Email templates</h1><p>Subject lines are editable here. The message bodies are branded templates in the codebase.</p></div>
  <a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/settings')) ?>">Settings</a>
</div>

<div class="admin-note">
  Placeholders such as <code>{reference}</code> are replaced when the email is sent.
  To change a message body, edit the matching file in <code>app/Views/emails/</code>.
</div>

<?php foreach ($templates as $template): ?>
  <div class="admin-panel">
    <form method="post" action="<?= e(url('/admin/settings/email-templates')) ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int) $template['id'] ?>">
      <div class="cluster cluster--between">
        <h2><?= e($template['name']) ?> <span class="badge"><?= e($template['key_name']) ?></span></h2>
        <span><?= (int) $template['is_active'] === 1 ? '<span class="badge badge--success">On</span>' : '<span class="badge">Off</span>' ?></span>
      </div>
      <div class="field">
        <label class="field__label">Subject</label>
        <input type="text" name="subject" value="<?= e($template['subject']) ?>" required>
        <?php if ($template['variables']): ?><p class="field__hint">Available placeholders: <?= e($template['variables']) ?></p><?php endif; ?>
      </div>
      <p class="muted" style="font-size:var(--step--1)"><?= e($template['body_html']) ?></p>
      <label class="checkbox"><input type="checkbox" name="is_active" value="1" <?= (int) $template['is_active'] === 1 ? 'checked' : '' ?>><span>Send this email</span></label>
      <button class="btn btn--sm" type="submit">Save</button>
    </form>
  </div>
<?php endforeach; ?>
<?php View::stop(); ?>

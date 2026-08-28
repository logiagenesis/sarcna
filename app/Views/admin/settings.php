<?php
use App\Core\View;
View::layout('layouts.admin');
View::start('content');
$labels = [
  'general' => 'General', 'contact' => 'Contact details', 'whatsapp' => 'WhatsApp button',
  'analytics' => 'Analytics & Search Console', 'shop' => 'Shop & bookings',
  'accounts' => 'Accounts', 'payments' => 'Payments',
];
?>
<div class="admin-head">
  <div><h1>Site settings</h1><p>Everything here is editable by the committee. Credentials live in <code>.env</code>, not in the database.</p></div>
  <div class="cluster">
    <a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/settings/email-templates')) ?>">Email templates</a>
    <a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/settings/diagnostics')) ?>">Diagnostics</a>
  </div>
</div>

<form method="post" action="<?= e(url('/admin/settings')) ?>">
  <?= csrf_field() ?>
  <?php foreach ($groups as $group => $settings): ?>
    <div class="admin-panel">
      <h2><?= e($labels[$group] ?? ucfirst((string) $group)) ?></h2>
      <?php foreach ($settings as $setting):
        $name = 'settings[' . $setting['key_name'] . ']';
        $id   = 'setting_' . $setting['key_name']; ?>
        <?php if ($setting['type'] === 'boolean'): ?>
          <label class="checkbox">
            <input type="checkbox" id="<?= e($id) ?>" name="<?= e($name) ?>" value="1" <?= in_array((string) $setting['value'], ['1', 'true', 'on'], true) ? 'checked' : '' ?>>
            <span><strong><?= e($setting['label']) ?></strong><?php if ($setting['description']): ?><br><span class="muted" style="font-size:var(--step--1)"><?= e($setting['description']) ?></span><?php endif; ?></span>
          </label>
        <?php else: ?>
          <div class="field">
            <label class="field__label" for="<?= e($id) ?>"><?= e($setting['label']) ?></label>
            <?php if ($setting['type'] === 'textarea'): ?>
              <textarea id="<?= e($id) ?>" name="<?= e($name) ?>" rows="3" style="min-height:80px"><?= e((string) $setting['value']) ?></textarea>
            <?php else: ?>
              <input type="<?= $setting['type'] === 'number' ? 'number' : ($setting['type'] === 'email' ? 'email' : 'text') ?>"
                     id="<?= e($id) ?>" name="<?= e($name) ?>" value="<?= e((string) $setting['value']) ?>">
            <?php endif; ?>
            <?php if ($setting['description']): ?><p class="field__hint"><?= e($setting['description']) ?></p><?php endif; ?>
            <p class="field__hint"><code><?= e($setting['key_name']) ?></code></p>
          </div>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>

  <button class="btn btn--lg" type="submit">Save all settings</button>
</form>
<?php View::stop(); ?>

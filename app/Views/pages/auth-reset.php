<?php
use App\Core\View;
View::layout('layouts.public');
View::start('content');
?>
<section class="section">
  <div class="container container--narrow">
    <div class="form-panel">
      <div class="rule"></div>
      <h1 style="font-size:var(--step-3)">Choose a new password</h1>

      <?php if ($token === ''): ?>
        <div class="alert alert--error"><div><p>This reset link is incomplete. Please <a href="<?= e(url('/forgot-password')) ?>">request a new one</a>.</p></div></div>
      <?php else: ?>
        <form method="post" action="<?= e(url('/reset-password')) ?>" data-once>
          <?= csrf_field() ?>
          <input type="hidden" name="token" value="<?= e($token) ?>">
          <div class="field">
            <label class="field__label" for="password">New password</label>
            <input type="password" id="password" name="password" required autocomplete="new-password">
            <p class="field__hint">At least 8 characters, with a letter and a number.</p>
            <?php if ($m = error_for('password')): ?><p class="field__error"><?= e($m) ?></p><?php endif; ?>
          </div>
          <div class="field">
            <label class="field__label" for="password_confirmation">Confirm new password</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
          </div>
          <button class="btn btn--block btn--lg" type="submit">Change my password</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php View::stop(); ?>

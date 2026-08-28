<?php
use App\Core\View;
View::layout('layouts.public');
View::start('content');
?>
<section class="section">
  <div class="container container--narrow">
    <div class="form-panel">
      <div class="rule"></div>
      <h1 style="font-size:var(--step-3)">Forgot your password</h1>
      <p class="muted">Give us the email address on your account and we will send a link to set a new password. The link lasts 60 minutes.</p>

      <form method="post" action="<?= e(url('/forgot-password')) ?>" data-once>
        <?= csrf_field() ?>
        <div class="field">
          <label class="field__label" for="email">Email address</label>
          <input type="email" id="email" name="email" required value="<?= e(old('email')) ?>">
          <?php if ($m = error_for('email')): ?><p class="field__error"><?= e($m) ?></p><?php endif; ?>
        </div>
        <button class="btn btn--block btn--lg" type="submit" data-busy-label="Sending…">Send the reset link</button>
      </form>

      <p style="margin-top:1.25rem;font-size:var(--step--1)"><a href="<?= e(url('/login')) ?>">Back to sign in</a></p>
    </div>
  </div>
</section>
<?php View::stop(); ?>

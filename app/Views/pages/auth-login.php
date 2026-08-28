<?php
use App\Core\View;
View::layout('layouts.public');
View::start('content');
?>
<section class="section">
  <div class="container container--narrow">
    <div class="form-panel">
      <div class="rule"></div>
      <h1 style="font-size:var(--step-3)">Sign in</h1>
      <p class="muted">Your orders, bed bookings and shuttle seats all live in one place.</p>

      <form method="post" action="<?= e(url('/login')) ?>" data-once>
        <?= csrf_field() ?>
        <div class="field">
          <label class="field__label" for="email">Email address</label>
          <input type="email" id="email" name="email" required autocomplete="username" value="<?= e(old('email')) ?>">
          <?php if ($m = error_for('email')): ?><p class="field__error"><?= e($m) ?></p><?php endif; ?>
        </div>
        <div class="field">
          <label class="field__label" for="password">Password</label>
          <input type="password" id="password" name="password" required autocomplete="current-password">
          <?php if ($m = error_for('password')): ?><p class="field__error"><?= e($m) ?></p><?php endif; ?>
        </div>
        <button class="btn btn--block btn--lg" type="submit" data-busy-label="Signing in…">Sign in</button>
      </form>

      <div class="cluster cluster--between" style="margin-top:1.25rem;font-size:var(--step--1)">
        <a href="<?= e(url('/forgot-password')) ?>">Forgot your password?</a>
        <a href="<?= e(url('/register')) ?>">Create an account</a>
      </div>
    </div>
  </div>
</section>
<?php View::stop(); ?>

<?php
use App\Core\View;
View::layout('layouts.public');
View::start('content');
?>
<section class="section">
  <div class="container container--narrow">
    <div class="form-panel">
      <div class="rule"></div>
      <h1 style="font-size:var(--step-3)">Create your account</h1>
      <p class="muted">An account keeps your registration, bed bookings and shuttle seats together, and gives you your check-in code.</p>

      <form method="post" action="<?= e(url('/register')) ?>" data-once>
        <?= csrf_field() ?>

        <div class="field-row field-row--2">
          <div class="field">
            <label class="field__label" for="first_name">First name</label>
            <input type="text" id="first_name" name="first_name" required value="<?= e(old('first_name')) ?>">
            <?php if ($m = error_for('first_name')): ?><p class="field__error"><?= e($m) ?></p><?php endif; ?>
          </div>
          <div class="field">
            <label class="field__label" for="last_name">Last name</label>
            <input type="text" id="last_name" name="last_name" required value="<?= e(old('last_name')) ?>">
            <?php if ($m = error_for('last_name')): ?><p class="field__error"><?= e($m) ?></p><?php endif; ?>
          </div>
        </div>

        <div class="field-row field-row--2">
          <div class="field">
            <label class="field__label" for="email">Email address</label>
            <input type="email" id="email" name="email" required autocomplete="username" value="<?= e(old('email')) ?>">
            <?php if ($m = error_for('email')): ?><p class="field__error"><?= e($m) ?></p><?php endif; ?>
          </div>
          <div class="field">
            <label class="field__label" for="phone">Mobile number</label>
            <input type="tel" id="phone" name="phone" required value="<?= e(old('phone')) ?>">
            <?php if ($m = error_for('phone')): ?><p class="field__error"><?= e($m) ?></p><?php endif; ?>
          </div>
        </div>

        <div class="field-row field-row--2">
          <div class="field">
            <label class="field__label" for="home_group">Home group <span class="muted">(optional)</span></label>
            <input type="text" id="home_group" name="home_group" value="<?= e(old('home_group')) ?>">
          </div>
          <div class="field">
            <label class="field__label" for="region">Area or region <span class="muted">(optional)</span></label>
            <input type="text" id="region" name="region" value="<?= e(old('region')) ?>">
          </div>
        </div>

        <div class="field-row field-row--2">
          <div class="field">
            <label class="field__label" for="password">Password</label>
            <input type="password" id="password" name="password" required autocomplete="new-password">
            <p class="field__hint">At least 8 characters, with a letter and a number.</p>
            <?php if ($m = error_for('password')): ?><p class="field__error"><?= e($m) ?></p><?php endif; ?>
          </div>
          <div class="field">
            <label class="field__label" for="password_confirmation">Confirm password</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
          </div>
        </div>

        <label class="checkbox">
          <input type="checkbox" name="terms" value="1" required>
          <span>I accept the <a href="<?= e(url('/terms')) ?>" target="_blank">terms</a> and the <a href="<?= e(url('/privacy-policy')) ?>" target="_blank">privacy policy</a>.</span>
        </label>
        <?php if ($m = error_for('terms')): ?><p class="field__error"><?= e($m) ?></p><?php endif; ?>

        <label class="checkbox">
          <input type="checkbox" name="marketing_opt_in" value="1">
          <span>Email me convention news and reminders. We never share your address.</span>
        </label>

        <button class="btn btn--block btn--lg" type="submit" data-busy-label="Creating your account…">Create account</button>
      </form>

      <p style="margin-top:1.25rem;font-size:var(--step--1)">Already have an account? <a href="<?= e(url('/login')) ?>">Sign in</a>.</p>
    </div>
  </div>
</section>
<?php View::stop(); ?>

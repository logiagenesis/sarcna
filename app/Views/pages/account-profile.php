<?php
use App\Core\View;
View::layout('layouts.public');
View::start('content');
?>
<section class="section">
  <div class="container">
    <div class="rule"></div>
    <h1 style="font-size:var(--step-3)">My profile</h1>
    <?php View::include('partials.account-nav'); ?>

    <div class="grid grid--2">
      <div class="form-panel">
        <h2 style="font-size:var(--step-2)">Your details</h2>
        <form method="post" action="<?= e(url('/account/profile')) ?>">
          <?= csrf_field() ?>
          <div class="field-row field-row--2">
            <div class="field">
              <label class="field__label" for="first_name">First name</label>
              <input type="text" id="first_name" name="first_name" required value="<?= e(old('first_name', $user['first_name'])) ?>">
            </div>
            <div class="field">
              <label class="field__label" for="last_name">Last name</label>
              <input type="text" id="last_name" name="last_name" required value="<?= e(old('last_name', $user['last_name'])) ?>">
            </div>
          </div>
          <div class="field">
            <label class="field__label" for="email">Email address</label>
            <input type="email" id="email" name="email" required value="<?= e(old('email', $user['email'])) ?>">
            <?php if ($m = error_for('email')): ?><p class="field__error"><?= e($m) ?></p><?php endif; ?>
            <p class="field__hint">Changing this means confirming the new address again.</p>
          </div>
          <div class="field">
            <label class="field__label" for="phone">Mobile number</label>
            <input type="tel" id="phone" name="phone" required value="<?= e(old('phone', (string) $user['phone'])) ?>">
          </div>
          <div class="field-row field-row--2">
            <div class="field">
              <label class="field__label" for="home_group">Home group</label>
              <input type="text" id="home_group" name="home_group" value="<?= e(old('home_group', (string) $user['home_group'])) ?>">
            </div>
            <div class="field">
              <label class="field__label" for="region">Area or region</label>
              <input type="text" id="region" name="region" value="<?= e(old('region', (string) $user['region'])) ?>">
            </div>
          </div>
          <div class="field">
            <label class="field__label" for="dietary_notes">Dietary notes</label>
            <input type="text" id="dietary_notes" name="dietary_notes" value="<?= e(old('dietary_notes', (string) $user['dietary_notes'])) ?>">
          </div>
          <div class="field">
            <label class="field__label" for="accessibility_notes">Accessibility notes</label>
            <input type="text" id="accessibility_notes" name="accessibility_notes" value="<?= e(old('accessibility_notes', (string) $user['accessibility_notes'])) ?>">
          </div>
          <label class="checkbox">
            <input type="checkbox" name="marketing_opt_in" value="1" <?= (int) $user['marketing_opt_in'] === 1 ? 'checked' : '' ?>>
            <span>Email me convention news and reminders.</span>
          </label>
          <button class="btn btn--block" type="submit">Save changes</button>
        </form>
      </div>

      <div>
        <div class="form-panel">
          <h2 style="font-size:var(--step-2)">Change password</h2>
          <form method="post" action="<?= e(url('/account/password')) ?>">
            <?= csrf_field() ?>
            <div class="field">
              <label class="field__label" for="current_password">Current password</label>
              <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
            </div>
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
            <button class="btn btn--block" type="submit">Change password</button>
          </form>
        </div>

        <div class="summary" style="position:static;margin-top:1.5rem">
          <h3 style="font-size:var(--step-1)">Account</h3>
          <div class="summary__row"><span>Member since</span><strong><?= e(za_date((string) $user['created_at'], 'j M Y')) ?></strong></div>
          <div class="summary__row"><span>Email confirmed</span><strong><?= $user['email_verified_at'] ? 'Yes' : 'Not yet' ?></strong></div>
          <div class="summary__row"><span>Last sign-in</span><strong><?= e(za_date((string) $user['last_login_at'], 'j M Y, H:i')) ?: '—' ?></strong></div>
          <hr>
          <p class="muted" style="font-size:var(--step--1)">
            To have your account and personal information deleted, write to us through the <a href="<?= e(url('/contact')) ?>">contact form</a>.
            See the <a href="<?= e(url('/privacy-policy')) ?>">privacy policy</a> for what we keep and for how long.
          </p>
        </div>
      </div>
    </div>
  </div>
</section>
<?php View::stop(); ?>

<?php
/** @var array $checks @var array $values @var array $errors */
use App\Core\View;

$pageTitle = 'Install';
$value = static fn (string $key, string $default = ''): string => e($values[$key] ?? $default);
$error = static fn (string $key): string => isset($errors[$key]) ? '<p class="field__error">' . e((string) reset($errors[$key])) . '</p>' : '';
$blocked = array_filter($checks, static fn (array $check): bool => !$check[1]);

View::include('install._head', ['pageTitle' => $pageTitle]);
?>
<h1>Install the convention website</h1>
<p class="muted">This runs once. It creates the database tables, loads the demo content, creates your administrator account and writes the configuration file. No SSH access is needed.</p>

<?php if ($errors !== []): ?>
  <div class="alert alert--error" style="margin-top:1.5rem">
    <div>
      <div class="alert__title">Please check the form</div>
      <ul style="margin:.35rem 0 0;padding-left:1.1rem">
        <?php foreach ($errors as $messages): foreach ((array) $messages as $message): ?>
          <li><?= e($message) ?></li>
        <?php endforeach; endforeach; ?>
      </ul>
    </div>
  </div>
<?php endif; ?>

<h2 style="margin-top:2rem">Server checks</h2>
<div style="margin-bottom:2rem">
  <?php foreach ($checks as [$label, $passed, $note]): ?>
    <div class="check">
      <span class="check__mark <?= $passed ? 'ok' : 'no' ?>"><?= $passed ? '✓' : '!' ?></span>
      <span><strong><?= e($label) ?></strong><br><span class="muted"><?= e($note) ?></span></span>
    </div>
  <?php endforeach; ?>
</div>

<?php if ($blocked !== []): ?>
  <div class="alert alert--warning">
    <div><div class="alert__title">Some checks did not pass</div>
    <p>You can still continue, but the installer may fail. Folder permissions are usually fixed in the cPanel File Manager (set folders to 755 and files to 644).</p></div>
  </div>
<?php endif; ?>

<form method="post" action="/install" data-once>
  <?= csrf_field() ?>

  <fieldset>
    <legend>Database</legend>
    <p class="muted" style="font-size:var(--step--1)">Create the database and user in <strong>cPanel &rarr; MySQL&reg; Databases</strong> first, then paste the details here. cPanel prefixes both names with your account name.</p>
    <div class="field-row field-row--2">
      <div class="field">
        <label class="field__label" for="db_host">Database host</label>
        <input type="text" id="db_host" name="db_host" value="<?= $value('db_host', 'localhost') ?>" required>
      </div>
      <div class="field">
        <label class="field__label" for="db_port">Port</label>
        <input type="text" id="db_port" name="db_port" value="<?= $value('db_port', '3306') ?>" required>
      </div>
    </div>
    <div class="field">
      <label class="field__label" for="db_name">Database name</label>
      <input type="text" id="db_name" name="db_name" value="<?= $value('db_name') ?>" placeholder="cpaneluser_sarcna27" required>
      <?= $error('db_name') ?>
    </div>
    <div class="field-row field-row--2">
      <div class="field">
        <label class="field__label" for="db_user">Database user</label>
        <input type="text" id="db_user" name="db_user" value="<?= $value('db_user') ?>" required>
      </div>
      <div class="field">
        <label class="field__label" for="db_pass">Database password</label>
        <input type="password" id="db_pass" name="db_pass" autocomplete="new-password">
      </div>
    </div>
    <label class="checkbox">
      <input type="checkbox" name="seed_demo" value="1" <?= ($values['seed_demo'] ?? '1') === '1' ? 'checked' : '' ?>>
      <span>Load the demo content — room types with real bed inventory, products, transport routes, programme, FAQs and gallery. Recommended for the committee preview.</span>
    </label>
  </fieldset>

  <fieldset>
    <legend>Website</legend>
    <div class="field">
      <label class="field__label" for="app_url">Website address</label>
      <input type="url" id="app_url" name="app_url" value="<?= $value('app_url') ?>" required>
      <p class="field__hint">Include https:// and no trailing slash. This is used for links in emails and for the PayFast return URLs.</p>
      <?= $error('app_url') ?>
    </div>
    <div class="field-row field-row--2">
      <div class="field">
        <label class="field__label" for="contact_email">Public contact email</label>
        <input type="email" id="contact_email" name="contact_email" value="<?= $value('contact_email') ?>" required>
        <?= $error('contact_email') ?>
      </div>
      <div class="field">
        <label class="field__label" for="admin_notification_email">Order notification email</label>
        <input type="email" id="admin_notification_email" name="admin_notification_email" value="<?= $value('admin_notification_email') ?>">
        <p class="field__hint">Leave blank to reuse the contact email.</p>
      </div>
    </div>
    <div class="field-row field-row--3">
      <div class="field">
        <label class="field__label" for="registration_email">Registration email</label>
        <input type="email" id="registration_email" name="registration_email" value="<?= $value('registration_email') ?>">
      </div>
      <div class="field">
        <label class="field__label" for="accommodation_email">Accommodation email</label>
        <input type="email" id="accommodation_email" name="accommodation_email" value="<?= $value('accommodation_email') ?>">
      </div>
      <div class="field">
        <label class="field__label" for="transport_email">Transport email</label>
        <input type="email" id="transport_email" name="transport_email" value="<?= $value('transport_email') ?>">
      </div>
    </div>
    <div class="field-row field-row--2">
      <div class="field">
        <label class="field__label" for="contact_phone">Public phone number</label>
        <input type="tel" id="contact_phone" name="contact_phone" value="<?= $value('contact_phone') ?>" placeholder="+27 21 000 0000">
      </div>
      <div class="field">
        <label class="field__label" for="whatsapp_number">WhatsApp number</label>
        <input type="tel" id="whatsapp_number" name="whatsapp_number" value="<?= $value('whatsapp_number') ?>" placeholder="27821234567">
        <p class="field__hint">International format, digits only. Leave blank to hide the floating button.</p>
      </div>
    </div>
  </fieldset>

  <fieldset>
    <legend>Administrator account</legend>
    <div class="field-row field-row--2">
      <div class="field">
        <label class="field__label" for="admin_first_name">First name</label>
        <input type="text" id="admin_first_name" name="admin_first_name" value="<?= $value('admin_first_name') ?>" required>
      </div>
      <div class="field">
        <label class="field__label" for="admin_last_name">Last name</label>
        <input type="text" id="admin_last_name" name="admin_last_name" value="<?= $value('admin_last_name') ?>" required>
      </div>
    </div>
    <div class="field">
      <label class="field__label" for="admin_email">Email address</label>
      <input type="email" id="admin_email" name="admin_email" value="<?= $value('admin_email') ?>" required autocomplete="username">
      <?= $error('admin_email') ?>
    </div>
    <div class="field-row field-row--2">
      <div class="field">
        <label class="field__label" for="admin_password">Password</label>
        <input type="password" id="admin_password" name="admin_password" required autocomplete="new-password">
        <p class="field__hint">At least 8 characters, with a letter and a number.</p>
        <?= $error('admin_password') ?>
      </div>
      <div class="field">
        <label class="field__label" for="admin_password_confirmation">Confirm password</label>
        <input type="password" id="admin_password_confirmation" name="admin_password_confirmation" required autocomplete="new-password">
      </div>
    </div>
  </fieldset>

  <fieldset>
    <legend>PayFast</legend>
    <p class="muted" style="font-size:var(--step--1)">Start in sandbox mode and switch to live once the committee has tested a full checkout. These values are written to <code>.env</code> and are never committed to Git.</p>
    <div class="field">
      <label class="field__label" for="payfast_mode">Mode</label>
      <select id="payfast_mode" name="payfast_mode">
        <option value="sandbox" <?= ($values['payfast_mode'] ?? 'sandbox') === 'sandbox' ? 'selected' : '' ?>>Sandbox (testing)</option>
        <option value="live" <?= ($values['payfast_mode'] ?? '') === 'live' ? 'selected' : '' ?>>Live (real payments)</option>
      </select>
    </div>
    <div class="field-row field-row--2">
      <div class="field">
        <label class="field__label" for="payfast_merchant_id">Merchant ID</label>
        <input type="text" id="payfast_merchant_id" name="payfast_merchant_id" value="<?= $value('payfast_merchant_id') ?>">
      </div>
      <div class="field">
        <label class="field__label" for="payfast_merchant_key">Merchant key</label>
        <input type="text" id="payfast_merchant_key" name="payfast_merchant_key" value="<?= $value('payfast_merchant_key') ?>">
      </div>
    </div>
    <div class="field">
      <label class="field__label" for="payfast_passphrase">Passphrase</label>
      <input type="text" id="payfast_passphrase" name="payfast_passphrase" value="<?= $value('payfast_passphrase') ?>">
      <p class="field__hint">Set the same passphrase in your PayFast dashboard under Settings &rarr; Security. It is required for signature validation.</p>
    </div>
  </fieldset>

  <fieldset>
    <legend>Email sending</legend>
    <div class="field">
      <label class="field__label" for="mail_driver">Method</label>
      <select id="mail_driver" name="mail_driver">
        <option value="smtp" <?= ($values['mail_driver'] ?? 'smtp') === 'smtp' ? 'selected' : '' ?>>SMTP — a cPanel mailbox (recommended)</option>
        <option value="mail" <?= ($values['mail_driver'] ?? '') === 'mail' ? 'selected' : '' ?>>PHP mail() — simplest, poorer deliverability</option>
        <option value="log"  <?= ($values['mail_driver'] ?? '') === 'log' ? 'selected' : '' ?>>Write to /storage/email-queue — testing only</option>
      </select>
    </div>
    <div class="field-row field-row--3">
      <div class="field">
        <label class="field__label" for="mail_host">SMTP host</label>
        <input type="text" id="mail_host" name="mail_host" value="<?= $value('mail_host') ?>">
      </div>
      <div class="field">
        <label class="field__label" for="mail_port">Port</label>
        <input type="text" id="mail_port" name="mail_port" value="<?= $value('mail_port', '587') ?>">
      </div>
      <div class="field">
        <label class="field__label" for="mail_encryption">Encryption</label>
        <select id="mail_encryption" name="mail_encryption">
          <option value="tls" <?= ($values['mail_encryption'] ?? 'tls') === 'tls' ? 'selected' : '' ?>>STARTTLS (587)</option>
          <option value="ssl" <?= ($values['mail_encryption'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL (465)</option>
          <option value="none" <?= ($values['mail_encryption'] ?? '') === 'none' ? 'selected' : '' ?>>None (25)</option>
        </select>
      </div>
    </div>
    <div class="field-row field-row--3">
      <div class="field">
        <label class="field__label" for="mail_username">Mailbox username</label>
        <input type="text" id="mail_username" name="mail_username" value="<?= $value('mail_username') ?>" autocomplete="off">
      </div>
      <div class="field">
        <label class="field__label" for="mail_password">Mailbox password</label>
        <input type="password" id="mail_password" name="mail_password" autocomplete="new-password">
      </div>
      <div class="field">
        <label class="field__label" for="mail_from_address">From address</label>
        <input type="email" id="mail_from_address" name="mail_from_address" value="<?= $value('mail_from_address') ?>">
      </div>
    </div>
  </fieldset>

  <fieldset>
    <legend>Analytics &amp; Search Console</legend>
    <div class="field-row field-row--2">
      <div class="field">
        <label class="field__label" for="ga_measurement_id">GA4 measurement ID</label>
        <input type="text" id="ga_measurement_id" name="ga_measurement_id" value="<?= $value('ga_measurement_id') ?>" placeholder="G-XXXXXXXXXX">
      </div>
      <div class="field">
        <label class="field__label" for="google_site_verification">Search Console verification code</label>
        <input type="text" id="google_site_verification" name="google_site_verification" value="<?= $value('google_site_verification') ?>">
        <p class="field__hint">The content value from the HTML tag method. Both can be added later in Admin &rarr; Settings.</p>
      </div>
    </div>
  </fieldset>

  <button class="btn btn--lg btn--block" type="submit" data-busy-label="Installing…">Install the website</button>
</form>
<?php View::include('install._foot'); ?>

<?php
use App\Core\View;
View::layout('layouts.admin');
View::start('content');
?>
<div class="admin-head">
  <div>
    <a class="link-arrow" href="<?= e(url('/admin/applications')) ?>">&larr; Applications</a>
    <h1 style="margin-top:.5rem"><?= e($application['name']) ?></h1>
    <p><?= e($application['reference']) ?> &middot; submitted <?= e(za_date((string) $application['created_at'], 'j F Y, H:i')) ?></p>
  </div>
</div>

<div class="admin-grid admin-grid--sidebar">
  <div class="admin-panel">
    <h2>Application</h2>
    <div class="summary__row"><span>Email</span><strong><a href="mailto:<?= e($application['email']) ?>"><?= e($application['email']) ?></a></strong></div>
    <div class="summary__row"><span>Phone</span><strong><?= e($application['phone']) ?></strong></div>
    <div class="summary__row"><span>Region</span><strong><?= e((string) $application['region']) ?: '—' ?></strong></div>
    <div class="summary__row"><span>Home group</span><strong><?= e((string) $application['home_group']) ?: '—' ?></strong></div>
    <div class="summary__row"><span>Clean time</span><strong><?= e((string) $application['clean_time']) ?: '—' ?></strong></div>
    <div class="summary__row"><span>Availability</span><strong style="text-align:right"><?= e((string) $application['availability']) ?: '—' ?></strong></div>

    <h3 style="margin-top:1.25rem">Service areas</h3>
    <div class="cluster">
      <?php foreach (array_filter(array_map('trim', explode(',', (string) $application['service_areas']))) as $area): ?>
        <span class="badge"><?= e($area) ?></span>
      <?php endforeach; ?>
    </div>

    <?php if ($application['skills']): ?>
      <h3 style="margin-top:1.25rem">Skills</h3>
      <p><?= nl2br(e($application['skills'])) ?></p>
    <?php endif; ?>

    <?php if ($application['notes']): ?>
      <h3 style="margin-top:1.25rem">Notes from the applicant</h3>
      <p><?= nl2br(e($application['notes'])) ?></p>
    <?php endif; ?>
  </div>

  <div>
    <div class="admin-panel">
      <h2>Status &amp; notes</h2>
      <form method="post" action="<?= e(url('/admin/applications/' . $application['id'])) ?>">
        <?= csrf_field() ?>
        <div class="field">
          <label class="field__label">Status</label>
          <select name="status">
            <?php foreach (['new' => 'New', 'reviewing' => 'Reviewing', 'accepted' => 'Accepted', 'waitlisted' => 'Waitlisted', 'declined' => 'Declined'] as $value => $label): ?>
              <option value="<?= $value ?>" <?= $application['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label class="field__label">Internal notes</label>
          <textarea name="admin_notes" rows="5"><?= e((string) $application['admin_notes']) ?></textarea>
        </div>
        <button class="btn btn--block btn--sm" type="submit">Save</button>
      </form>
    </div>

    <div class="admin-panel">
      <h2>Email this applicant</h2>
      <form method="post" action="<?= e(url('/admin/applications/' . $application['id'] . '/email')) ?>">
        <?= csrf_field() ?>
        <div class="field">
          <label class="field__label">Subject</label>
          <input type="text" name="subject" value="Your SARCNA 2027 service application" required>
        </div>
        <div class="field">
          <label class="field__label">Message</label>
          <textarea name="body" rows="6" required placeholder="Hi <?= e($application['name']) ?>, thank you for offering service…"></textarea>
        </div>
        <button class="btn btn--block btn--sm btn--ghost" type="submit">Send email</button>
      </form>
    </div>
  </div>
</div>
<?php View::stop(); ?>

<?php
use App\Core\View;
View::layout('layouts.admin');
View::start('content');

$percent = $progress['total'] > 0 ? (int) round($progress['real'] / $progress['total'] * 100) : 0;
?>
<div class="admin-head">
  <div>
    <h1>Photographs</h1>
    <p>Every picture the public site shows. Upload the real photographs here — no developer, no command line.</p>
  </div>
</div>

<div class="admin-panel">
  <div class="admin-panel__head">
    <h2>Real photographs in place</h2>
    <p class="muted" style="margin:0"><strong><?= (int) $progress['real'] ?></strong> of <?= (int) $progress['total'] ?> slots</p>
  </div>
  <div class="photo-progress" role="img" aria-label="<?= $percent ?>% of image slots have a real photograph">
    <span class="photo-progress__fill" style="width:<?= $percent ?>%"></span>
  </div>
  <p class="muted" style="font-size:var(--step--1);margin:.6rem 0 0">
    Slots still marked <em>Illustration</em> are showing the drawings this site ships with. They are deliberately
    plain so nobody mistakes them for the venue. Replace them before launch.
  </p>
</div>

<div class="admin-panel">
  <h2>Before you upload</h2>
  <ul class="checklist">
    <li>Use the <strong>original</strong> file from the camera or the one the venue sent you. A picture saved out of WhatsApp or copied off a web page is too small and will be refused.</li>
    <li>Each slot lists the size it needs. Anything smaller is rejected with the reason — the site will not show a soft photograph.</li>
    <li>Pictures are cropped to fit from the centre, so leave a little room around the subject.</li>
    <li>Write the alt text as if describing the picture to somebody on the telephone. It is required.</li>
    <li>Record where the photograph came from. If the committee is ever asked to prove permission, that note is the answer.</li>
  </ul>
</div>

<?php foreach ($groups as $groupName => $slots): ?>
  <div class="admin-panel">
    <h2><?= e((string) $groupName) ?></h2>

    <div class="photo-grid">
      <?php foreach ($slots as $slot): ?>
        <div class="photo-slot<?= $slot['placeholder'] ? ' photo-slot--placeholder' : '' ?>">
          <div class="photo-slot__media">
            <?= picture((string) ($slot['current'] ?? 'img/backgrounds/placeholder.jpg'), (string) $slot['label']) ?>
            <span class="photo-slot__tag<?= $slot['placeholder'] ? '' : ' photo-slot__tag--real' ?>">
              <?= $slot['placeholder'] ? 'Illustration' : 'Photograph' ?>
            </span>
          </div>

          <div class="photo-slot__body">
            <p class="photo-slot__label"><?= e((string) $slot['label']) ?></p>
            <p class="muted" style="font-size:.72rem;margin:.15rem 0 .5rem"><?= e((string) $slot['note']) ?></p>

            <form method="post" action="<?= e(url('/admin/photos')) ?>" enctype="multipart/form-data" class="photo-slot__form">
              <?= csrf_field() ?>
              <input type="hidden" name="slot" value="<?= e((string) $slot['key']) ?>">

              <label class="field__label" for="photo-<?= e((string) $slot['key']) ?>">
                Photograph <span class="muted">— at least <?= (int) $slot['width'] ?>×<?= (int) $slot['height'] ?></span>
              </label>
              <input type="file" id="photo-<?= e((string) $slot['key']) ?>" name="photo" accept="image/jpeg,image/png,image/webp" required>

              <label class="field__label" for="alt-<?= e((string) $slot['key']) ?>">Describe it <span style="color:var(--error)">*</span></label>
              <input type="text" id="alt-<?= e((string) $slot['key']) ?>" name="alt_text" required
                     placeholder="<?= e((string) $slot['label']) ?> at Boschendal">

              <label class="field__label" for="credit-<?= e((string) $slot['key']) ?>">Where it came from</label>
              <input type="text" id="credit-<?= e((string) $slot['key']) ?>" name="credit"
                     placeholder="Supplied by Boschendal, March 2027">

              <button class="btn btn--sm btn--block" type="submit">
                <?= $slot['placeholder'] ? 'Upload photograph' : 'Replace' ?>
              </button>
            </form>

            <?php if (!$slot['placeholder']): ?>
              <form method="post" action="<?= e(url('/admin/photos/reset')) ?>"
                    data-confirm="Put this slot back to the shipped illustration? The uploaded file stays on the server."
                    style="margin-top:.4rem">
                <?= csrf_field() ?>
                <input type="hidden" name="slot" value="<?= e((string) $slot['key']) ?>">
                <button class="btn btn--sm btn--ghost btn--block" type="submit">Back to the illustration</button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endforeach; ?>

<?php View::stop(); ?>

<?php
use App\Core\View;
View::layout('layouts.admin');
View::start('content');
?>
<div class="admin-head"><div><h1>Gallery</h1><p><?= count($images) ?> images. Every image needs alt text.</p></div></div>

<div class="admin-grid admin-grid--sidebar">
  <div>
    <?php
    $grouped = [];
    foreach ($images as $image) { $grouped[$image['category']][] = $image; }
    ?>
    <?php foreach ($grouped as $category => $items): ?>
      <div class="admin-panel">
        <h2><?= e(ucfirst((string) $category)) ?> <span class="muted" style="font-size:var(--step--1)">(<?= count($items) ?>)</span></h2>
        <div class="grid grid--4">
          <?php foreach ($items as $image): ?>
            <div class="card">
              <div class="card__media"><?= picture($image['file_path'], $image['alt_text']) ?></div>
              <div class="card__body" style="padding:.75rem">
                <p style="font-weight:700;font-size:var(--step--1);margin:0"><?= e((string) $image['title']) ?></p>
                <p class="muted" style="font-size:.72rem;margin:.2rem 0"><?= e($image['alt_text']) ?></p>
                <?php if ($image['source_note']): ?><p class="muted" style="font-size:.68rem;margin:0"><?= e($image['source_note']) ?></p><?php endif; ?>
                <form method="post" action="<?= e(url('/admin/gallery/' . $image['id'] . '/delete')) ?>" data-confirm="Remove this image from the gallery?" style="margin-top:.5rem">
                  <?= csrf_field() ?>
                  <button class="btn btn--sm btn--ghost" type="submit">Remove</button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>

    <?php if ($images === []): ?>
      <div class="empty-state"><div class="empty-state__icon">🖼</div><h3>No images yet</h3><p>Upload venue photography using the form.</p></div>
    <?php endif; ?>
  </div>

  <div class="admin-panel">
    <h2>Add an image</h2>
    <form method="post" action="<?= e(url('/admin/gallery')) ?>" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <div class="field">
        <label class="field__label" for="image_file">Upload</label>
        <input type="file" id="image_file" name="image_file" accept="image/*">
        <p class="field__hint">JPEG, PNG or WebP, up to 6 MB. A WebP copy is generated automatically.</p>
      </div>
      <div class="field">
        <label class="field__label" for="file_path">Or the path of an existing image</label>
        <input type="text" id="file_path" name="file_path" placeholder="/assets/img/venue/fynbos-gardens.jpg">
      </div>
      <div class="field">
        <label class="field__label" for="title">Title</label>
        <input type="text" id="title" name="title">
      </div>
      <div class="field">
        <label class="field__label" for="alt_text">Alt text <span style="color:var(--error)">*</span></label>
        <input type="text" id="alt_text" name="alt_text" required>
        <p class="field__hint">Describe the image for screen readers and search engines.</p>
      </div>
      <div class="field">
        <label class="field__label" for="category">Category</label>
        <select id="category" name="category">
          <?php foreach ($categories as $category): ?><option value="<?= e($category) ?>"><?= e(ucfirst($category)) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label class="field__label" for="source_note">Source &amp; permission note</label>
        <input type="text" id="source_note" name="source_note" placeholder="Supplied by the venue, licence reference…">
        <p class="field__hint">Recorded so the committee can prove permission later.</p>
      </div>
      <div class="field">
        <label class="field__label" for="sort_order">Sort order</label>
        <input type="number" id="sort_order" name="sort_order" value="0">
      </div>
      <button class="btn btn--block btn--sm" type="submit">Add image</button>
    </form>
  </div>
</div>
<?php View::stop(); ?>

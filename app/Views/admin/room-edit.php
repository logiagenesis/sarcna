<?php
use App\Core\View;
use App\Services\AccommodationService;
View::layout('layouts.admin');
View::start('content');
$isNew = $roomType === null;
$value = static fn (string $key, mixed $default = '') => e($roomType[$key] ?? $default);
?>
<div class="admin-head">
  <div>
    <a class="link-arrow" href="<?= e(url('/admin/rooms')) ?>">&larr; Room types</a>
    <h1 style="margin-top:.5rem"><?= $isNew ? 'New room type' : e($roomType['name']) ?></h1>
  </div>
  <?php if (!$isNew): ?>
    <a class="btn btn--sm btn--ghost" href="<?= e(url('/accommodation/' . $roomType['slug'])) ?>" target="_blank">View on the site</a>
  <?php endif; ?>
</div>

<div class="admin-grid admin-grid--sidebar">
  <div>
    <form method="post" action="<?= e(url($isNew ? '/admin/rooms' : '/admin/rooms/' . $roomType['id'])) ?>" enctype="multipart/form-data">
      <?= csrf_field() ?>

      <div class="admin-panel">
        <h2>Basics</h2>
        <div class="field">
          <label class="field__label" for="name">Name</label>
          <input type="text" id="name" name="name" required value="<?= $value('name') ?>" data-slug-source="slug">
        </div>
        <div class="field-row field-row--2">
          <div class="field">
            <label class="field__label" for="slug">URL slug</label>
            <input type="text" id="slug" name="slug" value="<?= $value('slug') ?>">
          </div>
          <div class="field">
            <label class="field__label" for="sort_order">Sort order</label>
            <input type="number" id="sort_order" name="sort_order" value="<?= $value('sort_order', 0) ?>">
          </div>
        </div>
        <div class="field">
          <label class="field__label" for="summary">Short summary</label>
          <input type="text" id="summary" name="summary" value="<?= $value('summary') ?>" maxlength="255">
        </div>
        <div class="field">
          <label class="field__label" for="description">Description (HTML allowed)</label>
          <textarea id="description" name="description" rows="8"><?= $value('description') ?></textarea>
        </div>
        <div class="field">
          <label class="field__label" for="amenities">Amenities</label>
          <input type="text" id="amenities" name="amenities" value="<?= $value('amenities') ?>">
          <p class="field__hint">Separate each one with a vertical bar: <code>Two single beds|En-suite bathroom|Heating</code></p>
        </div>
      </div>

      <div class="admin-panel">
        <h2>Beds and pricing</h2>
        <div class="field-row field-row--3">
          <div class="field">
            <label class="field__label" for="beds_per_unit">Beds per unit</label>
            <input type="number" id="beds_per_unit" name="beds_per_unit" min="1" max="20" required value="<?= $value('beds_per_unit', 2) ?>">
          </div>
          <div class="field">
            <label class="field__label" for="bed_rate">Rate per bed per night (R)</label>
            <input type="number" id="bed_rate" name="bed_rate" step="0.01" min="0" required
                   value="<?= $isNew ? '' : number_format(((int) $roomType['bed_rate_cents']) / 100, 2, '.', '') ?>">
          </div>
          <div class="field">
            <label class="field__label" for="private_unit_rate">Whole unit per night (R)</label>
            <input type="number" id="private_unit_rate" name="private_unit_rate" step="0.01" min="0"
                   value="<?= $isNew || $roomType['private_unit_rate_cents'] === null ? '' : number_format(((int) $roomType['private_unit_rate_cents']) / 100, 2, '.', '') ?>">
          </div>
        </div>
        <label class="checkbox"><input type="checkbox" name="allows_private_buyout" value="1" <?= $isNew || (int) $roomType['allows_private_buyout'] === 1 ? 'checked' : '' ?>><span>Allow private unit buyout</span></label>
        <label class="checkbox"><input type="checkbox" name="is_accessible" value="1" <?= !$isNew && (int) $roomType['is_accessible'] === 1 ? 'checked' : '' ?>><span>Step-free / accessible unit</span></label>
        <label class="checkbox"><input type="checkbox" name="is_offsite" value="1" <?= !$isNew && (int) $roomType['is_offsite'] === 1 ? 'checked' : '' ?>><span>Off the estate (partner property)</span></label>
      </div>

      <div class="admin-panel">
        <h2>Image &amp; SEO</h2>
        <div class="field">
          <label class="field__label" for="hero_image_file">Upload a hero image</label>
          <input type="file" id="hero_image_file" name="hero_image_file" accept="image/*">
          <p class="field__hint">JPEG, PNG or WebP, up to 6 MB. A WebP copy is generated automatically.</p>
        </div>
        <div class="field">
          <label class="field__label" for="hero_image">Or the path of an existing image</label>
          <input type="text" id="hero_image" name="hero_image" value="<?= $value('hero_image') ?>">
        </div>
        <div class="field">
          <label class="field__label" for="meta_title">Meta title</label>
          <input type="text" id="meta_title" name="meta_title" value="<?= $value('meta_title') ?>" maxlength="180">
        </div>
        <div class="field">
          <label class="field__label" for="meta_description">Meta description</label>
          <textarea id="meta_description" name="meta_description" rows="2" style="min-height:70px" maxlength="255"><?= $value('meta_description') ?></textarea>
        </div>
        <label class="checkbox"><input type="checkbox" name="is_active" value="1" <?= $isNew || (int) $roomType['is_active'] === 1 ? 'checked' : '' ?>><span>Visible on the website</span></label>
        <label class="checkbox"><input type="checkbox" name="is_mock" value="1" <?= !$isNew && (int) $roomType['is_mock'] === 1 ? 'checked' : '' ?>><span>Still placeholder content</span></label>

        <button class="btn btn--block" type="submit"><?= $isNew ? 'Create room type' : 'Save changes' ?></button>
      </div>
    </form>

    <?php if (!$isNew): ?>
      <div class="admin-panel">
        <h2>Nightly rates and availability</h2>
        <form method="post" action="<?= e(url('/admin/rooms/' . $roomType['id'] . '/rates')) ?>">
          <?= csrf_field() ?>
          <div class="table-wrap" data-rate-table>
            <table class="admin-table">
              <thead><tr><th>Night</th><th>Per bed (R)</th><th>Whole unit (R)</th><th>Label</th><th>On sale</th></tr></thead>
              <tbody>
                <?php foreach ($nights as $night): $rate = $rates[$night] ?? null; ?>
                  <tr>
                    <td><strong><?= e(za_date($night, 'D j M Y')) ?></strong></td>
                    <td><input type="number" step="0.01" min="0" name="bed_rate_<?= e($night) ?>" data-rate
                               value="<?= $rate ? number_format($rate['bed'] / 100, 2, '.', '') : '' ?>" style="max-width:120px"></td>
                    <td><input type="number" step="0.01" min="0" name="unit_rate_<?= e($night) ?>"
                               value="<?= $rate && $rate['unit'] !== null ? number_format($rate['unit'] / 100, 2, '.', '') : '' ?>" style="max-width:120px"></td>
                    <td><input type="text" name="label_<?= e($night) ?>" value="<?= e((string) ($rate['label'] ?? '')) ?>" placeholder="e.g. Early arrival" style="max-width:160px"></td>
                    <td><input type="checkbox" name="available_<?= e($night) ?>" value="1" <?= !$rate || $rate['available'] ? 'checked' : '' ?>></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <button class="btn btn--sm" style="margin-top:.75rem" type="submit">Save rates</button>
        </form>
      </div>

      <div class="admin-panel">
        <h2>Units and beds</h2>
        <div class="admin-note">
          Adding units never touches existing ones, so it is safe to add capacity mid-sale.
          A unit or bed with confirmed bookings cannot be taken out of service.
        </div>

        <form method="post" action="<?= e(url('/admin/rooms/' . $roomType['id'] . '/units')) ?>" class="inline-form" style="margin-bottom:1rem">
          <?= csrf_field() ?>
          <label>Add <input type="number" name="unit_count" value="1" min="1" max="200" style="width:80px"> unit(s)</label>
          <label>with <input type="number" name="beds_per_unit" value="<?= (int) $roomType['beds_per_unit'] ?>" min="1" max="20" style="width:80px"> bed(s) each</label>
          <button class="btn btn--sm" type="submit">Generate</button>
        </form>

        <div class="table-wrap">
          <table class="admin-table">
            <thead><tr><th>Unit</th><th>Code</th><th>Beds</th><th>Status</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($units as $unit): ?>
                <tr>
                  <td><strong><?= e($unit['name']) ?></strong></td>
                  <td><?= e((string) $unit['code']) ?></td>
                  <td>
                    <div class="bed-diagram">
                      <?php foreach ($beds as $bed): if ((int) $bed['room_unit_id'] !== (int) $unit['id']) continue; ?>
                        <span class="bed-dot <?= (int) $bed['is_active'] !== 1 ? 'is-held' : ((int) $bed['booked_nights'] > 0 ? 'is-taken' : '') ?>"
                              title="<?= e($bed['label']) ?> — <?= (int) $bed['booked_nights'] ?> night(s) booked<?= (int) $bed['is_active'] !== 1 ? ', out of service' : '' ?>">
                          <?= e(substr((string) $bed['label'], -1)) ?>
                        </span>
                      <?php endforeach; ?>
                    </div>
                  </td>
                  <td><?= (int) $unit['is_active'] === 1 ? '<span class="badge badge--success">In service</span>' : '<span class="badge badge--error">Out of service</span>' ?></td>
                  <td>
                    <form method="post" action="<?= e(url('/admin/rooms/units/' . $unit['id'] . '/toggle')) ?>">
                      <?= csrf_field() ?>
                      <button class="btn btn--sm btn--ghost" type="submit"><?= (int) $unit['is_active'] === 1 ? 'Take out' : 'Put back' ?></button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if ($units === []): ?><tr><td colspan="5" class="muted">No units yet — generate some above.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <div>
    <div class="admin-panel">
      <h2>How this is sold</h2>
      <p style="font-size:var(--step--1)">
        Every bed is an individual line of inventory keyed on <code>(bed, night)</code>. The database refuses a second
        booking for the same bed-night, so overselling is impossible even if two people check out at the same instant.
      </p>
      <p style="font-size:var(--step--1)">
        A booking that is cancelled or refunded releases its bed automatically.
      </p>
    </div>

    <?php if (!$isNew && $images !== []): ?>
      <div class="admin-panel">
        <h2>Gallery images</h2>
        <?php foreach ($images as $image): ?>
          <p style="font-size:var(--step--1)"><?= e($image['file_path']) ?><br><span class="muted"><?= e((string) $image['source_note']) ?></span></p>
        <?php endforeach; ?>
        <a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/gallery')) ?>">Manage the gallery</a>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php View::stop(); ?>

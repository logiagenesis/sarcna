<?php
use App\Core\View;
View::layout('layouts.admin');
View::start('content');
$tabs = ['banners' => 'Banners', 'pages' => 'Pages & policies', 'programme' => 'Programme', 'faqs' => 'FAQs', 'events' => 'Upcoming events'];
?>
<div class="admin-head"><div><h1>Content</h1><p>Everything on the public site that is not a product or a room.</p></div></div>

<div class="admin-tabs">
  <?php foreach ($tabs as $key => $label): ?>
    <a href="<?= e(url('/admin/content?tab=' . $key)) ?>" class="<?= $tab === $key ? 'is-active' : '' ?>"><?= e($label) ?></a>
  <?php endforeach; ?>
</div>

<?php if ($tab === 'banners'): ?>
  <?php foreach ($banners as $banner): ?>
    <div class="admin-panel">
      <form method="post" action="<?= e(url('/admin/content/banners')) ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $banner['id'] ?>">
        <div class="cluster cluster--between">
          <h2><?= e($banner['title']) ?> <span class="badge"><?= e($banner['position']) ?></span></h2>
          <span><?= (int) $banner['is_active'] === 1 ? '<span class="badge badge--success">Live</span>' : '<span class="badge">Off</span>' ?></span>
        </div>
        <div class="field-row field-row--2">
          <div class="field"><label class="field__label">Title</label><input type="text" name="title" value="<?= e($banner['title']) ?>" required></div>
          <div class="field"><label class="field__label">Position key</label><input type="text" name="position" value="<?= e($banner['position']) ?>" required></div>
        </div>
        <div class="field"><label class="field__label">Subtitle</label><input type="text" name="subtitle" value="<?= e((string) $banner['subtitle']) ?>"></div>
        <div class="field"><label class="field__label">Body</label><textarea name="body" rows="2" style="min-height:70px"><?= e((string) $banner['body']) ?></textarea></div>
        <div class="field-row field-row--2">
          <div class="field"><label class="field__label">Button label</label><input type="text" name="cta_label" value="<?= e((string) $banner['cta_label']) ?>"></div>
          <div class="field"><label class="field__label">Button link</label><input type="text" name="cta_url" value="<?= e((string) $banner['cta_url']) ?>"></div>
        </div>
        <div class="field-row field-row--2">
          <div class="field"><label class="field__label">Second button label</label><input type="text" name="secondary_label" value="<?= e((string) $banner['secondary_label']) ?>"></div>
          <div class="field"><label class="field__label">Second button link</label><input type="text" name="secondary_url" value="<?= e((string) $banner['secondary_url']) ?>"></div>
        </div>
        <div class="field-row field-row--2">
          <div class="field"><label class="field__label">Image path</label><input type="text" name="image" value="<?= e((string) $banner['image']) ?>"></div>
          <div class="field"><label class="field__label">Or upload</label><input type="file" name="image_file" accept="image/*"></div>
        </div>
        <div class="field"><label class="field__label">Image alt text</label><input type="text" name="image_alt" value="<?= e((string) $banner['image_alt']) ?>"></div>
        <div class="field"><label class="field__label">Sort order</label><input type="number" name="sort_order" value="<?= (int) $banner['sort_order'] ?>" style="max-width:120px"></div>
        <label class="checkbox"><input type="checkbox" name="is_active" value="1" <?= (int) $banner['is_active'] === 1 ? 'checked' : '' ?>><span>Live on the site</span></label>
        <div class="cluster">
          <button class="btn btn--sm" type="submit">Save banner</button>
        </div>
      </form>
      <form method="post" action="<?= e(url('/admin/content/banners/' . $banner['id'] . '/delete')) ?>" data-confirm="Delete this banner?" style="margin-top:.5rem">
        <?= csrf_field() ?>
        <button class="btn btn--sm btn--ghost" type="submit">Delete banner</button>
      </form>
    </div>
  <?php endforeach; ?>

  <div class="admin-panel">
    <h2>New banner</h2>
    <form method="post" action="<?= e(url('/admin/content/banners')) ?>" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <div class="field-row field-row--2">
        <div class="field"><label class="field__label">Title</label><input type="text" name="title" required></div>
        <div class="field"><label class="field__label">Position key</label><input type="text" name="position" value="home_hero" required>
          <p class="field__hint"><code>home_hero</code> is the main hero, <code>home_cta</code> the call-to-action band.</p></div>
      </div>
      <div class="field"><label class="field__label">Subtitle</label><input type="text" name="subtitle"></div>
      <div class="field"><label class="field__label">Body</label><textarea name="body" rows="2" style="min-height:70px"></textarea></div>
      <div class="field"><label class="field__label">Image</label><input type="file" name="image_file" accept="image/*"></div>
      <label class="checkbox"><input type="checkbox" name="is_active" value="1" checked><span>Live on the site</span></label>
      <button class="btn btn--sm" type="submit">Create banner</button>
    </form>
  </div>

<?php elseif ($tab === 'pages'): ?>
  <div class="admin-note">Policy pages are draft copy prepared for the committee. Have them reviewed before the site goes public.</div>
  <?php foreach ($pages as $page): ?>
    <div class="admin-panel">
      <form method="post" action="<?= e(url('/admin/content/pages')) ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $page['id'] ?>">
        <div class="cluster cluster--between">
          <h2><?= e($page['title']) ?> <span class="badge">/<?= e($page['slug']) ?></span></h2>
          <a class="btn btn--sm btn--ghost" href="<?= e(url('/' . $page['slug'])) ?>" target="_blank">View</a>
        </div>
        <div class="field"><label class="field__label">Title</label><input type="text" name="title" value="<?= e($page['title']) ?>" required></div>
        <div class="field"><label class="field__label">Subtitle</label><input type="text" name="subtitle" value="<?= e((string) $page['subtitle']) ?>"></div>
        <div class="field">
          <label class="field__label">Body (HTML)</label>
          <textarea name="body_html" rows="14" style="font-family:ui-monospace,monospace;font-size:.82rem"><?= e((string) $page['body_html']) ?></textarea>
        </div>
        <div class="field-row field-row--2">
          <div class="field"><label class="field__label">Meta title</label><input type="text" name="meta_title" value="<?= e((string) $page['meta_title']) ?>"></div>
          <div class="field"><label class="field__label">Meta description</label><input type="text" name="meta_description" maxlength="255" value="<?= e((string) $page['meta_description']) ?>"></div>
        </div>
        <label class="checkbox"><input type="checkbox" name="is_published" value="1" <?= (int) $page['is_published'] === 1 ? 'checked' : '' ?>><span>Published</span></label>
        <button class="btn btn--sm" type="submit">Save page</button>
      </form>
    </div>
  <?php endforeach; ?>

<?php elseif ($tab === 'programme'): ?>
  <div class="admin-panel">
    <h2>Add a programme item</h2>
    <form method="post" action="<?= e(url('/admin/content/programme')) ?>" class="inline-form">
      <?= csrf_field() ?>
      <input type="date" name="day_date" required value="<?= e((string) config('event.starts_at') ? date('Y-m-d', (int) strtotime((string) config('event.starts_at'))) : '') ?>">
      <input type="time" name="start_time" required>
      <input type="time" name="end_time" placeholder="End">
      <input type="text" name="title" placeholder="Title" required style="min-width:220px">
      <input type="text" name="location" placeholder="Location">
      <input type="text" name="track" placeholder="Track">
      <label class="checkbox" style="margin:0"><input type="checkbox" name="is_highlight" value="1"><span>Highlight</span></label>
      <label class="checkbox" style="margin:0"><input type="checkbox" name="is_active" value="1" checked><span>Active</span></label>
      <button class="btn btn--sm" type="submit">Add</button>
    </form>
  </div>

  <?php
  $byDay = [];
  foreach ($programme as $item) { $byDay[$item['day_date']][] = $item; }
  ?>
  <?php foreach ($byDay as $date => $items): ?>
    <div class="admin-panel">
      <h2><?= e(za_date($date, 'l j F Y')) ?></h2>
      <?php foreach ($items as $item): ?>
        <div style="display:flex;gap:.4rem;align-items:center;flex-wrap:wrap;padding:.5rem 0;border-bottom:1px solid var(--line)">
          <form method="post" action="<?= e(url('/admin/content/programme')) ?>" class="inline-form" style="flex:1">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
            <input type="date" name="day_date" value="<?= e((string) $item['day_date']) ?>">
            <input type="time" name="start_time" value="<?= e(substr((string) $item['start_time'], 0, 5)) ?>">
            <input type="time" name="end_time" value="<?= $item['end_time'] ? e(substr((string) $item['end_time'], 0, 5)) : '' ?>">
            <input type="text" name="title" value="<?= e($item['title']) ?>" style="min-width:200px">
            <input type="text" name="location" value="<?= e((string) $item['location']) ?>" placeholder="Location">
            <input type="text" name="track" value="<?= e((string) $item['track']) ?>" placeholder="Track" style="width:120px">
            <label class="checkbox" style="margin:0"><input type="checkbox" name="is_highlight" value="1" <?= (int) $item['is_highlight'] === 1 ? 'checked' : '' ?>><span>★</span></label>
            <label class="checkbox" style="margin:0"><input type="checkbox" name="is_active" value="1" <?= (int) $item['is_active'] === 1 ? 'checked' : '' ?>><span>On</span></label>
            <button class="btn btn--sm" type="submit">Save</button>
          </form>
          <form method="post" action="<?= e(url('/admin/content/programme/' . $item['id'] . '/delete')) ?>" data-confirm="Delete this programme item?">
            <?= csrf_field() ?>
            <button class="btn btn--sm btn--ghost" type="submit">Delete</button>
          </form>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>

<?php elseif ($tab === 'faqs'): ?>
  <div class="admin-panel">
    <h2>Add a question</h2>
    <form method="post" action="<?= e(url('/admin/content/faqs')) ?>">
      <?= csrf_field() ?>
      <div class="field-row field-row--2">
        <div class="field"><label class="field__label">Category</label><input type="text" name="category" value="General"></div>
        <div class="field"><label class="field__label">Sort order</label><input type="number" name="sort_order" value="0"></div>
      </div>
      <div class="field"><label class="field__label">Question</label><input type="text" name="question" required></div>
      <div class="field"><label class="field__label">Answer (HTML allowed)</label><textarea name="answer" rows="4" required></textarea></div>
      <label class="checkbox"><input type="checkbox" name="is_active" value="1" checked><span>Show on the site</span></label>
      <button class="btn btn--sm" type="submit">Add question</button>
    </form>
  </div>

  <?php foreach ($faqs as $faq): ?>
    <div class="admin-panel">
      <form method="post" action="<?= e(url('/admin/content/faqs')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $faq['id'] ?>">
        <div class="field-row field-row--3">
          <div class="field"><label class="field__label">Category</label><input type="text" name="category" value="<?= e($faq['category']) ?>"></div>
          <div class="field"><label class="field__label">Sort order</label><input type="number" name="sort_order" value="<?= (int) $faq['sort_order'] ?>"></div>
          <div class="field" style="display:flex;align-items:flex-end">
            <label class="checkbox" style="margin:0"><input type="checkbox" name="is_active" value="1" <?= (int) $faq['is_active'] === 1 ? 'checked' : '' ?>><span>Visible</span></label>
          </div>
        </div>
        <div class="field"><label class="field__label">Question</label><input type="text" name="question" value="<?= e($faq['question']) ?>" required></div>
        <div class="field"><label class="field__label">Answer</label><textarea name="answer" rows="3" required><?= e($faq['answer']) ?></textarea></div>
        <button class="btn btn--sm" type="submit">Save</button>
      </form>
      <form method="post" action="<?= e(url('/admin/content/faqs/' . $faq['id'] . '/delete')) ?>" data-confirm="Delete this question?" style="margin-top:.5rem">
        <?= csrf_field() ?>
        <button class="btn btn--sm btn--ghost" type="submit">Delete</button>
      </form>
    </div>
  <?php endforeach; ?>

<?php elseif ($tab === 'events'): ?>
  <div class="admin-panel">
    <h2>Add an upcoming event</h2>
    <form method="post" action="<?= e(url('/admin/content/events')) ?>" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <div class="field"><label class="field__label">Title</label><input type="text" name="title" required></div>
      <div class="field-row field-row--2">
        <div class="field"><label class="field__label">Starts</label><input type="datetime-local" name="starts_at" required></div>
        <div class="field"><label class="field__label">Ends</label><input type="datetime-local" name="ends_at"></div>
      </div>
      <div class="field-row field-row--2">
        <div class="field"><label class="field__label">Location</label><input type="text" name="location"></div>
        <div class="field"><label class="field__label">Link</label><input type="text" name="link_url"></div>
      </div>
      <div class="field"><label class="field__label">Description</label><textarea name="description" rows="3"></textarea></div>
      <div class="field"><label class="field__label">Image</label><input type="file" name="image_file" accept="image/*"></div>
      <label class="checkbox"><input type="checkbox" name="is_active" value="1" checked><span>Show on the site</span></label>
      <button class="btn btn--sm" type="submit">Add event</button>
    </form>
  </div>

  <?php foreach ($events as $event): ?>
    <div class="admin-panel">
      <form method="post" action="<?= e(url('/admin/content/events')) ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $event['id'] ?>">
        <div class="field"><label class="field__label">Title</label><input type="text" name="title" value="<?= e($event['title']) ?>" required></div>
        <div class="field-row field-row--2">
          <div class="field"><label class="field__label">Starts</label><input type="datetime-local" name="starts_at" value="<?= e(date('Y-m-d\TH:i', (int) strtotime((string) $event['starts_at']))) ?>" required></div>
          <div class="field"><label class="field__label">Ends</label><input type="datetime-local" name="ends_at" value="<?= $event['ends_at'] ? e(date('Y-m-d\TH:i', (int) strtotime((string) $event['ends_at']))) : '' ?>"></div>
        </div>
        <div class="field-row field-row--2">
          <div class="field"><label class="field__label">Location</label><input type="text" name="location" value="<?= e((string) $event['location']) ?>"></div>
          <div class="field"><label class="field__label">Link</label><input type="text" name="link_url" value="<?= e((string) $event['link_url']) ?>"></div>
        </div>
        <div class="field"><label class="field__label">Description</label><textarea name="description" rows="3"><?= e((string) $event['description']) ?></textarea></div>
        <label class="checkbox"><input type="checkbox" name="is_active" value="1" <?= (int) $event['is_active'] === 1 ? 'checked' : '' ?>><span>Visible</span></label>
        <button class="btn btn--sm" type="submit">Save</button>
      </form>
      <form method="post" action="<?= e(url('/admin/content/events/' . $event['id'] . '/delete')) ?>" data-confirm="Delete this event?" style="margin-top:.5rem">
        <?= csrf_field() ?>
        <button class="btn btn--sm btn--ghost" type="submit">Delete</button>
      </form>
    </div>
  <?php endforeach; ?>
<?php endif; ?>
<?php View::stop(); ?>

<?php
use App\Core\View;
View::layout('layouts.public');
View::start('content');
?>
<?php View::include('partials.page-hero', [
  'eyebrow' => 'Gallery',
  'title'   => 'The estate and its spaces',
  'lede'    => 'Every image below is original illustration created for this preview build. They will be replaced with licensed venue photography before launch.',
  'image'   => 'img/venue/gardens.jpg',
  'crumbs'  => ['Gallery' => null],
]); ?>

<section class="section">
  <div class="container">
    <?php if ($categories !== []): ?>
      <div class="cluster" style="margin-bottom:1.5rem">
        <a class="btn btn--sm <?= $active === '' ? '' : 'btn--ghost' ?>" href="<?= e(url('/gallery')) ?>">Everything</a>
        <?php foreach ($categories as $category): ?>
          <a class="btn btn--sm <?= $active === $category ? '' : 'btn--ghost' ?>" href="<?= e(url('/gallery?category=' . urlencode((string) $category))) ?>"><?= e(ucfirst((string) $category)) ?></a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ($images === []): ?>
      <div class="empty-state"><div class="empty-state__icon">🖼</div><h3>No images yet</h3><p>The committee will add venue photography here.</p></div>
    <?php else: ?>
      <div class="gallery-grid">
        <?php foreach ($images as $image): ?>
          <figure class="gallery-item" data-lightbox="<?= e(uploaded($image['file_path'])) ?>"
                  data-lightbox-alt="<?= e($image['alt_text']) ?>" data-lightbox-caption="<?= e($image['title'] ?? '') ?>">
            <?= picture($image['file_path'], $image['alt_text']) ?>
            <?php if ($image['title']): ?><figcaption><?= e($image['title']) ?></figcaption><?php endif; ?>
          </figure>
        <?php endforeach; ?>
      </div>
      <p class="muted" style="margin-top:1.5rem;font-size:var(--step--1)">Image credits and permissions are tracked in <code>/docs/image-source-log.md</code>.</p>
    <?php endif; ?>
  </div>
</section>

<div class="lightbox" id="lightbox" role="dialog" aria-modal="true" aria-label="Image viewer">
  <button class="lightbox__close" type="button" aria-label="Close">&times;</button>
  <div><img src="" alt=""><p class="lightbox__caption"></p></div>
</div>
<?php View::stop(); ?>

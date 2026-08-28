<?php
/**
 * @var string      $title
 * @var string|null $eyebrow
 * @var string|null $lede
 * @var string|null $image
 * @var array|null  $crumbs   label => path
 * @var string|null $actions  raw HTML
 */
$image = $image ?? 'img/backgrounds/section-mist.jpg';
?>
<section class="page-hero">
  <div class="page-hero__media"><?= picture($image, '', ['loading' => 'eager', 'class' => '']) ?></div>
  <div class="container">
    <?php if (!empty($crumbs)): ?>
      <nav class="breadcrumbs" aria-label="Breadcrumb">
        <a href="<?= e(url('/')) ?>">Home</a>
        <?php foreach ($crumbs as $label => $path): ?>
          <span>/</span>
          <?php if ($path === null): ?><span style="color:var(--gold)"><?= e($label) ?></span>
          <?php else: ?><a href="<?= e(url($path)) ?>"><?= e($label) ?></a><?php endif; ?>
        <?php endforeach; ?>
      </nav>
    <?php endif; ?>

    <?php if (!empty($eyebrow)): ?><span class="eyebrow" style="color:var(--gold)"><?= e($eyebrow) ?></span><?php endif; ?>
    <h1><?= e($title) ?></h1>
    <?php if (!empty($lede)): ?><p><?= e($lede) ?></p><?php endif; ?>
    <?php if (!empty($actions)): ?><div class="cluster" style="margin-top:1.5rem"><?= $actions ?></div><?php endif; ?>
  </div>
</section>

<?php
use App\Core\View;
View::layout('layouts.public');
View::start('content');
?>
<?php View::include('partials.page-hero', [
  'eyebrow' => 'Programme',
  'title'   => 'The weekend, hour by hour',
  'lede'    => 'Times are indicative and may be adjusted by the committee closer to the weekend.',
  'image'   => 'img/backgrounds/section-mist.jpg',
  'crumbs'  => ['Programme' => null],
]); ?>

<section class="section">
  <div class="container grid grid--sidebar">
    <div>
      <?php if ($days === []): ?>
        <div class="empty-state">
          <div class="empty-state__icon">🗓</div>
          <h3>The programme is being finalised</h3>
          <p>The committee is putting the weekend together. Check back soon, or register now and we will email you when it is published.</p>
        </div>
      <?php endif; ?>

      <?php foreach ($days as $date => $items): ?>
        <div class="programme-day">
          <div class="programme-day__head">
            <h2 style="margin:0"><?= e(za_date($date, 'l j F')) ?></h2>
            <span class="muted"><?= e(za_date($date, 'Y')) ?></span>
          </div>
          <?php foreach ($items as $item): ?>
            <div class="programme-item<?= (int) $item['is_highlight'] === 1 ? ' is-highlight' : '' ?>">
              <div class="programme-item__time">
                <?= e(substr((string) $item['start_time'], 0, 5)) ?>
                <?php if ($item['end_time']): ?><br><span class="muted" style="font-size:var(--step--1);font-weight:400"><?= e(substr((string) $item['end_time'], 0, 5)) ?></span><?php endif; ?>
              </div>
              <div>
                <p class="programme-item__title"><?= e($item['title']) ?><?php if ((int) $item['is_highlight'] === 1): ?> <span class="badge badge--gold">Highlight</span><?php endif; ?></p>
                <?php if ($item['description']): ?><p class="programme-item__desc"><?= e($item['description']) ?></p><?php endif; ?>
                <?php if ($item['location'] || $item['track']): ?>
                  <p class="programme-item__desc">
                    <?php if ($item['location']): ?>📍 <?= e($item['location']) ?><?php endif; ?>
                    <?php if ($item['track']): ?> &middot; <?= e($item['track']) ?><?php endif; ?>
                  </p>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <aside class="summary">
      <h3 style="font-size:var(--step-1)">Be there</h3>
      <p class="muted" style="font-size:var(--step--1)">Registration opens at 14:00 on Friday. Accommodation check-in from 16:00.</p>
      <a class="btn btn--block" href="<?= e(url('/shop/registration')) ?>">Register</a>
      <a class="btn btn--ghost btn--block" style="margin-top:.6rem" href="<?= e(url('/accommodation')) ?>">Book a bed</a>
      <a class="btn btn--ghost btn--block" style="margin-top:.6rem" href="<?= e(url('/transport')) ?>">Book transport</a>
      <hr>
      <p class="muted" style="font-size:var(--step--1)">Meals shown in the programme are included where stated. Everything else is available from the farm kitchen.</p>
    </aside>
  </div>
</section>
<?php View::stop(); ?>

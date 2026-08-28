<?php
use App\Core\View;
use App\Services\ProductService;
View::layout('layouts.public');
View::start('content');
?>
<?php View::include('partials.page-hero', [
  'eyebrow' => 'Registration',
  'title'   => 'Register for the convention',
  'lede'    => 'Registration covers every meeting, workshop and speaker session, plus the Saturday night celebration.',
  'image'   => 'img/backgrounds/cta-sunset.jpg',
  'crumbs'  => ['Shop' => '/shop', 'Registration' => null],
]); ?>

<section class="section">
  <div class="container">
    <?php if (!$isOpen): ?>
      <div class="alert alert--warning"><div><div class="alert__title">Registration is closed</div>
      <p>The committee has paused registration. Please check back, or ask us on WhatsApp.</p></div></div>
    <?php endif; ?>

    <div class="section-head"><div class="rule"></div><span class="eyebrow">The whole weekend</span><h2>Full registration</h2></div>
    <div class="grid grid--2">
      <?php foreach ($weekend as $product): ?>
        <div class="card reveal">
          <div class="card__body">
            <div class="cluster">
              <?php if (ProductService::isOnSale($product)): ?><span class="badge badge--warning">On sale</span><?php endif; ?>
              <?php if (str_contains(strtolower($product['name']), 'early')): ?><span class="badge badge--gold">Limited allocation</span><?php endif; ?>
            </div>
            <h3 class="card__title" style="font-size:var(--step-2)"><?= e($product['name']) ?></h3>
            <p class="card__text" style="font-size:var(--step-0)"><?= e($product['short_description']) ?></p>
            <ul style="font-size:var(--step--1);color:var(--ink-soft)">
              <li>All main meetings, workshops and speaker sessions</li>
              <li>Saturday night celebration</li>
              <li>Convention badge and welcome pack</li>
              <li>Accommodation and transport booked separately</li>
            </ul>
            <div class="card__foot">
              <span class="card__price"><?= e(money(ProductService::priceFor($product))) ?><small>per person</small></span>
              <a class="btn" href="<?= e(url('/shop/' . $product['slug'])) ?>">Register</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="section-head" style="margin-top:3rem"><div class="rule"></div><span class="eyebrow">Coming for a day</span><h2>Day passes</h2></div>
    <div class="grid grid--3">
      <?php foreach ($days as $product): ?>
        <div class="card reveal"><div class="card__body">
          <h3 class="card__title"><?= e($product['name']) ?></h3>
          <p class="card__text"><?= e($product['short_description']) ?></p>
          <div class="card__foot">
            <span class="card__price"><?= e(money(ProductService::priceFor($product))) ?></span>
            <a class="btn btn--sm" href="<?= e(url('/shop/' . $product['slug'])) ?>">Choose</a>
          </div>
        </div></div>
      <?php endforeach; ?>
    </div>

    <div class="alert alert--info" style="margin-top:2rem">
      <div><div class="alert__title">Day visitors</div>
      <p>Driving yourself? Book the free <a href="<?= e(url('/transport/day-visitor-parking-pass')) ?>">day visitor parking pass</a> so the venue can plan the parking field.</p></div>
    </div>
  </div>
</section>
<?php View::stop(); ?>

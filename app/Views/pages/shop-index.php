<?php
use App\Core\View;
use App\Services\ProductService;
View::layout('layouts.public');
View::start('content');

$card = static function (array $product): void { ?>
  <a class="card card--link reveal" href="<?= e(url('/shop/' . $product['slug'])) ?>">
    <div class="card__media"><?= picture($product['image'] ?? 'img/backgrounds/placeholder.jpg', $product['name']) ?></div>
    <div class="card__body">
      <div class="cluster">
        <?php if (ProductService::isOnSale($product)): ?><span class="badge badge--warning">On sale</span><?php endif; ?>
        <?php if ((int) $product['track_stock'] === 1 && (int) $product['stock'] <= 0): ?><span class="badge badge--error">Sold out</span>
        <?php elseif ((int) $product['track_stock'] === 1 && (int) $product['stock'] <= (int) $product['low_stock_threshold']): ?><span class="badge badge--warning">Only <?= (int) $product['stock'] ?> left</span><?php endif; ?>
      </div>
      <h3 class="card__title" style="font-size:var(--step-1)"><?= e($product['name']) ?></h3>
      <p class="card__text"><?= e($product['short_description']) ?></p>
      <div class="card__foot">
        <span class="card__price">
          <?php if ((int) $product['allows_custom_amount'] === 1): ?>Any amount
          <?php else: ?>
            <?php if (ProductService::isOnSale($product)): ?><s><?= e(money((int) $product['price_cents'])) ?></s><?php endif; ?>
            <?= e(money(ProductService::priceFor($product))) ?>
          <?php endif; ?>
        </span>
        <span class="link-arrow">View <span>&rarr;</span></span>
      </div>
    </div>
  </a>
<?php };
?>
<?php View::include('partials.page-hero', [
  'eyebrow' => 'Shop',
  'title'   => 'Everything for the weekend, in one cart',
  'lede'    => 'Registration, beds, shuttle seats, merchandise and donations all check out together through PayFast.',
  'image'   => 'img/backgrounds/cta-sunset.jpg',
  'crumbs'  => ['Shop' => null],
]); ?>

<section class="section">
  <div class="container">
    <div class="cluster" style="margin-bottom:2rem">
      <a class="btn btn--sm" href="<?= e(url('/shop/registration')) ?>">Registration</a>
      <a class="btn btn--sm btn--ghost" href="<?= e(url('/accommodation')) ?>">Accommodation</a>
      <a class="btn btn--sm btn--ghost" href="<?= e(url('/shop/merchandise')) ?>">Merchandise</a>
      <a class="btn btn--sm btn--ghost" href="<?= e(url('/transport')) ?>">Transport</a>
      <a class="btn btn--sm btn--ghost" href="<?= e(url('/donations')) ?>">Donations</a>
    </div>

    <div class="section-head"><div class="rule"></div><span class="eyebrow">Registration &amp; day passes</span><h2>Start here</h2></div>
    <div class="grid grid--3"><?php foreach ($registration as $product) { $card($product); } ?></div>

    <div class="section-head" style="margin-top:3rem"><div class="rule"></div><span class="eyebrow">Merchandise</span><h2>Take the weekend home</h2></div>
    <div class="grid grid--4"><?php foreach ($merch as $product) { $card($product); } ?></div>

    <div class="section-head" style="margin-top:3rem"><div class="rule"></div><span class="eyebrow">Donations</span><h2>Support the convention</h2></div>
    <div class="grid grid--3"><?php foreach ($donations as $product) { $card($product); } ?></div>
  </div>
</section>

<section class="section section--sunk">
  <div class="container grid grid--2">
    <div>
      <div class="rule"></div>
      <h2>How checkout works</h2>
      <ol>
        <li>Add whatever you need to the cart — beds, registration, shuttle seats, merch.</li>
        <li>Sign in or create an account so your bookings are kept together.</li>
        <li>Confirm attendee, guest and passenger details.</li>
        <li>Pay once through PayFast — card, Instant EFT, SnapScan and more.</li>
        <li>We confirm everything by email the moment PayFast tells us the payment went through.</li>
      </ol>
    </div>
    <div>
      <div class="rule"></div>
      <h2>Good to know</h2>
      <ul>
        <li>Beds in your cart are held for 15 minutes so nobody else can take them.</li>
        <li>Merchandise is collected at the registration desk during the weekend.</li>
        <li>Nothing is confirmed until PayFast verifies the payment — landing back on our site is not enough on its own.</li>
        <li>See the <a href="<?= e(url('/refund-policy')) ?>">refund policy</a> before you book.</li>
      </ul>
    </div>
  </div>
</section>
<?php View::stop(); ?>

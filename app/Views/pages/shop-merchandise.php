<?php
use App\Core\View;
use App\Services\ProductService;
View::layout('layouts.public');
View::start('content');
?>
<?php View::include('partials.page-hero', [
  'eyebrow' => 'Merchandise',
  'title'   => 'Convention merchandise',
  'lede'    => $pickupNote ?: 'Collected at the registration desk during the convention weekend.',
  'image'   => 'img/merch/hoodie.jpg',
  'crumbs'  => ['Shop' => '/shop', 'Merchandise' => null],
]); ?>

<section class="section">
  <div class="container">
    <div class="grid grid--4">
      <?php foreach ($products as $product):
        $soldOut = (int) $product['track_stock'] === 1 && (int) $product['stock'] <= 0; ?>
        <a class="card card--link reveal" href="<?= e(url('/shop/' . $product['slug'])) ?>">
          <div class="card__media"><?= picture($product['image'] ?? 'img/backgrounds/placeholder.jpg', $product['name']) ?></div>
          <div class="card__body">
            <?php if ($soldOut): ?><span class="badge badge--error">Sold out</span>
            <?php elseif ((int) $product['stock'] <= (int) $product['low_stock_threshold']): ?><span class="badge badge--warning">Only <?= (int) $product['stock'] ?> left</span><?php endif; ?>
            <h3 class="card__title" style="font-size:var(--step-0)"><?= e($product['name']) ?></h3>
            <p class="card__text"><?= e($product['short_description']) ?></p>
            <div class="card__foot">
              <span class="card__price"><?= e(money(ProductService::priceFor($product))) ?></span>
              <span class="link-arrow">View <span>&rarr;</span></span>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>

    <div class="alert alert--info" style="margin-top:2rem">
      <div><div class="alert__title">Collection</div><p><?= e($pickupNote) ?> See the <a href="<?= e(url('/merchandise-terms')) ?>">merchandise terms</a> for sizes, exchanges and returns.</p></div>
    </div>
  </div>
</section>
<?php View::stop(); ?>

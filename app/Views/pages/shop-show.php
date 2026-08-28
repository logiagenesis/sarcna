<?php
use App\Core\View;
use App\Services\ProductService;
View::layout('layouts.public');
View::start('content');

$price     = ProductService::priceFor($product);
$onSale    = ProductService::isOnSale($product);
$tracking  = (int) $product['track_stock'] === 1;
$soldOut   = $tracking && (int) $product['stock'] <= 0;
$isDonation = (int) $product['allows_custom_amount'] === 1;
$isRegistration = in_array($product['type'], ['registration', 'day_pass'], true);
?>
<section class="section">
  <div class="container">
    <nav class="breadcrumbs" style="color:var(--ink-muted);margin-bottom:1.5rem">
      <a href="<?= e(url('/')) ?>" style="color:var(--vineyard)">Home</a><span>/</span>
      <a href="<?= e(url('/shop')) ?>" style="color:var(--vineyard)">Shop</a><span>/</span>
      <span><?= e($product['name']) ?></span>
    </nav>

    <div class="grid grid--sidebar">
      <div>
        <div style="border-radius:var(--radius-l);overflow:hidden;box-shadow:var(--shadow-m)">
          <?= picture($product['image'] ?? 'img/backgrounds/placeholder.jpg', $product['name'], ['loading' => 'eager']) ?>
        </div>

        <?php if (count($images) > 1): ?>
          <div class="gallery-grid" style="margin-top:1rem;grid-template-columns:repeat(auto-fill,minmax(120px,1fr))">
            <?php foreach ($images as $image): ?>
              <figure class="gallery-item" style="aspect-ratio:1"
                      data-lightbox="<?= e(uploaded($image['file_path'])) ?>" data-lightbox-alt="<?= e($image['alt_text']) ?>">
                <?= picture($image['file_path'], $image['alt_text']) ?>
              </figure>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <div class="prose" style="margin-top:2rem"><?= $product['description'] ?></div>

        <?php if ($product['type'] === 'merchandise'): ?>
          <div class="alert alert--info" style="margin-top:1.5rem">
            <div><div class="alert__title">Collection at the convention</div>
            <p>Merchandise is collected at the registration desk during the weekend. Bring your order reference or check-in code.</p></div>
          </div>
        <?php endif; ?>
      </div>

      <aside class="summary">
        <div class="cluster">
          <?php if ($onSale): ?><span class="badge badge--warning">On sale</span><?php endif; ?>
          <?php if ($soldOut): ?><span class="badge badge--error">Sold out</span>
          <?php elseif ($tracking && (int) $product['stock'] <= (int) $product['low_stock_threshold']): ?>
            <span class="badge badge--warning">Only <?= (int) $product['stock'] ?> left</span>
          <?php endif; ?>
        </div>

        <h1 style="font-size:var(--step-2);margin:.5rem 0 .25rem"><?= e($product['name']) ?></h1>
        <p class="muted" style="font-size:var(--step--1)"><?= e($product['short_description']) ?></p>

        <p class="card__price" style="font-size:var(--step-3);margin:.75rem 0">
          <?php if ($isDonation): ?>Any amount
          <?php else: ?>
            <?php if ($onSale): ?><s><?= e(money((int) $product['price_cents'])) ?></s> <?php endif; ?><?= e(money($price)) ?>
          <?php endif; ?>
        </p>

        <?php if ($soldOut): ?>
          <div class="alert alert--error" style="margin:0"><div><p>This item has sold out. Contact the committee if you would like to go on the waiting list.</p></div></div>
          <a class="btn btn--ghost btn--block" style="margin-top:1rem" href="<?= e(url('/contact')) ?>">Contact us</a>
        <?php else: ?>
          <form method="post" action="<?= e(url('/shop/' . $product['slug'] . '/add')) ?>" data-once
                data-track-submit="add_to_cart" data-track-params='{"item_category":"<?= e($product['type']) ?>"}'
                <?= $isDonation ? 'data-donation-form' : '' ?>>
            <?= csrf_field() ?>

            <?php if ($variants !== []): ?>
              <div class="field">
                <label class="field__label" for="variant_id">Size &amp; colour</label>
                <select id="variant_id" name="variant_id" required>
                  <option value="">Choose an option…</option>
                  <?php foreach ($variants as $variant): ?>
                    <option value="<?= (int) $variant['id'] ?>" <?= (int) $variant['stock'] <= 0 ? 'disabled' : '' ?>>
                      <?= e(trim(implode(' / ', array_filter([$variant['size'], $variant['colour']])))) ?>
                      <?= (int) $variant['price_delta_cents'] !== 0 ? ' (+' . money((int) $variant['price_delta_cents']) . ')' : '' ?>
                      <?= (int) $variant['stock'] <= 0 ? ' — sold out' : '' ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            <?php endif; ?>

            <?php if ($isDonation): ?>
              <div class="field">
                <span class="field__label">Choose an amount</span>
                <div class="cluster">
                  <?php foreach ([10000, 25000, 50000, 100000] as $preset): ?>
                    <button type="button" class="btn btn--sm btn--ghost" data-amount="<?= number_format($preset / 100, 2, '.', '') ?>"><?= e(money($preset)) ?></button>
                  <?php endforeach; ?>
                </div>
              </div>
              <div class="field">
                <label class="field__label" for="amount">Amount (Rand)</label>
                <input type="number" id="amount" name="amount" min="<?= number_format(((int) $product['min_amount_cents']) / 100, 2, '.', '') ?>" step="1" value="250" required>
                <p class="field__hint">Minimum <?= e(money((int) $product['min_amount_cents'])) ?>.</p>
              </div>
              <label class="checkbox"><input type="checkbox" name="is_anonymous" value="1"><span>Keep my donation anonymous</span></label>
              <div class="field">
                <label class="field__label" for="message">Message (optional)</label>
                <textarea id="message" name="message" rows="2" style="min-height:70px"></textarea>
              </div>
            <?php else: ?>
              <div class="field">
                <label class="field__label" for="quantity">Quantity</label>
                <div class="qty">
                  <button type="button" data-step="down" aria-label="Decrease quantity">&minus;</button>
                  <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?= (int) $product['max_per_order'] ?>">
                  <button type="button" data-step="up" aria-label="Increase quantity">+</button>
                </div>
              </div>
            <?php endif; ?>

            <?php if ($isRegistration): ?>
              <fieldset style="background:var(--surface-sunk);padding:1rem;margin-bottom:1rem">
                <legend style="font-size:var(--step--1)">Attendee</legend>
                <div class="field">
                  <label class="field__label" for="attendee_name">Full name</label>
                  <input type="text" id="attendee_name" name="attendee_name" value="<?= e(auth() ? trim(auth()['first_name'] . ' ' . auth()['last_name']) : '') ?>">
                </div>
                <div class="field">
                  <label class="field__label" for="attendee_email">Email</label>
                  <input type="email" id="attendee_email" name="attendee_email" value="<?= e(auth()['email'] ?? '') ?>">
                </div>
                <div class="field" style="margin-bottom:0">
                  <label class="field__label" for="dietary_notes">Dietary notes</label>
                  <input type="text" id="dietary_notes" name="dietary_notes" placeholder="Vegetarian, halaal, allergies…">
                </div>
              </fieldset>
              <p class="muted" style="font-size:var(--step--1)">Registering for more than one person? Add one registration per person and edit the names at checkout.</p>
            <?php endif; ?>

            <button class="btn btn--block btn--lg" type="submit" data-busy-label="Adding…">Add to cart</button>
          </form>
        <?php endif; ?>

        <hr>
        <p class="muted" style="font-size:var(--step--1)">Secure payment through PayFast. See our <a href="<?= e(url('/terms')) ?>">terms</a> and <a href="<?= e(url('/refund-policy')) ?>">refund policy</a>.</p>
      </aside>
    </div>
  </div>
</section>

<?php if ($related !== []): ?>
<section class="section section--sunk">
  <div class="container">
    <div class="section-head"><div class="rule"></div><h2>You might also want</h2></div>
    <div class="grid grid--3">
      <?php foreach ($related as $item): ?>
        <a class="card card--link" href="<?= e(url('/shop/' . $item['slug'])) ?>">
          <div class="card__media"><?= picture($item['image'] ?? 'img/backgrounds/placeholder.jpg', $item['name']) ?></div>
          <div class="card__body">
            <h3 class="card__title" style="font-size:var(--step-0)"><?= e($item['name']) ?></h3>
            <div class="card__foot">
              <span class="card__price"><?= e(money(ProductService::priceFor($item))) ?></span>
              <span class="link-arrow">View <span>&rarr;</span></span>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<div class="lightbox" id="lightbox" role="dialog" aria-modal="true" aria-label="Image viewer">
  <button class="lightbox__close" type="button" aria-label="Close">&times;</button>
  <div><img src="" alt=""><p class="lightbox__caption"></p></div>
</div>
<?php View::stop(); ?>

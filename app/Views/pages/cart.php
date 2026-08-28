<?php
use App\Core\View;
use App\Services\CartService;
View::layout('layouts.public');
View::start('content');
$items = $totals['items'];
?>
<section class="section">
  <div class="container">
    <ol class="steps">
      <li class="is-current">Cart</li>
      <li>Details</li>
      <li>Payment</li>
      <li>Confirmed</li>
    </ol>

    <?php if ($items === []): ?>
      <div class="empty-state">
        <div class="empty-state__icon">🛒</div>
        <h3>Your cart is empty</h3>
        <p>Start with registration, then add a bed and a shuttle seat — everything checks out together.</p>
        <div class="cluster cluster--center" style="margin-top:1.5rem">
          <a class="btn" href="<?= e(url('/shop/registration')) ?>">Register</a>
          <a class="btn btn--ghost" href="<?= e(url('/accommodation')) ?>">Book a bed</a>
          <a class="btn btn--ghost" href="<?= e(url('/transport')) ?>">Book transport</a>
        </div>
      </div>
    <?php else: ?>

      <div class="grid grid--sidebar">
        <div>
          <h1 style="font-size:var(--step-3)">Your cart</h1>

          <?php if ($holdExpires !== null): ?>
            <div class="hold-timer" data-hold-expires="<?= e($holdExpires) ?>">
              <span>⏱</span>
              <span>Your beds are held for <strong data-hold-clock>--:--</strong>. Complete checkout before the timer runs out or they go back on sale.</span>
            </div>
          <?php endif; ?>

          <form method="post" action="<?= e(url('/cart/update')) ?>">
            <?= csrf_field() ?>
            <?php foreach ($items as $item):
              $meta = $item['meta']; ?>
              <div class="cart-line">
                <div class="cart-line__thumb">
                  <?= picture($meta['image'] ?? ($item['product_image'] ?? match ($item['item_type']) {
                        'accommodation' => 'img/rooms/retreat-twin-room.jpg',
                        'transport'     => 'img/transport/airport-shuttle.jpg',
                        'donation'      => 'img/venue/boma-firepit.jpg',
                        default         => 'img/backgrounds/placeholder.jpg',
                      }), $item['description']) ?>
                </div>

                <div>
                  <p class="cart-line__title"><?= e($item['description']) ?></p>
                  <p class="cart-line__meta">
                    <span class="badge" style="font-size:.62rem"><?= e(ucfirst($item['item_type'])) ?></span>
                    <?php if ($item['item_type'] === 'accommodation'): ?>
                      <?= (int) ($meta['bed_count'] ?? 1) ?> bed<?= (int) ($meta['bed_count'] ?? 1) === 1 ? '' : 's' ?>
                      <?php if (!empty($meta['unit_name'])): ?> &middot; <?= e($meta['unit_name']) ?><?php endif; ?>
                      <?php if (!empty($meta['is_private_unit'])): ?> &middot; <strong>private unit</strong><?php endif; ?>
                      <?php if (!empty($meta['roommate_request'])): ?><br>Roommate request: <?= e($meta['roommate_request']) ?><?php endif; ?>
                    <?php elseif ($item['item_type'] === 'transport'): ?>
                      <?= e($meta['pickup_point'] ?? '') ?> &rarr; <?= e($meta['dropoff_point'] ?? '') ?>
                      <?php if (!empty($meta['passenger_name'])): ?><br>Lead passenger: <?= e($meta['passenger_name']) ?><?php endif; ?>
                    <?php elseif ($item['item_type'] === 'registration' && !empty($meta['attendee_name'])): ?>
                      Attendee: <?= e($meta['attendee_name']) ?>
                    <?php elseif ($item['item_type'] === 'donation' && !empty($meta['is_anonymous'])): ?>
                      Anonymous donation
                    <?php endif; ?>
                  </p>

                  <div class="cluster" style="margin-top:.5rem">
                    <?php if (in_array($item['item_type'], ['merchandise', 'registration', 'transport'], true)): ?>
                      <div class="qty">
                        <button type="button" data-step="down" aria-label="Decrease">&minus;</button>
                        <input type="number" name="quantities[<?= (int) $item['id'] ?>]" value="<?= (int) $item['quantity'] ?>" min="1" max="20" aria-label="Quantity for <?= e($item['description']) ?>">
                        <button type="button" data-step="up" aria-label="Increase">+</button>
                      </div>
                    <?php else: ?>
                      <span class="muted" style="font-size:var(--step--1)">Qty <?= (int) $item['quantity'] ?></span>
                    <?php endif; ?>
                  </div>
                </div>

                <div style="text-align:right">
                  <p class="cart-line__price"><?= e(money((int) $item['total_cents'])) ?></p>
                  <?php if ((int) $item['quantity'] > 1): ?>
                    <p class="cart-line__meta"><?= e(money((int) $item['unit_price_cents'])) ?> each</p>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>

            <div class="cluster cluster--between" style="margin-top:1.25rem">
              <button class="btn btn--ghost btn--sm" type="submit">Update quantities</button>
              <a class="link-arrow" href="<?= e(url('/shop')) ?>">Keep shopping <span>&rarr;</span></a>
            </div>
          </form>

          <div class="cluster" style="margin-top:1rem">
            <?php foreach ($items as $item): ?>
              <form method="post" action="<?= e(url('/cart/remove')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                <button class="btn btn--sm btn--ghost" type="submit">Remove <?= e(excerpt($item['description'], 34)) ?></button>
              </form>
            <?php endforeach; ?>
          </div>

          <form method="post" action="<?= e(url('/cart/clear')) ?>" style="margin-top:1rem">
            <?= csrf_field() ?>
            <button class="btn btn--sm btn--ghost" type="submit">Empty the cart</button>
          </form>
        </div>

        <aside class="summary">
          <h2 style="font-size:var(--step-2)">Summary</h2>

          <?php foreach ($totals['by_type'] as $type => $amount): ?>
            <div class="summary__row"><span><?= e(ucfirst($type)) ?></span><span><?= e(money($amount)) ?></span></div>
          <?php endforeach; ?>

          <div class="summary__row"><span>Subtotal</span><span><?= e(money($totals['subtotal_cents'])) ?></span></div>

          <?php if ($totals['coupon'] !== null): ?>
            <div class="summary__row" style="color:var(--success)">
              <span>Coupon <?= e($totals['coupon']['code']) ?></span>
              <span>&minus;<?= e(money($totals['discount_cents'])) ?></span>
            </div>
            <form method="post" action="<?= e(url('/cart/coupon/remove')) ?>">
              <?= csrf_field() ?>
              <button class="btn btn--sm btn--ghost" type="submit">Remove coupon</button>
            </form>
          <?php else: ?>
            <form method="post" action="<?= e(url('/cart/coupon')) ?>" style="margin:.75rem 0">
              <?= csrf_field() ?>
              <label class="field__label" for="code">Coupon code</label>
              <div class="cluster" style="flex-wrap:nowrap">
                <input type="text" id="code" name="code" placeholder="Enter a code" style="text-transform:uppercase">
                <button class="btn btn--sm btn--ghost" type="submit">Apply</button>
              </div>
            </form>
          <?php endif; ?>

          <div class="summary__row summary__row--total">
            <span>Total</span><span><?= e(money($totals['total_cents'])) ?></span>
          </div>

          <a class="btn btn--block btn--lg" href="<?= e(url('/checkout')) ?>"
             data-track="begin_checkout" data-track-params='{"value":<?= (int) $totals['total_cents'] / 100 ?>,"currency":"ZAR"}'>
            Checkout
          </a>
          <p class="muted" style="font-size:var(--step--1);margin-top:.75rem">
            Payment is handled by PayFast. Nothing is confirmed until PayFast tells us the payment succeeded.
          </p>
        </aside>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php View::stop(); ?>

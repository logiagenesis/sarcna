<?php
use App\Core\View;
View::layout('layouts.public');
View::start('content');
?>
<?php View::include('partials.page-hero', [
  'eyebrow' => 'Donations',
  'title'   => 'Keep the doors open',
  'lede'    => 'Every convention is self-supporting through the contributions of the fellowship. Your donation keeps registration affordable and puts newcomers in the room.',
  'image'   => 'img/venue/boma-firepit.jpg',
  'crumbs'  => ['Donations' => null],
]); ?>

<section class="section">
  <div class="container grid grid--sidebar">
    <div>
      <?php if (!$isOpen): ?>
        <div class="alert alert--warning"><div><p>Donations are closed at the moment.</p></div></div>
      <?php endif; ?>

      <div class="rule"></div>
      <h2>Where the money goes</h2>
      <p>The convention is run entirely by volunteers. Registration covers the venue, the programme and the vehicles; donations cover the gap — and they pay for the people who could not otherwise be here.</p>
      <ul>
        <li><strong>Seventh Tradition.</strong> The traditional contribution, at whatever amount you can give.</li>
        <li><strong>Sponsor a newcomer.</strong> R850 covers one full weekend registration. The committee allocates sponsorships confidentially.</li>
        <li><strong>Sponsor a bed.</strong> Any amount towards accommodation for someone who could not otherwise stay over.</li>
      </ul>
      <p class="muted" style="font-size:var(--step--1)">Donations are not refundable. See the <a href="<?= e(url('/refund-policy')) ?>">refund policy</a>.</p>

      <div class="grid grid--3" style="margin-top:2rem">
        <?php foreach ($products as $product): ?>
          <div class="card reveal">
            <div class="card__media"><?= picture($product['image'] ?? 'img/backgrounds/placeholder.jpg', $product['name']) ?></div>
            <div class="card__body">
              <h3 class="card__title" style="font-size:var(--step-1)"><?= e($product['name']) ?></h3>
              <p class="card__text"><?= e($product['short_description']) ?></p>
              <div class="card__foot">
                <span class="card__price"><?= (int) $product['allows_custom_amount'] === 1 ? 'Any amount' : e(money((int) $product['price_cents'])) ?></span>
                <a class="btn btn--sm" href="#donate" onclick="document.getElementById('product_id').value='<?= (int) $product['id'] ?>';document.getElementById('product_id').dispatchEvent(new Event('change'));">Choose</a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <aside class="summary" id="donate">
      <h2 style="font-size:var(--step-2);margin-bottom:.25rem">Make a donation</h2>
      <?php if ($count > 0): ?>
        <p class="muted" style="font-size:var(--step--1)"><?= (int) $count ?> donation<?= $count === 1 ? '' : 's' ?> received so far.</p>
      <?php endif; ?>

      <?php if ($isOpen): ?>
      <form method="post" action="<?= e(url('/donations')) ?>" data-donation-form data-once
            data-track-submit="add_to_cart" data-track-params='{"item_category":"donation"}'>
        <?= csrf_field() ?>

        <div class="field">
          <label class="field__label" for="product_id">What would you like to support?</label>
          <select id="product_id" name="product_id" required>
            <?php foreach ($products as $product): ?>
              <option value="<?= (int) $product['id'] ?>" data-fixed="<?= (int) $product['allows_custom_amount'] === 1 ? '0' : (int) $product['price_cents'] ?>">
                <?= e($product['name']) ?><?= (int) $product['allows_custom_amount'] === 1 ? '' : ' — ' . money((int) $product['price_cents']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field">
          <span class="field__label">Quick amounts</span>
          <div class="cluster">
            <?php foreach ([10000, 25000, 50000, 85000] as $preset): ?>
              <button type="button" class="btn btn--sm btn--ghost" data-amount="<?= number_format($preset / 100, 2, '.', '') ?>"><?= e(money($preset)) ?></button>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="field">
          <label class="field__label" for="amount">Amount (Rand)</label>
          <input type="number" id="amount" name="amount" min="20" step="1" value="250" required>
        </div>

        <label class="checkbox"><input type="checkbox" name="is_anonymous" value="1"><span>Keep my donation anonymous</span></label>

        <div class="field">
          <label class="field__label" for="message">Message (optional)</label>
          <textarea id="message" name="message" rows="2" style="min-height:70px"></textarea>
        </div>

        <button class="btn btn--block btn--lg" type="submit">Add to cart</button>
        <p class="muted" style="font-size:var(--step--1);margin-top:.75rem">You can donate on its own or alongside registration in the same payment.</p>
      </form>
      <?php endif; ?>
    </aside>
  </div>
</section>
<?php View::stop(); ?>

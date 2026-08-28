<?php
use App\Core\View;
use App\Services\ProductService;
View::layout('layouts.public');
View::start('content');
$event = config('event');
$byDay = [];
foreach ($programme as $item) { $byDay[$item['day_date']][] = $item; }
?>
<?php View::include('partials.page-hero', [
  'eyebrow' => 'The convention',
  'title'   => 'Three days in the Cape Winelands',
  'lede'    => $event['supporting'],
  'image'   => 'img/venue/auditorium.jpg',
  'crumbs'  => ['The Convention' => null],
  'actions' => '<a class="btn btn--gold" href="' . e(url('/shop/registration')) . '">Register now</a><a class="btn btn--outline-light" href="' . e(url('/programme')) . '">Full programme</a>',
]); ?>

<section class="section">
  <div class="container">
    <div class="section-head section-head--center">
      <div class="rule" style="margin-inline:auto"></div>
      <span class="eyebrow">What your registration covers</span>
      <h2>Everything in the programme</h2>
    </div>

    <div class="grid grid--3">
      <?php
      $includes = [
        ['Main meetings', 'The opening meeting on Friday night, the Saturday main meeting and the Sunday closing meeting.'],
        ['Workshops', 'Concurrent workshops on sponsorship, the steps and service, running through Saturday morning.'],
        ['Speaker sessions', 'Shared experience from speakers across the region on Saturday afternoon.'],
        ['Saturday night celebration', 'Music, dancing and the countdown — the high point of the weekend.'],
        ['Fellowship spaces', 'The fire pit lawn, the farmhouse lounge and a dining terrace that never really closes.'],
        ['Service forum', 'An open forum on convention and regional service, and how to get involved next year.'],
      ];
      foreach ($includes as [$title, $text]): ?>
        <div class="card reveal"><div class="card__body">
          <h3 class="card__title"><?= e($title) ?></h3>
          <p class="card__text"><?= e($text) ?></p>
        </div></div>
      <?php endforeach; ?>
    </div>

    <div class="alert alert--info" style="margin-top:2rem">
      <div>
        <div class="alert__title">Booked separately</div>
        <p>Accommodation, transport and merchandise are priced and booked on their own pages, so you only pay for what you need. Everything ends up in one cart and one payment.</p>
      </div>
    </div>
  </div>
</section>

<section class="section section--sunk">
  <div class="container">
    <div class="section-head">
      <div class="rule"></div>
      <span class="eyebrow">Registration options</span>
      <h2>Pick the weekend or a day</h2>
    </div>
    <div class="grid grid--3">
      <?php foreach ($products as $product): ?>
        <div class="card reveal"><div class="card__body">
          <span class="badge <?= $product['type'] === 'day_pass' ? '' : 'badge--gold' ?>"><?= $product['type'] === 'day_pass' ? 'Day pass' : 'Full weekend' ?></span>
          <h3 class="card__title"><?= e($product['name']) ?></h3>
          <p class="card__text"><?= e($product['short_description']) ?></p>
          <div class="card__foot">
            <span class="card__price"><?= e(money(ProductService::priceFor($product))) ?></span>
            <a class="btn btn--sm" href="<?= e(url('/shop/' . $product['slug'])) ?>">Choose</a>
          </div>
        </div></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head">
      <div class="rule"></div>
      <span class="eyebrow">The shape of the weekend</span>
      <h2>Friday to Sunday, at a glance</h2>
    </div>

    <?php foreach ($byDay as $date => $items): ?>
      <div class="programme-day">
        <div class="programme-day__head">
          <h3 style="margin:0"><?= e(za_date($date, 'l j F Y')) ?></h3>
          <span class="muted"><?= count($items) ?> sessions</span>
        </div>
        <?php foreach ($items as $item): ?>
          <div class="programme-item<?= (int) $item['is_highlight'] === 1 ? ' is-highlight' : '' ?>">
            <div class="programme-item__time"><?= e(substr((string) $item['start_time'], 0, 5)) ?></div>
            <div>
              <p class="programme-item__title"><?= e($item['title']) ?></p>
              <?php if ($item['description'] || $item['location']): ?>
                <p class="programme-item__desc"><?= e($item['description']) ?><?= $item['location'] ? ' &middot; ' . e($item['location']) : '' ?></p>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  </div>
</section>
<?php View::stop(); ?>

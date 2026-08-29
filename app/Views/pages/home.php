<?php
use App\Core\View;
use App\Services\AccommodationService;
use App\Services\ProductService;
use App\Services\SettingsService;

/** @var array|null $hero @var array|null $cta @var array $roomTypes @var array $occupancy */
$event = config('event');
View::layout('layouts.public');
View::start('content');
?>

<!-- ============================================================ hero -->
<section class="hero">
  <div class="hero__media">
    <?php
    // Art-directed hero: a portrait crop on phones, the wide scene above that.
    $heroImage = $hero['image'] ?? 'img/backgrounds/hero-winelands.jpg';
    $heroAlt   = $hero['image_alt'] ?? 'Illustration of a Cape Winelands sunrise over vineyards and mountains';
    $heroWide  = str_starts_with($heroImage, '/') ? uploaded($heroImage) : asset($heroImage);
    $usesDefault = !isset($hero['image']) || $hero['image'] === '' || str_contains((string) $heroImage, 'hero-winelands');
    ?>
    <picture>
      <?php if ($usesDefault): ?>
        <source media="(max-width: 40rem)" type="image/webp" srcset="<?= e(asset('img/backgrounds/hero-mobile.webp')) ?>">
        <source media="(max-width: 40rem)" srcset="<?= e(asset('img/backgrounds/hero-mobile.jpg')) ?>">
      <?php endif; ?>
      <source type="image/webp" srcset="<?= e(preg_replace('/\.(jpe?g|png)(\?.*)?$/i', '.webp$2', $heroWide)) ?>">
      <img src="<?= e($heroWide) ?>" alt="<?= e($heroAlt) ?>" loading="eager" fetchpriority="high" decoding="async">
    </picture>
  </div>
  <div class="container hero__inner">
    <span class="eyebrow hero__eyebrow"><?= e($event['dates_label']) ?> &middot; Cape Winelands, South Africa</span>
    <h1><?= e($hero['title'] ?? $event['title']) ?></h1>
    <p class="hero__slogan"><?= e($hero['subtitle'] ?? $event['slogan']) ?></p>
    <p class="hero__lede"><?= e($hero['body'] ?? $event['supporting']) ?></p>

    <div class="hero__meta">
      <div>
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 11h18"/></svg>
        <strong><?= e($event['dates_label']) ?></strong>
      </div>
      <div>
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 21s7-6.2 7-11a7 7 0 1 0-14 0c0 4.8 7 11 7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg>
        <strong><?= e($event['venue_name']) ?></strong>
      </div>
    </div>

    <div class="hero__actions">
      <a class="btn btn--gold btn--lg" href="<?= e(url($hero['cta_url'] ?? '/shop/registration')) ?>"
         data-track="begin_checkout" data-track-params='{"item_category":"registration","source":"hero"}'>
        <?= e($hero['cta_label'] ?? 'Register now') ?>
      </a>
      <a class="btn btn--outline-light btn--lg" href="<?= e(url($hero['secondary_url'] ?? '/accommodation')) ?>">
        <?= e($hero['secondary_label'] ?? 'Book accommodation') ?>
      </a>
      <a class="btn btn--outline-light btn--lg" href="<?= e(url('/transport')) ?>">Book transport</a>
    </div>

    <?php if (SettingsService::bool('show_countdown', true)): ?>
      <?php View::include('partials.countdown'); ?>
    <?php endif; ?>
  </div>
  <a class="hero-scroll" href="#what-to-expect" aria-label="Scroll to the convention overview">Explore</a>
</section>

<!-- ================================================== feature blocks -->
<section class="section" id="what-to-expect">
  <div class="container">
    <div class="section-head section-head--center">
      <div class="rule" style="margin-inline:auto"></div>
      <span class="eyebrow">One weekend, everything in one place</span>
      <h2>Register, book a bed, reserve a seat</h2>
      <p class="muted">Everything you need for the convention weekend is on this site — and it is all in one cart, so you check out once.</p>
    </div>

    <div class="grid grid--4">
      <?php
      $features = [
          ['The Convention', 'Three days of meetings, workshops, speakers and fellowship.', '/convention', 'M4 6h16M4 12h16M4 18h10'],
          ['The Venue', 'The Retreat at Boschendal — eighteen cottages, an auditorium and a boma.', '/venue', 'M3 21h18M5 21V9l7-5 7 5v12M10 21v-6h4v6'],
          ['Accommodation', 'En-suite twin rooms, sold one bed at a time.', '/accommodation', 'M3 18v-6a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v6M3 18h18M6 10V7h12v3'],
          ['Transport', 'Airport, city and local shuttles, seat by seat.', '/transport', 'M4 17h16M6 17V8h12v9M8 8V6h8v2M7 20v-3M17 20v-3'],
          ['Merchandise', 'Tees, hoodies and keepsakes from the weekend.', '/shop/merchandise', 'M6 7h12l1 13H5L6 7ZM9 7V5a3 3 0 0 1 6 0v2'],
          ['Service', 'Put your hand up — every convention runs on service.', '/service', 'M12 21s-7-4.5-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 11c0 5.5-7 10-7 10Z'],
          ['Donations', 'Seventh Tradition, and sponsor a newcomer.', '/donations', 'M12 3v18M7 8h7a3 3 0 0 1 0 6H8'],
          ['Programme', 'The full weekend timetable, hour by hour.', '/programme', 'M8 2v4M16 2v4M3 10h18M5 6h14v15H5z'],
      ];
      foreach ($features as $index => [$title, $text, $href, $path]): ?>
        <a class="feature-card reveal" href="<?= e(url($href)) ?>" style="transition-delay: <?= $index * 45 ?>ms">
          <span class="feature-card__icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="<?= $path ?>"/></svg>
          </span>
          <h3><?= e($title) ?></h3>
          <p><?= e($text) ?></p>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- =========================================================== venue -->
<section class="section section--sunk">
  <div class="container">
    <div class="media-split">
      <div class="media-split__media reveal">
        <?= picture('img/venue/boschendal-overview.jpg', 'Illustration of the Boschendal estate at sunrise with vineyard rows running to the mountains') ?>
      </div>
      <div class="reveal">
        <div class="rule"></div>
        <span class="eyebrow">The venue</span>
        <h2>Boschendal Retreat Cottages &amp; Conference Venue</h2>
        <p>An 1&nbsp;800-hectare farm in the Dwars River valley, an hour from Cape Town, between the Simonsberg and the Groot Drakenstein. Title deeds from 1685; vineyards, orchards and mountains on every horizon.</p>
        <p>We are at <strong>the Retreat</strong> — eighteen cottages in a secluded corner of the estate with their own auditorium, screening room, dining lounge, boma, natural swimming pool and fynbos gardens. Everything the weekend needs, in one quiet place, with nowhere else to be.</p>
        <div class="stat-row" style="margin:1.5rem 0">
          <div class="stat"><div class="stat__value">1&nbsp;hr</div><div class="stat__label">From Cape Town</div></div>
          <div class="stat"><div class="stat__value">18</div><div class="stat__label">Cottages, 72 beds</div></div>
          <div class="stat"><div class="stat__value">1685</div><div class="stat__label">Title deeds date from</div></div>
        </div>
        <div class="cluster">
          <a class="btn" href="<?= e(url('/venue')) ?>">Explore the venue</a>
          <a class="btn btn--ghost" href="<?= e(url('/gallery')) ?>">See the gallery</a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- =================================================== accommodation -->
<?php if (SettingsService::bool('accommodation_enabled', true) && $roomTypes !== []): ?>
<section class="section">
  <div class="container">
    <div class="cluster cluster--between" style="align-items:flex-end;margin-bottom:2rem">
      <div class="section-head" style="margin-bottom:0">
        <div class="rule"></div>
        <span class="eyebrow">Accommodation</span>
        <h2>Book a bed, not a whole room</h2>
        <p class="muted">Beds are sold one at a time. Take one bed in a shared cottage and the other beds stay on sale for other attendees — or book the whole unit privately.</p>
      </div>
      <a class="link-arrow" href="<?= e(url('/accommodation')) ?>">All room types <span>&rarr;</span></a>
    </div>

    <div class="grid grid--3">
      <?php foreach ($roomTypes as $roomType):
        $availability = AccommodationService::availability((int) $roomType['id']);
        $totalFree    = array_sum($availability);
        ?>
        <a class="card card--link reveal" href="<?= e(url('/accommodation/' . $roomType['slug'])) ?>">
          <div class="card__media"><?= picture($roomType['hero_image'] ?? 'img/backgrounds/placeholder.jpg', $roomType['name']) ?></div>
          <div class="card__body">
            <div class="cluster">
              <span class="badge"><?= (int) $roomType['beds_per_unit'] ?> beds per unit</span>
              <?php if ((int) $roomType['is_accessible'] === 1): ?><span class="badge badge--success">Step-free</span><?php endif; ?>
            </div>
            <h3 class="card__title"><?= e($roomType['name']) ?></h3>
            <p class="card__text"><?= e($roomType['summary']) ?></p>
            <div class="availability" style="margin-top:.5rem">
              <?php foreach ($availability as $night => $free): ?>
                <span class="availability__night <?= $free === 0 ? 'is-out' : ($free < 6 ? 'is-low' : '') ?>">
                  <strong><?= e(za_date($night, 'D j M')) ?></strong>
                  <?= $free === 0 ? 'Fully booked' : $free . ' bed' . ($free === 1 ? '' : 's') . ' free' ?>
                </span>
              <?php endforeach; ?>
            </div>
            <div class="card__foot">
              <span class="card__price">from <?= e(money((int) $roomType['bed_rate_cents'])) ?><small>per bed, per night</small></span>
              <span class="link-arrow">View <span>&rarr;</span></span>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ==================================================== registration -->
<?php if ($registration !== []): ?>
<section class="section section--dark">
  <div class="container">
    <div class="section-head section-head--center">
      <div class="rule" style="margin-inline:auto"></div>
      <span class="eyebrow">Registration</span>
      <h2>Secure your place</h2>
      <p>Registration covers every meeting, workshop and speaker session, plus the Saturday night celebration.</p>
    </div>

    <div class="grid grid--3">
      <?php foreach ($registration as $product): ?>
        <div class="card reveal">
          <div class="card__body">
            <div class="cluster">
              <span class="badge <?= $product['type'] === 'day_pass' ? '' : 'badge--gold' ?>"><?= $product['type'] === 'day_pass' ? 'Day pass' : 'Full weekend' ?></span>
              <?php if (ProductService::isOnSale($product)): ?><span class="badge badge--warning">On sale</span><?php endif; ?>
            </div>
            <h3 class="card__title"><?= e($product['name']) ?></h3>
            <p class="card__text"><?= e($product['short_description']) ?></p>
            <div class="card__foot">
              <span class="card__price"><?= e(money(ProductService::priceFor($product))) ?></span>
              <a class="btn btn--sm" href="<?= e(url('/shop/' . $product['slug'])) ?>">Register</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="cluster cluster--center" style="margin-top:2rem">
      <a class="btn btn--light" href="<?= e(url('/shop/registration')) ?>">See all registration options</a>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ======================================================= programme -->
<?php if ($programme !== []): ?>
<section class="section">
  <div class="container grid grid--sidebar">
    <div>
      <div class="rule"></div>
      <span class="eyebrow">The weekend</span>
      <h2>Programme highlights</h2>
      <p class="muted">Three days built around meetings, service, rest and each other. The full hour-by-hour programme is on the programme page.</p>

      <div style="margin-top:1.5rem">
        <?php foreach ($programme as $item): ?>
          <div class="programme-item">
            <div class="programme-item__time"><?= e(substr((string) $item['start_time'], 0, 5)) ?></div>
            <div>
              <p class="programme-item__title"><?= e($item['title']) ?></p>
              <p class="programme-item__desc"><?= e(za_date((string) $item['day_date'], 'l j F')) ?><?= $item['location'] ? ' &middot; ' . e($item['location']) : '' ?></p>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <a class="btn btn--ghost" style="margin-top:1.5rem" href="<?= e(url('/programme')) ?>">See the full programme</a>
    </div>

    <aside class="summary">
      <h3 style="font-size:var(--step-1)">Getting there</h3>
      <p class="muted" style="font-size:var(--step--1)">Shuttles run from Cape Town International, the city centre, Stellenbosch and around the Winelands.</p>
      <?php foreach ($routes as $route): ?>
        <div class="summary__row">
          <span><?= e($route['name']) ?></span>
          <strong><?= (int) $route['price_cents'] === 0 ? 'Free' : e(money((int) $route['price_cents'])) ?></strong>
        </div>
      <?php endforeach; ?>
      <a class="btn btn--block" style="margin-top:1rem" href="<?= e(url('/transport')) ?>">Book transport</a>
      <p class="muted" style="font-size:var(--step--1);margin-top:.75rem">Seats are limited per departure and sold per passenger.</p>
    </aside>
  </div>
</section>
<?php endif; ?>

<!-- ============================================================ merch -->
<?php if ($merch !== []): ?>
<section class="section section--sunk">
  <div class="container">
    <div class="cluster cluster--between" style="align-items:flex-end;margin-bottom:2rem">
      <div class="section-head" style="margin-bottom:0">
        <div class="rule"></div>
        <span class="eyebrow">Merchandise</span>
        <h2>Take the weekend home</h2>
      </div>
      <a class="link-arrow" href="<?= e(url('/shop/merchandise')) ?>">Shop everything <span>&rarr;</span></a>
    </div>

    <div class="grid grid--4">
      <?php foreach ($merch as $product): ?>
        <a class="card card--link reveal" href="<?= e(url('/shop/' . $product['slug'])) ?>">
          <div class="card__media"><?= picture($product['image'] ?? 'img/backgrounds/placeholder.jpg', $product['name']) ?></div>
          <div class="card__body">
            <h3 class="card__title" style="font-size:var(--step-0)"><?= e($product['name']) ?></h3>
            <div class="card__foot">
              <span class="card__price"><?= e(money(ProductService::priceFor($product))) ?></span>
              <span class="link-arrow">View <span>&rarr;</span></span>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ========================================================== gallery -->
<?php if ($gallery !== []): ?>
<section class="section">
  <div class="container">
    <div class="section-head section-head--center">
      <div class="rule" style="margin-inline:auto"></div>
      <span class="eyebrow">The estate</span>
      <h2>Where we will be</h2>
      <?php if (\App\Services\PhotoService::stillShowingIllustrations()): ?>
        <p class="muted">Some imagery below is original illustration for this preview build rather than photographs of the venue.</p>
      <?php endif; ?>
    </div>
    <div class="gallery-grid">
      <?php foreach ($gallery as $image): ?>
        <figure class="gallery-item" data-lightbox="<?= e(uploaded($image['file_path'])) ?>"
                data-lightbox-alt="<?= e($image['alt_text']) ?>" data-lightbox-caption="<?= e($image['title']) ?>">
          <?= picture($image['file_path'], $image['alt_text']) ?>
          <figcaption><?= e($image['title']) ?></figcaption>
        </figure>
      <?php endforeach; ?>
    </div>
    <div class="cluster cluster--center" style="margin-top:1.5rem">
      <a class="btn btn--ghost" href="<?= e(url('/gallery')) ?>">Open the full gallery</a>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ============================================================= CTA -->
<?php if ($cta !== null): ?>
<section class="page-hero" style="padding-block:var(--space-2xl)">
  <div class="page-hero__media"><?= picture($cta['image'] ?? 'img/backgrounds/cta-sunset.jpg', $cta['image_alt'] ?? '') ?></div>
  <div class="container text-center">
    <span class="eyebrow" style="color:var(--gold)"><?= e($cta['title']) ?></span>
    <h2 style="color:var(--cream)"><?= e($cta['subtitle']) ?></h2>
    <p style="margin-inline:auto"><?= e($cta['body']) ?></p>
    <div class="cluster cluster--center" style="margin-top:1.75rem">
      <a class="btn btn--gold btn--lg" href="<?= e(url($cta['cta_url'] ?? '/donations')) ?>"><?= e($cta['cta_label'] ?? 'Donate') ?></a>
      <a class="btn btn--outline-light btn--lg" href="<?= e(url('/service')) ?>">Do service</a>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ============================================================= FAQ -->
<?php if ($faqs !== []): ?>
<section class="section">
  <div class="container grid grid--sidebar">
    <div>
      <div class="rule"></div>
      <span class="eyebrow">Questions</span>
      <h2>The things people ask us most</h2>
      <div class="accordion" style="margin-top:1.5rem">
        <?php foreach ($faqs as $index => $faq): ?>
          <details<?= $index === 0 ? ' open' : '' ?>>
            <summary><?= e($faq['question']) ?></summary>
            <div class="accordion__body"><?= $faq['answer'] ?></div>
          </details>
        <?php endforeach; ?>
      </div>
      <a class="btn btn--ghost" style="margin-top:1.5rem" href="<?= e(url('/faq')) ?>">All questions</a>
    </div>

    <aside class="summary">
      <h3 style="font-size:var(--step-1)">Still stuck?</h3>
      <p class="muted" style="font-size:var(--step--1)">WhatsApp is the fastest way to reach the committee, and someone answers most days.</p>
      <a class="btn btn--block" href="<?= e(whatsapp_link()) ?>" target="_blank" rel="noopener" data-track="whatsapp_click">WhatsApp the committee</a>
      <a class="btn btn--ghost btn--block" style="margin-top:.6rem" href="<?= e(url('/contact')) ?>">Send a message</a>

      <?php if ($events !== []): ?>
        <h3 style="font-size:var(--step-1);margin-top:1.75rem">Coming up</h3>
        <?php foreach ($events as $upcoming): ?>
          <div class="summary__row" style="align-items:flex-start">
            <div>
              <strong><?= e($upcoming['title']) ?></strong><br>
              <span class="muted"><?= e(za_date((string) $upcoming['starts_at'], 'j M Y')) ?><?= $upcoming['location'] ? ' &middot; ' . e($upcoming['location']) : '' ?></span>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </aside>
  </div>
</section>
<?php endif; ?>

<div class="lightbox" id="lightbox" role="dialog" aria-modal="true" aria-label="Image viewer">
  <button class="lightbox__close" type="button" aria-label="Close">&times;</button>
  <div>
    <img src="" alt="">
    <p class="lightbox__caption"></p>
  </div>
</div>

<?php View::stop(); ?>

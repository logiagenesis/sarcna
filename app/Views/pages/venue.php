<?php
use App\Core\View;
View::layout('layouts.public');
View::start('content');
$event = config('event');
?>
<?php View::include('partials.page-hero', [
  'eyebrow' => 'The venue',
  'title'   => 'Boschendal Retreat Cottages & Conference Venue',
  'lede'    => 'A working Cape farm estate between Franschhoek and Stellenbosch — cottages among the orchards, a barn big enough to hold us all, and mountains in every direction.',
  'image'   => 'img/venue/boschendal-overview.jpg',
  'crumbs'  => ['The Venue' => null],
  'actions' => '<a class="btn btn--gold" href="' . e(url('/accommodation')) . '">Book accommodation</a><a class="btn btn--outline-light" href="' . e(url('/gallery')) . '">See the gallery</a>',
]); ?>

<section class="section">
  <div class="container">
    <div class="media-split">
      <div class="reveal">
        <div class="rule"></div>
        <span class="eyebrow">Where you will be</span>
        <h2>An hour from the city, and a world away from it</h2>
        <p>The estate sits in the Dwars River valley in the Cape Winelands, roughly an hour from Cape Town International Airport and forty minutes from Stellenbosch. The drive in runs between oaks and orchards, and the last stretch is gravel.</p>
        <p>Everything the convention uses is on one property: the conference barn, the dining terrace, the gardens, the fire pit lawn and most of the accommodation. Once you arrive you can leave the car parked until Sunday.</p>
        <div class="stat-row" style="margin-top:1.5rem">
          <div class="stat"><div class="stat__value">1 hr</div><div class="stat__label">From Cape Town International</div></div>
          <div class="stat"><div class="stat__value">40 min</div><div class="stat__label">From Stellenbosch</div></div>
          <div class="stat"><div class="stat__value">15 min</div><div class="stat__label">From Franschhoek village</div></div>
        </div>
      </div>
      <div class="media-split__media reveal">
        <?= picture('img/venue/arrival-drive.jpg', 'Illustration of the oak-lined arrival drive onto the estate') ?>
      </div>
    </div>
  </div>
</section>

<section class="section section--sunk">
  <div class="container">
    <div class="section-head section-head--center">
      <div class="rule" style="margin-inline:auto"></div>
      <span class="eyebrow">The spaces</span>
      <h2>What is on the estate</h2>
      <p class="muted">Everything below is within a few minutes&rsquo; walk of everything else.</p>
    </div>

    <div class="grid grid--3">
      <?php
      $spaces = [
        ['Retreat cottages', 'img/venue/retreat-cottages.jpg', 'Illustration of the retreat cottages among the orchards',
         'Small cottages set among the orchards, each with a stoep and a view. Two, four and eight-bed layouts, all with heating — August nights in the valley are cold.'],
        ['The conference barn', 'img/venue/conference-barn.jpg', 'Illustration of the conference barn',
         'A restored barn that seats the whole convention for the main meetings, with a stage, a good sound system and doors that open onto the lawn.'],
        ['Workshop rooms', 'img/conference/workshop-room.jpg', 'Illustration of a workshop room',
         'Three smaller rooms off the barn for concurrent workshops, sharing sessions and the service forum.'],
        ['The dining terrace', 'img/venue/farm-kitchen.jpg', 'Illustration of the dining terrace at dusk',
         'Farm-style tables under cover, where meals are served and where most of the weekend&rsquo;s real conversations happen.'],
        ['Gardens and lawns', 'img/venue/gardens.jpg', 'Illustration of the estate gardens in morning mist',
         'Lawns, herb gardens and shaded benches, with enough corners to find one to yourself between sessions.'],
        ['The fire pit', 'img/venue/fireside.jpg', 'Illustration of the fire pit lawn after sunset',
         'A fire that is lit at sunset and runs as late as people keep talking. Bring a jacket.'],
      ];
      foreach ($spaces as [$title, $image, $alt, $text]): ?>
        <div class="card reveal">
          <div class="card__media"><?= picture($image, $alt) ?></div>
          <div class="card__body">
            <h3 class="card__title"><?= e($title) ?></h3>
            <p class="card__text"><?= $text ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="media-split media-split--reverse">
      <div class="media-split__media reveal">
        <?= picture('img/venue/mountain-walk.jpg', 'Illustration of the mountain path behind the estate') ?>
      </div>
      <div class="reveal">
        <div class="rule"></div>
        <span class="eyebrow">Between sessions</span>
        <h2>Sober-friendly things to do</h2>
        <ul>
          <li><strong>The sunrise walk.</strong> A guided walk up the farm road on Saturday at 07:00, back in time for breakfast.</li>
          <li><strong>Farm roads and mountain paths.</strong> Marked routes from 20 minutes to two hours, all starting at the main gate.</li>
          <li><strong>Morning meditation</strong> in the gardens on Sunday.</li>
          <li><strong>The fellowship lounge</strong> in the farmhouse — quieter than the barn, open late.</li>
          <li><strong>Coffee.</strong> The dining terrace runs coffee and tea from early until late.</li>
          <li><strong>Card and board games</strong> in the workshop rooms on Saturday afternoon.</li>
          <li><strong>Stargazing</strong> from the fire pit lawn — there is very little light pollution in the valley.</li>
        </ul>
        <div class="alert alert--warning" style="margin-top:1.25rem">
          <div>
            <div class="alert__title">A note on the estate&rsquo;s wine history</div>
            <p>Boschendal is a historic Cape farm with a long wine-making history. That history is part of the place, but it is not part of our weekend: no alcohol is served at any convention event, and wine tasting is not offered as a convention activity in any form.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section section--sunk">
  <div class="container">
    <div class="grid grid--2">
      <div>
        <div class="rule"></div>
        <h2>Arrival and parking</h2>
        <ul>
          <li><strong>Registration desk</strong> opens at 14:00 on Friday 27 August, in the old barn beside the main parking field.</li>
          <li><strong>Accommodation check-in</strong> from 16:00. Early-arrival guests on Thursday can check in from 15:00.</li>
          <li><strong>Parking</strong> is free and on the estate. Cottage guests can park at their cottage; day visitors use the parking field and should book the free parking pass so the venue can plan.</li>
          <li><strong>The last stretch of road is gravel</strong> and well maintained — any car will manage it, but take it slowly.</li>
          <li><strong>Checkout</strong> is 10:00 on Sunday. Luggage can be left at the registration desk until the afternoon shuttles.</li>
        </ul>
        <a class="btn btn--ghost" href="https://www.google.com/maps/search/?api=1&amp;query=Boschendal+Franschhoek" target="_blank" rel="noopener">Open directions in Google Maps</a>
      </div>

      <div>
        <div class="rule"></div>
        <h2>Accessibility</h2>
        <ul>
          <li><strong>Step-free cottages.</strong> Our Accessible Twin Cottages have step-free access, widened doorways, grab rails and a roll-in shower, and sit closest to the barn on level paved paths.</li>
          <li><strong>The conference barn</strong> is step-free with an accessible bathroom, and there is reserved seating at the front and at the back.</li>
          <li><strong>Paths</strong> between the barn, the terrace and the nearest cottages are paved; paths to the outlying cottages and the walks are gravel and uneven.</li>
          <li><strong>Accessible parking bays</strong> are next to the barn.</li>
          <li><strong>Hearing.</strong> The main meetings run through a PA system. Tell us if you need a seat near the front.</li>
          <li><strong>Tell us what you need</strong> in the notes when you book, and the accommodation team will be in touch before the weekend.</li>
        </ul>
        <a class="btn btn--ghost" href="<?= e(url('/contact')) ?>">Ask about access</a>
      </div>
    </div>
  </div>
</section>

<?php if ($gallery !== []): ?>
<section class="section">
  <div class="container">
    <div class="section-head section-head--center">
      <div class="rule" style="margin-inline:auto"></div>
      <span class="eyebrow">Gallery</span>
      <h2>The estate</h2>
    </div>
    <div class="gallery-grid">
      <?php foreach ($gallery as $image): ?>
        <figure class="gallery-item" data-lightbox="<?= e(uploaded($image['file_path'])) ?>"
                data-lightbox-alt="<?= e($image['alt_text']) ?>" data-lightbox-caption="<?= e($image['title'] ?? '') ?>">
          <?= picture($image['file_path'], $image['alt_text']) ?>
          <figcaption><?= e($image['title'] ?? '') ?></figcaption>
        </figure>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section section--dark">
  <div class="container text-center">
    <div class="rule" style="margin-inline:auto"></div>
    <h2>Stay on the estate</h2>
    <p style="margin-inline:auto">Beds from <?= e(money(70000)) ?> a night, sold one at a time. Book a single bed or take a whole cottage.</p>
    <div class="cluster cluster--center" style="margin-top:1.5rem">
      <a class="btn btn--gold btn--lg" href="<?= e(url('/accommodation')) ?>">See room types</a>
      <a class="btn btn--outline-light btn--lg" href="<?= e(url('/venue/history')) ?>">Venue history</a>
    </div>
  </div>
</section>

<div class="lightbox" id="lightbox" role="dialog" aria-modal="true" aria-label="Image viewer">
  <button class="lightbox__close" type="button" aria-label="Close">&times;</button>
  <div><img src="" alt=""><p class="lightbox__caption"></p></div>
</div>
<?php View::stop(); ?>

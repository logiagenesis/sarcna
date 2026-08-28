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
        <p>Boschendal is an 1&nbsp;800-hectare farm in the Dwars River valley, between Franschhoek and Stellenbosch, with the Simonsberg on one side and the Groot Drakenstein on the other. The name is <em>Bos-en-dal</em> — wood and valley — and once you are in it, that is exactly what it is.</p>
        <p>We are not using the whole farm. The convention takes place at <strong>the Retreat</strong>, a self-contained cluster of eighteen cottages tucked into a secluded corner of the estate, with its own auditorium, dining lounge, boma and natural swimming pool. Once you arrive you can leave the car parked until Sunday.</p>
        <div class="stat-row" style="margin-top:1.5rem">
          <div class="stat"><div class="stat__value">1 hr</div><div class="stat__label">From Cape Town</div></div>
          <div class="stat"><div class="stat__value">18</div><div class="stat__label">Cottages at the Retreat</div></div>
          <div class="stat"><div class="stat__value">72</div><div class="stat__label">Beds on the estate</div></div>
          <div class="stat"><div class="stat__value">1685</div><div class="stat__label">Title deeds date from</div></div>
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
        ['The cottages', 'img/venue/retreat-cottages.jpg', 'Illustration of the Retreat cottages across the lawns',
         'Eighteen self-catering cottages across manicured lawns. Each holds two twin bedrooms, and <strong>every bedroom has its own front door and its own en-suite bathroom</strong> — you never walk through anyone else&rsquo;s room to reach yours.'],
        ['Inside a cottage', 'img/venue/cottage-interior.jpg', 'Illustration standing in for a cottage living room and fireplace',
         'The two bedrooms share a living room with a kitchen and an indoor fireplace, and a private patio with a braai. WiFi and satellite TV throughout. August nights in the valley are cold; the fireplaces are not decorative.'],
        ['The auditorium', 'img/venue/auditorium.jpg', 'Illustration standing in for the Retreat auditorium',
         'The Retreat&rsquo;s own auditorium holds the whole convention for the main meetings, with proper sound and staging. Everything else is a few minutes&rsquo; walk from its door.'],
        ['The screening room', 'img/venue/screening-room.jpg', 'Illustration standing in for the private screening room',
         'A private screening room — unusual for a conference venue, and ours for the weekend. We are running a recovery film and discussion track in it on Saturday afternoon.'],
        ['Break-off rooms', 'img/venue/dining-lounge.jpg', 'Illustration standing in for the lounge and dining area',
         'Boardroom-style break-off rooms for the concurrent workshops and the service forum, plus a lounge and dining area where most of the weekend&rsquo;s real conversations will happen.'],
        ['The boma', 'img/venue/boma-firepit.jpg', 'Illustration of the communal boma after sunset',
         'A communal fire that is lit at sunset and runs as late as people keep talking. Bring a jacket.'],
        ['The natural pool', 'img/venue/natural-pool.jpg', 'Illustration standing in for the natural swimming pool',
         'A natural swimming pool in the gardens. In late August it is bracing. People swim in it anyway.'],
        ['Trails and horses', 'img/venue/walking-trails.jpg', 'Illustration of the walking trails behind the Retreat',
         'Mountainside walking trails start at the Retreat gate, and the farm runs scenic horse rides. The Saturday sunrise walk is on the programme.'],
        ['Fynbos gardens', 'img/venue/fynbos-gardens.jpg', 'Illustration of the fynbos gardens in morning mist',
         'Indigenous fynbos gardens with enough quiet corners to find one to yourself between sessions. Sunday morning meditation happens here.'],
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
        <?= picture('img/venue/walking-trails.jpg', 'Illustration of the mountain path behind the estate') ?>
      </div>
      <div class="reveal">
        <div class="rule"></div>
        <span class="eyebrow">Between sessions</span>
        <h2>Sober-friendly things to do</h2>
        <ul>
          <li><strong>The sunrise trail walk.</strong> Guided, Saturday at 07:00, on the mountainside trails that start at the Retreat gate. Back in time for breakfast.</li>
          <li><strong>Horse riding.</strong> The farm runs scenic rides across the estate. Book at reception — spaces are limited and go quickly.</li>
          <li><strong>The natural swimming pool.</strong> Bracing in August. That has never stopped anyone.</li>
          <li><strong>The screening room.</strong> A recovery film and discussion track runs on Saturday afternoon, and the room is open at other times.</li>
          <li><strong>The boma.</strong> A fire from sunset until people stop talking, which on a Saturday is late.</li>
          <li><strong>Fynbos gardens.</strong> Indigenous planting, benches, and quiet. Sunday meditation is here.</li>
          <li><strong>Coffee.</strong> The lounge and dining area runs coffee and tea from early until late.</li>
          <li><strong>Stargazing.</strong> There is very little light pollution in the valley.</li>
        </ul>
        <div class="alert alert--warning" style="margin-top:1.25rem">
          <div>
            <div class="alert__title">A note on the estate&rsquo;s wine history</div>
            <p>Boschendal is a historic Cape wine farm — its title deeds date from 1685 and its vineyards run six kilometres along the mountain slopes. That history is part of the place, and we would rather say so than have anyone find out on arrival.</p>
            <p>It is not part of our weekend. The Retreat is a self-contained venue in its own corner of the farm, with its own auditorium, dining and grounds. No alcohol is served at any convention event, wine tasting is not offered as a convention activity in any form, and the estate&rsquo;s tasting facilities are elsewhere on the property. If it would help you to know the layout before you commit, ask us and we will walk you through it.</p>
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
          <li><strong>Registration</strong> opens at 14:00 on Friday 27 August at the Retreat reception.</li>
          <li><strong>Cottage check-in</strong> from 16:00. Early-arrival guests on Thursday can check in from 15:00.</li>
          <li><strong>Parking is free</strong> and at the cottages themselves — you can unload at your own front door. Day visitors use the visitor field and should book the free parking pass so the venue can plan.</li>
          <li><strong>The Retreat is signposted from the main farm entrance</strong> and sits away from the public areas of the estate. Follow the Retreat signs, not the wine-tasting ones.</li>
          <li><strong>Checkout</strong> is 10:00 on Sunday. Luggage can be left at reception until the afternoon shuttles.</li>
          <li><strong>About an hour from Cape Town</strong>, forty minutes from Stellenbosch, fifteen from Franschhoek village.</li>
        </ul>
        <a class="btn btn--ghost" href="https://www.google.com/maps/search/?api=1&amp;query=Boschendal+Franschhoek" target="_blank" rel="noopener">Open directions in Google Maps</a>
      </div>

      <div>
        <div class="rule"></div>
        <h2>Accessibility</h2>
        <ul>
          <li><strong>Step-free rooms.</strong> The Accessible Retreat Rooms are in the cottages closest to the auditorium and the dining lounge, on level paved paths, with a widened doorway, a roll-in shower and grab rails.</li>
          <li><strong>Every bedroom is entered from outside</strong> — no stairs or corridors between the parking bay and your bed in those cottages.</li>
          <li><strong>The auditorium</strong> has reserved seating at the front and at the back, and runs through a PA system. Tell us if you need to be near the front.</li>
          <li><strong>Paths</strong> between the cottages, the auditorium and the dining lounge are paved and level; the mountainside trails and parts of the gardens are gravel and uneven.</li>
          <li><strong>Accessible parking bays</strong> are next to the auditorium.</li>
          <li><strong>Tell us what you need</strong> in the notes when you book. The accommodation team confirms the detail with the venue before you arrive rather than leaving you to discover it.</li>
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
    <p style="margin-inline:auto">Seventy-two beds on the estate, sold one at a time. Take a single bed, or book the whole room privately.</p>
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

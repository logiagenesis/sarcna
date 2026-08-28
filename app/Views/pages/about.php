<?php
use App\Core\View;
View::layout('layouts.public');
View::start('content');
$event = config('event');
?>
<?php View::include('partials.page-hero', [
  'eyebrow' => 'About',
  'title'   => 'Rooted in Recovery. Rising Together.',
  'lede'    => 'What this convention is, who it is for, and why we are holding it in the Cape Winelands.',
  'image'   => 'img/venue/boma-firepit.jpg',
  'crumbs'  => ['About' => null],
]); ?>

<section class="section">
  <div class="container grid grid--sidebar">
    <div class="prose">
      <div class="rule"></div>
      <h2>A weekend built around one idea</h2>
      <p>Recovery holds when it is rooted — in the steps, in service, and in each other. A convention is where those roots meet the air: three days out of ordinary life, in a place quiet enough to hear yourself think and full enough of people that you never have to.</p>
      <p><strong>Rooted in Recovery. Rising Together.</strong> is our theme for 2027. The first half is what keeps us here. The second half is what we do with it.</p>

      <p class="pull-quote">Nobody recovers alone, and nobody has to.</p>

      <h2>Who this weekend is for</h2>
      <ul>
        <li><strong>Newcomers.</strong> You are the most important person at this convention. Come as you are, sit where you like, leave when you need to.</li>
        <li><strong>Anyone with time in the fellowship</strong> who wants a weekend of meetings, workshops and the kind of conversation that only happens away from home.</li>
        <li><strong>People who want to be of service.</strong> Every convention runs on service, and there is a place for you.</li>
        <li><strong>Home groups travelling together.</strong> Book a four-bed cottage or the eight-bed farmhouse and keep the group in one place.</li>
      </ul>

      <h2>What is different about 2027</h2>
      <p>We have moved the convention to a retreat. Instead of a hotel and a hired conference room, the whole weekend happens at <strong>the Boschendal Retreat</strong> — eighteen self-catering cottages in a secluded corner of an 1&nbsp;800-hectare farm in the Cape Winelands, with their own auditorium, screening room, break-off rooms, dining lounge, boma and natural swimming pool.</p>
      <p>Everything is on one site, so registration, accommodation and transport are booked in one place, in one cart, with one payment. And beds are sold <strong>individually</strong>: a single person does not have to pay for a whole room, and anyone who would rather not share can book the room privately. Every bedroom has its own front door and its own bathroom, so sharing a cottage is not the same as sharing a room.</p>

      <h2>A substance-free weekend</h2>
      <p>Boschendal is a historic Cape farm with a wine-making history going back to 1685. We would rather tell you that than have you find out on arrival. It plays no part in our weekend: the Retreat is a self-contained venue away from the estate&rsquo;s public areas, no alcohol is served at any convention event, and wine tasting is not offered as a convention activity in any form. What we are there for is the land, the quiet and each other.</p>

      <h2>Anonymity</h2>
      <p>Who you see here and what you hear here stays here. No photography inside meetings, no posting pictures of other attendees, and a wristband at registration if you would rather not be photographed at all. Our <a href="<?= e(url('/photo-anonymity-notice')) ?>">photo and anonymity notice</a> sets out the detail.</p>

      <h2>Who runs it</h2>
      <p>The convention is organised by a volunteer committee drawn from groups across the region, and it is entirely self-supporting through registration, merchandise and Seventh Tradition contributions. Nothing on this site is sold for profit; any surplus goes to the following year&rsquo;s convention and to newcomer sponsorships.</p>
    </div>

    <aside class="summary">
      <h3 style="font-size:var(--step-1)">The essentials</h3>
      <div class="summary__row"><span>Dates</span><strong><?= e($event['dates_label']) ?></strong></div>
      <div class="summary__row"><span>Early arrival</span><strong><?= e(za_date($event['early_arrival'], 'D j M')) ?></strong></div>
      <div class="summary__row"><span>Venue</span><strong style="text-align:right"><?= e($event['venue_name']) ?></strong></div>
      <div class="summary__row"><span>Where</span><strong style="text-align:right"><?= e($event['venue_region']) ?></strong></div>
      <div class="summary__row"><span>From Cape Town</span><strong>About 1 hour</strong></div>

      <a class="btn btn--block" style="margin-top:1rem" href="<?= e(url('/shop/registration')) ?>">Register for the weekend</a>
      <a class="btn btn--ghost btn--block" style="margin-top:.6rem" href="<?= e(url('/accommodation')) ?>">Book a bed</a>

      <?php if ($faqs !== []): ?>
        <h3 style="font-size:var(--step-1);margin-top:1.75rem">Common questions</h3>
        <div class="accordion">
          <?php foreach ($faqs as $faq): ?>
            <details><summary style="font-size:var(--step--1)"><?= e($faq['question']) ?></summary><div class="accordion__body" style="font-size:var(--step--1)"><?= $faq['answer'] ?></div></details>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </aside>
  </div>
</section>
<?php View::stop(); ?>

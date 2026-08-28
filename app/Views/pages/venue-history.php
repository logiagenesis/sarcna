<?php
use App\Core\View;
View::layout('layouts.public');
View::start('content');
?>
<?php View::include('partials.page-hero', [
  'eyebrow' => 'The venue',
  'title'   => 'Venue history & the Cape Winelands',
  'lede'    => 'The valley, the farm, and why a place with this much history suits a weekend about starting again.',
  'image'   => 'img/venue/arrival-drive.jpg',
  'crumbs'  => ['The Venue' => '/venue', 'History' => null],
]); ?>

<section class="section">
  <div class="container grid grid--sidebar">
    <article class="prose">
      <div class="rule"></div>
      <h2>Bos-en-dal — wood and valley</h2>
      <p>Boschendal covers about 1&nbsp;800 hectares of the Dwars River valley, a broad green basin between Franschhoek and Stellenbosch with the Simonsberg rising on one side and the Groot Drakenstein on the other. Its Dutch name means simply <em>wood and valley</em>, which is a fair description of what you see when you come over the last rise.</p>
      <p>It is a working farm, not a museum: vineyards running some six kilometres along the mountain slopes, orchards, a food garden, cattle, and a kitchen that cooks what the land produces.</p>

      <h2>1685, and the people who came after</h2>
      <p>The farm&rsquo;s title deeds are dated <strong>1685</strong>, which makes it one of the oldest wine farms in South Africa. Its first owner was <strong>Jean le Long</strong>, one of roughly two hundred French Huguenots who fled religious persecution in Europe; the Dutch East India Company granted him land at the Cape in 1688 and the title deed was written in 1713.</p>
      <p>In 1715 the farm passed to another Huguenot, <strong>Abraham de Villiers</strong>, who sold it to his brother Jacques two years later. The De Villiers family farmed Boschendal until <strong>1879</strong> — more than a century and a half — and it was they who laid the farming foundations and built the Manor House that still stands at the werf.</p>

      <h2>The part of the story that is harder</h2>
      <p>None of that happened on empty land, and none of it was built by the families whose names are on the title deeds. The valley was Khoi land long before the first European settlement. The farms that followed — Boschendal among them — were built by enslaved people brought to the Cape from East Africa, Madagascar, India and Southeast Asia, and the descendants of those communities live in this valley still.</p>
      <p>Coming to a place like this means holding both things at once: the beauty of it, and an honest account of how it came to look the way it does. It seemed better to write that down than to leave it out.</p>

      <h2>The Retreat</h2>
      <p>Our weekend does not take place at the historic werf. It takes place at <strong>the Retreat</strong> — eighteen cottages in a secluded corner of the farm, built as a place for groups to come and think, with an auditorium, a screening room, break-off rooms, a dining lounge, a boma and a natural swimming pool, wrapped in fynbos gardens with the mountains on every horizon.</p>
      <p>That is why we chose it. It holds all of us in one place, it is quiet enough to hear yourself, and there is nowhere else to be.</p>

      <div class="alert alert--warning">
        <div>
          <div class="alert__title">Wine and our weekend</div>
          <p>Boschendal is a wine farm as well as a working farm, and we say so plainly rather than pretending otherwise. None of it forms part of our convention: no alcohol is served at any convention event or meal we run, tastings are not offered as a convention activity, and the Retreat is a self-contained venue away from the estate&rsquo;s public tasting areas. If it helps you to know the layout in advance, ask us and we will describe exactly where our weekend takes place.</p>
        </div>
      </div>

      <h2>The mountains, and the weather</h2>
      <p>The Simonsberg rises to the north-west and the Groot Drakenstein closes the valley to the east. Both catch the first and last light of the day, which is why the sunrise walk is on the programme and why most people end up at the boma at sunset without anyone suggesting it.</p>
      <p>Late August is the end of the Cape winter and the beginning of spring. Expect green hills, running water, orchards coming into blossom, and cold nights — often down to five or six degrees. Days can be bright and mild or wet and grey, sometimes both before lunch. Pack layers, a waterproof jacket and shoes you do not mind getting muddy. The cottages have fireplaces, and you will use them.</p>

      <hr>
      <p class="muted"><small>The history above is drawn from published accounts of the estate. Every factual claim on this page and on the venue page should be checked against the venue&rsquo;s own material before the site goes live; the committee&rsquo;s verification notes are kept in <code>/docs/venue-source-log.md</code>.</small></p>
    </article>

    <aside class="summary">
      <h3 style="font-size:var(--step-1)">In short</h3>
      <div class="summary__row"><span>Estate</span><strong>Boschendal, 1&nbsp;800 ha</strong></div>
      <div class="summary__row"><span>Title deeds</span><strong>1685</strong></div>
      <div class="summary__row"><span>Valley</span><strong>Dwars River</strong></div>
      <div class="summary__row"><span>Mountains</span><strong style="text-align:right">Simonsberg &amp; Groot&nbsp;Drakenstein</strong></div>
      <div class="summary__row"><span>Our venue</span><strong style="text-align:right">The Retreat<br>18 cottages, 72 beds</strong></div>
      <div class="summary__row"><span>From Cape Town</span><strong>About 1 hour</strong></div>
      <div class="summary__row"><span>August weather</span><strong style="text-align:right">Cool days, cold nights</strong></div>
      <hr>
      <a class="btn btn--block" href="<?= e(url('/venue')) ?>">Back to the venue</a>
      <a class="btn btn--ghost btn--block" style="margin-top:.6rem" href="<?= e(url('/accommodation')) ?>">Book a bed</a>
    </aside>
  </div>
</section>
<?php View::stop(); ?>

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
      <h2>The Dwars River valley</h2>
      <p>The estate lies in the Dwars River valley, a broad green corridor between the Simonsberg and Groot Drakenstein mountains in the Western Cape. It is farmed land — orchards, vineyards, pasture and oaks — and it has been worked continuously for more than three centuries.</p>
      <p>The valley&rsquo;s history is not a simple one. Long before European settlement it was the land of the Khoi. The farms that followed were built on the labour of enslaved people brought to the Cape from East Africa, Madagascar, India and Southeast Asia, and the descendants of those communities live in the valley still. Coming to a place like this means holding both things at once: the beauty of it, and an honest account of how it came to look the way it does.</p>

      <h2>The farm</h2>
      <p>Boschendal is one of the oldest farms in the valley, established in the late seventeenth century and worked ever since. The werf — the cluster of historic buildings at its heart — is Cape Dutch in style, with white gables, thick walls and small deep windows built for the Cape summer.</p>
      <p>The estate today is a working farm as much as a destination: orchards, vegetable gardens, livestock, and a farm kitchen that cooks what the land produces. The retreat cottages and the conference barn sit apart from the historic werf, in among the orchards.</p>

      <h2>Why here</h2>
      <p>We looked for somewhere quiet, somewhere whole, and somewhere that would hold a few hundred people without feeling like a hotel. This valley does that. There are walks that take twenty minutes and walks that take two hours. There is a barn big enough for all of us and cottages far enough apart that you can be alone when you need to be.</p>
      <p>There is also something fitting about holding a convention on rooted ground — old vines, old oaks, old walls — while we talk about starting again.</p>

      <div class="alert alert--warning">
        <div>
          <div class="alert__title">Wine and our weekend</div>
          <p>Boschendal is a wine estate as well as a farm, and we say so plainly rather than pretending otherwise. None of it forms part of our convention: no alcohol is served at any convention event or meal we run, tastings are not offered as a convention activity, and the areas we use are separate from the estate&rsquo;s tasting facilities. If it helps you to know the layout in advance, ask us and we will describe exactly where our weekend takes place.</p>
        </div>
      </div>

      <h2>The mountains</h2>
      <p>The Simonsberg rises to the north-west and the Groot Drakenstein range closes the valley to the east. Both catch the first and last light of the day, which is why the sunrise walk is on the programme, and why most people end up on the fire pit lawn at sunset without anyone suggesting it.</p>

      <h2>August in the valley</h2>
      <p>Late August is the end of the Cape winter and the start of spring. Expect green hills, running water, orchards coming into blossom, and cold nights — often down to five or six degrees. Days can be bright and mild or wet and grey, sometimes both. Pack layers, a waterproof jacket and shoes you do not mind getting muddy.</p>

      <hr>
      <p class="muted"><small>Every factual claim on this page must be checked against the venue&rsquo;s own material before the site goes live. The committee&rsquo;s verification notes are kept in <code>/docs/venue-source-log.md</code>.</small></p>
    </article>

    <aside class="summary">
      <h3 style="font-size:var(--step-1)">In short</h3>
      <div class="summary__row"><span>Region</span><strong>Cape Winelands</strong></div>
      <div class="summary__row"><span>Valley</span><strong>Dwars River</strong></div>
      <div class="summary__row"><span>Mountains</span><strong style="text-align:right">Simonsberg &amp; Groot Drakenstein</strong></div>
      <div class="summary__row"><span>Nearest village</span><strong>Franschhoek</strong></div>
      <div class="summary__row"><span>August weather</span><strong style="text-align:right">Cool days, cold nights</strong></div>
      <hr>
      <a class="btn btn--block" href="<?= e(url('/venue')) ?>">Back to the venue</a>
      <a class="btn btn--ghost btn--block" style="margin-top:.6rem" href="<?= e(url('/accommodation')) ?>">Book a bed</a>
    </aside>
  </div>
</section>
<?php View::stop(); ?>

<?php
/**
 * The venue on film — official videos from Boschendal's own YouTube channel.
 *
 * Privacy first: nothing is loaded from YouTube until a visitor chooses to
 * press play. The card shows a locally hosted poster (with the official
 * thumbnail layered on top where the network allows it); the click swaps in a
 * youtube-nocookie.com iframe, so no Google cookies are set by merely visiting
 * this page. Each card also carries a plain "watch on YouTube" link, which is
 * all a visitor with scripts disabled needs.
 *
 * The selection is deliberate for a recovery convention: the estate, the
 * function spaces and the Tree House venue — none of the channel's
 * wine-tasting material.
 */
$venueVideos = [
    [
        'id'     => '7OJNTWqSE0o',
        'title'  => 'Live our farm, your way',
        'blurb'  => 'Boschendal\'s own short film of the estate — the mountains, the werf and the outdoors delegates will have on their doorstep.',
        'poster' => 'venue/boschendal-overview',
    ],
    [
        'id'     => '8W-kIQsgRLg',
        'title'  => 'The function spaces',
        'blurb'  => 'The estate\'s celebration venues on film — the same spaces that host the convention\'s main meeting and evening programme.',
        'poster' => 'venue/conference-barn',
    ],
    [
        'id'     => 'Jv-ejDHfSPQ',
        'title'  => 'The Tree House',
        'blurb'  => 'A look inside one of Boschendal\'s most-loved venues, tucked into the trees a short walk from the cottages.',
        'poster' => 'venue/boma-firepit',
    ],
];
?>
<section class="section section--sunk" id="videos">
  <div class="container">
    <div class="section-head section-head--center">
      <div class="rule" style="margin-inline:auto"></div>
      <span class="eyebrow">See it move</span>
      <h2>The estate on film</h2>
      <p>Three short films from Boschendal's own channel. Nothing loads from YouTube until you press play.</p>
    </div>

    <div class="video-grid">
      <?php foreach ($venueVideos as $video): ?>
        <figure class="video-card" data-video-id="<?= e($video['id']) ?>" data-video-title="<?= e($video['title']) ?>">
          <button type="button" class="video-card__poster" aria-label="Play the video: <?= e($video['title']) ?>">
            <img src="<?= e(asset('img/' . $video['poster'] . '.jpg')) ?>" alt="" loading="lazy" width="1200" height="675">
            <img class="video-card__thumb" src="https://i.ytimg.com/vi/<?= e($video['id']) ?>/hqdefault.jpg"
                 alt="" loading="lazy" onerror="this.remove()">
            <span class="video-card__play" aria-hidden="true">
              <svg viewBox="0 0 24 24" width="26" height="26" fill="currentColor"><path d="M8 5.5v13l11-6.5z"/></svg>
            </span>
          </button>
          <figcaption>
            <strong><?= e($video['title']) ?></strong>
            <p><?= e($video['blurb']) ?></p>
            <a class="video-card__external" href="https://www.youtube.com/watch?v=<?= e($video['id']) ?>"
               target="_blank" rel="noopener">Watch on YouTube</a>
          </figcaption>
        </figure>
      <?php endforeach; ?>
    </div>

    <p class="muted" style="text-align:center;font-size:var(--step--1);margin-top:1.25rem">
      Films by and &copy; <a href="https://boschendal.com/" target="_blank" rel="noopener">Boschendal</a>.
      Pressing play loads the video from YouTube's privacy-enhanced player.
    </p>
  </div>
</section>

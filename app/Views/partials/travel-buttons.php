<?php
/**
 * The two travel buttons — flights and a hire car. By design these appear in
 * exactly two places: the checkout page and the order confirmation. Nowhere
 * else, so they stay a helpful nudge instead of an advert.
 */
?>
<div class="travel-nudge">
  <p class="travel-nudge__label">Still need to get here?</p>
  <div class="travel-nudge__buttons">
    <a class="travel-btn" href="https://www.cheapflights.co.za/" target="_blank" rel="noopener">
      <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M17.8 19.2 16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2z"/>
      </svg>
      <span>
        <strong>Book flights</strong>
        <small>cheapflights.co.za</small>
      </span>
    </a>
    <a class="travel-btn" href="https://www.avis.co.za/" target="_blank" rel="noopener">
      <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M5 11 6.5 6.5A2 2 0 0 1 8.4 5h7.2a2 2 0 0 1 1.9 1.5L19 11m-14 0h14m-14 0a2 2 0 0 0-2 2v4a1 1 0 0 0 1 1h1a1 1 0 0 0 1-1v-1h12v1a1 1 0 0 0 1 1h1a1 1 0 0 0 1-1v-4a2 2 0 0 0-2-2M7.5 14h.01M16.5 14h.01"/>
      </svg>
      <span>
        <strong>Hire a car</strong>
        <small>avis.co.za</small>
      </span>
    </a>
  </div>
</div>

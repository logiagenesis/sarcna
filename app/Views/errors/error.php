<?php
use App\Core\View;
use App\Services\SeoService;

SeoService::set(['title' => $status . ' — Page not available', 'robots' => 'noindex,follow']);
View::layout('layouts.public');
View::start('content');
?>
<section class="section">
  <div class="container container--narrow text-center">
    <span class="eyebrow">Error <?= (int) $status ?></span>
    <h1><?= e($message) ?></h1>
    <p class="muted" style="margin-inline:auto">
      The page you were looking for may have moved, or the link may be out of date.
      Everything below is a good place to pick up again.
    </p>
    <div class="cluster cluster--center" style="margin-top:2rem">
      <a class="btn" href="<?= e(url('/')) ?>">Home</a>
      <a class="btn btn--ghost" href="<?= e(url('/shop/registration')) ?>">Register</a>
      <a class="btn btn--ghost" href="<?= e(url('/accommodation')) ?>">Accommodation</a>
      <a class="btn btn--ghost" href="<?= e(url('/contact')) ?>">Contact us</a>
    </div>
  </div>
</section>
<?php View::stop(); ?>

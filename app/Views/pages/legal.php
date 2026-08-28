<?php
use App\Core\View;
View::layout('layouts.public');
View::start('content');
?>
<?php View::include('partials.page-hero', [
  'eyebrow' => 'Policies',
  'title'   => $page['title'],
  'lede'    => $page['subtitle'],
  'image'   => $page['hero_image'] ?? 'img/backgrounds/section-mist.jpg',
  'crumbs'  => [$page['title'] => null],
]); ?>

<section class="section">
  <div class="container grid grid--sidebar">
    <article class="prose"><?= $page['body_html'] ?></article>

    <aside class="summary">
      <h3 style="font-size:var(--step-1)">All policies</h3>
      <ul style="list-style:none;padding:0;margin:0">
        <?php foreach ($legal as $item): ?>
          <li style="margin-bottom:.45rem">
            <a href="<?= e(url('/' . $item['slug'])) ?>" <?= $item['slug'] === $page['slug'] ? 'style="font-weight:700"' : '' ?>><?= $item['title'] ?></a>
          </li>
        <?php endforeach; ?>
      </ul>
      <hr>
      <p class="muted" style="font-size:var(--step--1)">Last updated <?= e(za_date((string) $page['updated_at'], 'j F Y')) ?>. Questions about this policy can go to the committee through the <a href="<?= e(url('/contact')) ?>">contact form</a>.</p>
    </aside>
  </div>
</section>
<?php View::stop(); ?>

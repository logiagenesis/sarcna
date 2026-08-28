<?php
use App\Core\View;
View::layout('layouts.public');
View::start('content');
?>
<?php View::include('partials.page-hero', [
  'eyebrow' => 'Questions',
  'title'   => 'Frequently asked questions',
  'lede'    => 'If your question is not here, WhatsApp us — someone from the committee answers most days.',
  'image'   => 'img/venue/dining-lounge.jpg',
  'crumbs'  => ['FAQ' => null],
]); ?>

<section class="section">
  <div class="container grid grid--sidebar">
    <div>
      <?php foreach ($grouped as $category => $faqs): ?>
        <h2 id="<?= e(slugify((string) $category)) ?>" style="margin-top:2rem"><?= e($category) ?></h2>
        <div class="accordion">
          <?php foreach ($faqs as $faq): ?>
            <details>
              <summary><?= e($faq['question']) ?></summary>
              <div class="accordion__body"><?= $faq['answer'] ?></div>
            </details>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <aside class="summary">
      <h3 style="font-size:var(--step-1)">Jump to</h3>
      <ul style="list-style:none;padding:0;margin:0">
        <?php foreach (array_keys($grouped) as $category): ?>
          <li style="margin-bottom:.4rem"><a href="#<?= e(slugify((string) $category)) ?>"><?= e($category) ?></a></li>
        <?php endforeach; ?>
      </ul>
      <hr>
      <a class="btn btn--block" href="<?= e(whatsapp_link()) ?>" target="_blank" rel="noopener" data-track="whatsapp_click">WhatsApp the committee</a>
      <a class="btn btn--ghost btn--block" style="margin-top:.6rem" href="<?= e(url('/contact')) ?>">Send a message</a>
    </aside>
  </div>
</section>
<?php View::stop(); ?>

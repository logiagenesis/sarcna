<?php
use App\Core\View;
View::layout('layouts.public');
View::start('content');
?>
<?php View::include('partials.page-hero', [
  'eyebrow' => 'Service',
  'title'   => 'Do service at the convention',
  'lede'    => 'Every convention runs on service, and there is a place for you whether you have three weeks clean or thirty years.',
  'image'   => 'img/venue/natural-pool.jpg',
  'crumbs'  => ['Service' => null],
]); ?>

<section class="section">
  <div class="container grid grid--sidebar">
    <div>
      <div class="rule"></div>
      <h2>What service looks like</h2>
      <p>Most shifts are two to three hours across the weekend, and you will still get to the meetings you want to be in. The service co-ordinator places people according to what they offer and what the weekend needs, and sends shifts out before the convention.</p>

      <div class="grid grid--2" style="margin-top:1.5rem">
        <div>
          <h3 style="font-size:var(--step-1)">Areas that always need people</h3>
          <ul style="font-size:var(--step--1)">
            <li><strong>Registration</strong> — badges, packs and answering the same question kindly for the fortieth time.</li>
            <li><strong>Hospitality</strong> — welcoming, directing, looking after newcomers.</li>
            <li><strong>Transport</strong> — airport runs and shuttle marshalling. A valid licence helps.</li>
            <li><strong>Merchandise</strong> — the shop table, sizes, and cashing up.</li>
          </ul>
        </div>
        <div>
          <h3 style="font-size:var(--step-1)">And also</h3>
          <ul style="font-size:var(--step--1)">
            <li><strong>Tea and coffee</strong> — the most important job at any convention.</li>
            <li><strong>Stewarding</strong> — keeping the meetings running and the space safe.</li>
            <li><strong>Decor and set-up</strong> — Thursday and Friday.</li>
            <li><strong>Clean-up</strong> — Sunday afternoon, and always short of hands.</li>
          </ul>
        </div>
      </div>

      <div class="alert alert--info" style="margin-top:1.5rem">
        <div><div class="alert__title">Newcomers welcome</div>
        <p>You do not need years of clean time to be useful. Tell us where you are and we will place you somewhere you will enjoy.</p></div>
      </div>
    </div>

    <aside class="summary">
      <h2 style="font-size:var(--step-2);margin-bottom:.25rem">Service application</h2>
      <p class="muted" style="font-size:var(--step--1)">The co-ordinator will be in touch before the weekend.</p>

      <form method="post" action="<?= e(url('/service')) ?>" data-once data-track-submit="generate_lead" data-track-params='{"form":"service"}'>
        <?= csrf_field() ?>
        <div class="honeypot"><label>Leave this empty <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

        <div class="field">
          <label class="field__label" for="name">Full name</label>
          <input type="text" id="name" name="name" required value="<?= e(old('name', auth() ? trim(auth()['first_name'] . ' ' . auth()['last_name']) : '')) ?>">
          <?php if ($m = error_for('name')): ?><p class="field__error"><?= e($m) ?></p><?php endif; ?>
        </div>

        <div class="field-row field-row--2">
          <div class="field">
            <label class="field__label" for="email">Email</label>
            <input type="email" id="email" name="email" required value="<?= e(old('email', auth()['email'] ?? '')) ?>">
            <?php if ($m = error_for('email')): ?><p class="field__error"><?= e($m) ?></p><?php endif; ?>
          </div>
          <div class="field">
            <label class="field__label" for="phone">Mobile</label>
            <input type="tel" id="phone" name="phone" required value="<?= e(old('phone', auth()['phone'] ?? '')) ?>">
            <?php if ($m = error_for('phone')): ?><p class="field__error"><?= e($m) ?></p><?php endif; ?>
          </div>
        </div>

        <div class="field-row field-row--2">
          <div class="field">
            <label class="field__label" for="region">Area or region</label>
            <input type="text" id="region" name="region" value="<?= e(old('region')) ?>">
          </div>
          <div class="field">
            <label class="field__label" for="home_group">Home group</label>
            <input type="text" id="home_group" name="home_group" value="<?= e(old('home_group')) ?>">
          </div>
        </div>

        <div class="field">
          <label class="field__label" for="clean_time">Clean time</label>
          <input type="text" id="clean_time" name="clean_time" placeholder="Optional — some service positions ask for it" value="<?= e(old('clean_time')) ?>">
        </div>

        <div class="field">
          <span class="field__label">Where would you like to serve?</span>
          <?php foreach ($areas as $area): ?>
            <label class="checkbox"><input type="checkbox" name="service_areas[]" value="<?= e($area) ?>"><span><?= e($area) ?></span></label>
          <?php endforeach; ?>
          <?php if ($m = error_for('service_areas')): ?><p class="field__error"><?= e($m) ?></p><?php endif; ?>
        </div>

        <div class="field">
          <label class="field__label" for="availability">When are you available?</label>
          <input type="text" id="availability" name="availability" placeholder="e.g. All day Saturday, Friday evening" value="<?= e(old('availability')) ?>">
        </div>

        <div class="field">
          <label class="field__label" for="skills">Anything useful we should know?</label>
          <textarea id="skills" name="skills" rows="2" style="min-height:70px" placeholder="Driver's licence, first aid, sound, admin…"><?= e(old('skills')) ?></textarea>
        </div>

        <div class="field">
          <label class="field__label" for="notes">Notes</label>
          <textarea id="notes" name="notes" rows="2" style="min-height:70px"><?= e(old('notes')) ?></textarea>
        </div>

        <label class="checkbox">
          <input type="checkbox" name="consent" value="1" required>
          <span>I agree that the committee may store and use these details to place me in service, in line with the <a href="<?= e(url('/privacy-policy')) ?>">privacy policy</a>.</span>
        </label>
        <?php if ($m = error_for('consent')): ?><p class="field__error"><?= e($m) ?></p><?php endif; ?>

        <button class="btn btn--block btn--lg" type="submit" data-busy-label="Sending…">Submit application</button>
      </form>
    </aside>
  </div>
</section>
<?php View::stop(); ?>

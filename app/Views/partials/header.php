<?php
/** @var int $cartCount */
use App\Services\AuthService;
use App\Services\SettingsService;

$navigation = [
    ['label' => 'The Convention', 'url' => '/convention', 'children' => [
        '/convention'    => 'Convention overview',
        '/about'         => 'About SARCNA 2027',
        '/programme'     => 'Weekend programme',
        '/service'       => 'Service & volunteering',
        '/faq'           => 'Frequently asked questions',
    ]],
    ['label' => 'The Venue', 'url' => '/venue', 'children' => [
        '/venue'         => 'Boschendal experience',
        '/venue/history' => 'Venue history & the Winelands',
        '/gallery'       => 'Gallery',
    ]],
    ['label' => 'Stay', 'url' => '/accommodation', 'children' => []],
    ['label' => 'Transport', 'url' => '/transport', 'children' => []],
    ['label' => 'Shop', 'url' => '/shop', 'children' => [
        '/shop/registration'  => 'Registration & day passes',
        '/shop/merchandise'   => 'Merchandise',
        '/donations'          => 'Donations',
    ]],
    ['label' => 'Contact', 'url' => '/contact', 'children' => []],
];
?>
<header class="site-header">
  <div class="container site-header__inner">
    <a class="site-logo" href="<?= e(url('/')) ?>" aria-label="<?= e(SettingsService::get('site_name', config('app.name'))) ?> home">
      <img src="<?= e(asset('brand/logo.svg')) ?>" alt="<?= e(SettingsService::get('site_name', config('app.name'))) ?>" width="230" height="66">
    </a>

    <nav class="nav" aria-label="Main">
      <ul class="nav__list">
        <?php foreach ($navigation as $item): ?>
          <li class="nav__item">
            <a class="nav__link<?= nav_class($item['url']) ?>" href="<?= e(url($item['url'])) ?>"><?= e($item['label']) ?></a>
            <?php if ($item['children'] !== []): ?>
              <ul class="nav__panel">
                <?php foreach ($item['children'] as $childUrl => $childLabel): ?>
                  <li><a href="<?= e(url($childUrl)) ?>"><?= e($childLabel) ?></a></li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    </nav>

    <div class="header-actions">
      <a class="btn btn--gold btn--sm" href="<?= e(url('/shop/registration')) ?>"
         data-track="select_promotion" data-track-params='{"promotion_name":"header_register"}'>Register</a>

      <a class="cart-button" href="<?= e(url('/cart')) ?>" aria-label="Cart, <?= (int) $cartCount ?> item(s)">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
          <path d="M3 3h2l2.4 12.1a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.6L21 7H6"/>
          <circle cx="10" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/>
        </svg>
        <span class="cart-button__count" data-cart-count<?= $cartCount === 0 ? ' hidden' : '' ?>><?= (int) $cartCount ?></span>
      </a>

      <?php if (AuthService::check()): ?>
        <a class="btn btn--ghost btn--sm" href="<?= e(url(AuthService::isAdmin() ? '/admin' : '/account')) ?>">
          <?= AuthService::isAdmin() ? 'Admin' : 'My account' ?>
        </a>
      <?php else: ?>
        <a class="btn btn--ghost btn--sm" href="<?= e(url('/login')) ?>">Sign in</a>
      <?php endif; ?>

      <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="mobile-nav" aria-label="Open menu">
        <span></span><span></span><span></span>
      </button>
    </div>
  </div>

  <div class="mobile-nav" id="mobile-nav">
    <div class="container">
      <ul>
        <?php foreach ($navigation as $item): ?>
          <li>
            <a href="<?= e(url($item['url'])) ?>"><?= e($item['label']) ?></a>
            <?php if ($item['children'] !== []): ?>
              <ul class="mobile-nav__sub">
                <?php foreach ($item['children'] as $childUrl => $childLabel): ?>
                  <li><a href="<?= e(url($childUrl)) ?>"><?= e($childLabel) ?></a></li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
        <li><a href="<?= e(url('/cart')) ?>">Cart (<?= (int) $cartCount ?>)</a></li>
        <li><a href="<?= e(url(AuthService::check() ? '/account' : '/login')) ?>"><?= AuthService::check() ? 'My account' : 'Sign in' ?></a></li>
      </ul>
    </div>
  </div>
</header>

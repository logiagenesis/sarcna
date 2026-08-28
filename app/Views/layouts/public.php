<?php
/** @var string $__content */
use App\Core\View;
use App\Services\CartService;
use App\Services\SeoService;
use App\Services\SettingsService;

$gaId          = (string) SettingsService::get('ga_measurement_id', config('analytics.ga_measurement_id', ''));
$verification  = (string) SettingsService::get('google_site_verification', config('analytics.google_site_verification', ''));
$cartCount     = CartService::count();
$minimiseChat  = is_active('/checkout') || is_active('/cart');
?><!DOCTYPE html>
<html lang="en-ZA">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(SeoService::title()) ?></title>
<meta name="description" content="<?= e(SeoService::description()) ?>">
<meta name="robots" content="<?= e(SeoService::robots()) ?>">
<link rel="canonical" href="<?= e(SeoService::canonical()) ?>">
<?php if ($verification !== ''): ?>
<meta name="google-site-verification" content="<?= e($verification) ?>">
<?php endif; ?>

<meta property="og:type" content="<?= e(SeoService::type()) ?>">
<meta property="og:site_name" content="<?= e(SettingsService::get('site_name', config('app.name'))) ?>">
<meta property="og:title" content="<?= e(SeoService::get('title', config('event.title'))) ?>">
<meta property="og:description" content="<?= e(SeoService::description()) ?>">
<meta property="og:url" content="<?= e(SeoService::canonical()) ?>">
<meta property="og:image" content="<?= e(SeoService::image()) ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:locale" content="en_ZA">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e(SeoService::get('title', config('event.title'))) ?>">
<meta name="twitter:description" content="<?= e(SeoService::description()) ?>">
<meta name="twitter:image" content="<?= e(SeoService::image()) ?>">
<meta name="theme-color" content="#173D2F">

<link rel="icon" href="<?= e(asset('brand/favicon.svg')) ?>" type="image/svg+xml">
<link rel="alternate icon" href="<?= e(asset('brand/favicon.ico')) ?>" sizes="48x48">
<link rel="apple-touch-icon" href="<?= e(asset('brand/apple-touch-icon.png')) ?>">

<link rel="preload" href="<?= e(asset('fonts/Lora-Bold.ttf')) ?>" as="font" type="font/ttf" crossorigin>
<link rel="preload" href="<?= e(asset('fonts/WorkSans-Regular.ttf')) ?>" as="font" type="font/ttf" crossorigin>
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">

<?php foreach (SeoService::schemaBlocks() as $schema): ?>
<script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
<?php endforeach; ?>

<?php if ($gaId !== ''): ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($gaId) ?>"></script>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', '<?= e($gaId) ?>', { anonymize_ip: true });
</script>
<?php endif; ?>
<?= View::section('head') ?>
</head>
<body data-minimise-whatsapp="<?= $minimiseChat ? '1' : '0' ?>">
<a class="skip-link" href="#main">Skip to content</a>

<?php View::include('partials.notice-bar'); ?>
<?php View::include('partials.header', ['cartCount' => $cartCount]); ?>

<main id="main">
<?php View::include('partials.flash'); ?>
<?= View::section('content') ?>
</main>

<?php View::include('partials.footer'); ?>
<?php View::include('partials.whatsapp'); ?>
<?php View::include('partials.cookie-notice'); ?>

<script src="<?= e(asset('js/app.js')) ?>" defer></script>
<?= View::section('scripts') ?>
</body>
</html>

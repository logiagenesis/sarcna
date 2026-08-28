<?php
use App\Core\View;
use App\Services\AuthService;

$user = AuthService::user();

$navigation = [
    'Overview' => [
        ['/admin', 'Dashboard', 'dashboard'],
        ['/admin/checkin', 'Check-in desk', 'checkin'],
    ],
    'Finance' => [
        ['/admin/finance', 'Overview', 'finance'],
        ['/admin/finance/income', 'Income', 'finance'],
        ['/admin/finance/expenses', 'Expenses', 'finance'],
        ['/admin/finance/budget', 'Budget vs actual', 'finance'],
        ['/admin/finance/reconciliation', 'Bank reconciliation', 'finance'],
        ['/admin/finance/refunds', 'Refunds', 'finance'],
    ],
    'Money' => [
        ['/admin/orders', 'Orders', 'orders'],
        ['/admin/payments', 'Payments', 'payments'],
        ['/admin/donations', 'Donations', 'donations'],
        ['/admin/coupons', 'Coupons', 'coupons'],
    ],
    'Accommodation' => [
        ['/admin/bookings/operations', 'Rooming operations', 'bookings'],
        ['/admin/bookings', 'Bookings', 'bookings'],
        ['/admin/bookings/board', 'Bed board', 'bookings'],
        ['/admin/bookings/run-sheet', 'Run sheet', 'bookings'],
        ['/admin/bookings/holds', 'Live holds', 'bookings'],
        ['/admin/rooms', 'Room types & beds', 'rooms'],
    ],
    'Transport' => [
        ['/admin/transport', 'Routes & departures', 'transport'],
    ],
    'Shop' => [
        ['/admin/products', 'Products & stock', 'products'],
    ],
    'People' => [
        ['/admin/customers', 'Customers & admins', 'customers'],
        ['/admin/applications', 'Service applications', 'dashboard'],
        ['/admin/messages', 'Contact messages', 'dashboard'],
    ],
    'Content' => [
        ['/admin/content', 'Banners, pages, programme', 'content'],
        ['/admin/gallery', 'Gallery', 'gallery'],
    ],
    'Settings' => [
        ['/admin/settings', 'Site settings', '*'],
        ['/admin/settings/email-templates', 'Email templates', '*'],
        ['/admin/settings/diagnostics', 'Diagnostics', '*'],
        ['/admin/logs', 'Logs', '*'],
    ],
];
?><!DOCTYPE html>
<html lang="en-ZA">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title><?= e(($pageTitle ?? 'Admin') . ' — SARCNA 2027 admin') ?></title>
<link rel="icon" href="<?= e(asset('brand/favicon.svg')) ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body class="admin">
<div class="admin-shell">
  <aside class="admin-side">
    <a class="admin-side__brand" href="<?= e(url('/admin')) ?>">
      <img src="<?= e(asset('brand/logo-light.svg')) ?>" alt="SARCNA 2027 admin">
    </a>

    <?php foreach ($navigation as $group => $links):
      $visible = array_filter($links, static fn (array $link): bool => $link[2] === '*' ? can('*') : can($link[2]));
      if ($visible === []) continue; ?>
      <div class="admin-side__group">
        <p class="admin-side__label"><?= e($group) ?></p>
        <?php foreach ($visible as [$path, $label, $permission]): ?>
          <a href="<?= e(url($path)) ?>" class="<?= is_active($path, $path === '/admin') ? 'is-active' : '' ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>

    <div class="admin-side__foot">
      <p style="margin:0 0 .35rem"><strong style="color:var(--cream)"><?= e(trim($user['first_name'] . ' ' . $user['last_name'])) ?></strong></p>
      <p style="margin:0 0 .6rem;font-size:.75rem">
        <?= e(implode(', ', array_map(static fn (string $role): string => AuthService::ROLE_LABELS[$role] ?? $role, $user['roles']))) ?>
      </p>
      <a href="<?= e(url('/')) ?>">View the website &rarr;</a>
      <form method="post" action="<?= e(url('/logout')) ?>" style="margin-top:.5rem">
        <?= csrf_field() ?>
        <button class="btn btn--sm btn--outline-light" type="submit">Sign out</button>
      </form>
    </div>
  </aside>

  <main class="admin-main">
    <?php View::include('partials.flash'); ?>
    <?= View::section('content') ?>
  </main>
</div>
<script src="<?= e(asset('js/admin.js')) ?>" defer></script>
</body>
</html>

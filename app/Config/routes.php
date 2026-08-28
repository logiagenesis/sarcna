<?php
declare(strict_types=1);

use App\Controllers\AccommodationController;
use App\Controllers\AccountController;
use App\Controllers\AuthController;
use App\Controllers\CartController;
use App\Controllers\CheckoutController;
use App\Controllers\ContactController;
use App\Controllers\DonationController;
use App\Controllers\GalleryController;
use App\Controllers\HomeController;
use App\Controllers\InstallController;
use App\Controllers\PageController;
use App\Controllers\PaymentController;
use App\Controllers\ProgrammeController;
use App\Controllers\ServiceController;
use App\Controllers\ShopController;
use App\Controllers\SitemapController;
use App\Controllers\TransportController;
use App\Controllers\VenueController;
use App\Controllers\Admin;
use App\Core\Router;

$router = new Router();

/* ------------------------------------------------------------- installer */

$router->get('/install', [InstallController::class, 'index']);
$router->post('/install', [InstallController::class, 'run'], ['throttle:15,600']);

/* ---------------------------------------------------------------- public */

$router->get('/', [HomeController::class, 'index']);
$router->get('/about', [PageController::class, 'about']);
$router->get('/convention', [PageController::class, 'convention']);
$router->get('/programme', [ProgrammeController::class, 'index']);
$router->get('/venue', [VenueController::class, 'index']);
$router->get('/venue/history', [VenueController::class, 'history']);
$router->get('/gallery', [GalleryController::class, 'index']);
$router->get('/faq', [PageController::class, 'faq']);

$router->get('/accommodation', [AccommodationController::class, 'index']);
$router->get('/accommodation/{slug}', [AccommodationController::class, 'show']);
$router->post('/accommodation/{slug}/book', [AccommodationController::class, 'book'], ['throttle:40,300']);

$router->get('/shop', [ShopController::class, 'index']);
$router->get('/shop/registration', [ShopController::class, 'registration']);
$router->get('/shop/merchandise', [ShopController::class, 'merchandise']);
$router->get('/shop/{slug}', [ShopController::class, 'show']);
$router->post('/shop/{slug}/add', [ShopController::class, 'add'], ['throttle:60,300']);

$router->get('/transport', [TransportController::class, 'index']);
$router->get('/transport/{slug}', [TransportController::class, 'show']);
$router->post('/transport/{slug}/book', [TransportController::class, 'book'], ['throttle:40,300']);

$router->get('/donations', [DonationController::class, 'index']);
$router->post('/donations', [DonationController::class, 'add'], ['throttle:30,300']);

$router->get('/service', [ServiceController::class, 'index']);
$router->post('/service', [ServiceController::class, 'store'], ['throttle:10,900']);

$router->get('/contact', [ContactController::class, 'index']);
$router->post('/contact', [ContactController::class, 'store'], ['throttle:10,900']);

/* ------------------------------------------------------------ cart flow */

$router->get('/cart', [CartController::class, 'index']);
$router->post('/cart/update', [CartController::class, 'update']);
$router->post('/cart/remove', [CartController::class, 'remove']);
$router->post('/cart/clear', [CartController::class, 'clear']);
$router->post('/cart/coupon', [CartController::class, 'coupon'], ['throttle:20,600']);
$router->post('/cart/coupon/remove', [CartController::class, 'removeCoupon']);
$router->get('/cart/status', [CartController::class, 'status']);

$router->get('/checkout', [CheckoutController::class, 'index']);
$router->post('/checkout', [CheckoutController::class, 'place'], ['throttle:20,600']);
$router->get('/checkout/pay/{reference}', [CheckoutController::class, 'pay']);

$router->get('/payment/success', [PaymentController::class, 'success']);
$router->get('/payment/cancelled', [PaymentController::class, 'cancelled']);
$router->post('/payment/notify', [PaymentController::class, 'notify']);
$router->get('/payment/notify', [PaymentController::class, 'notifyProbe']);

/* --------------------------------------------------------------- account */

$router->get('/login', [AuthController::class, 'showLogin'], ['guest']);
$router->post('/login', [AuthController::class, 'login'], ['throttle:10,900']);
$router->get('/register', [AuthController::class, 'showRegister'], ['guest']);
$router->post('/register', [AuthController::class, 'register'], ['throttle:8,900']);
$router->post('/logout', [AuthController::class, 'logout']);
$router->get('/forgot-password', [AuthController::class, 'showForgotPassword'], ['guest']);
$router->post('/forgot-password', [AuthController::class, 'sendResetLink'], ['throttle:6,900']);
$router->get('/reset-password', [AuthController::class, 'showResetPassword'], ['guest']);
$router->post('/reset-password', [AuthController::class, 'resetPassword'], ['throttle:8,900']);
$router->get('/verify-email', [AuthController::class, 'verifyEmail']);
$router->post('/verify-email/resend', [AuthController::class, 'resendVerification'], ['throttle:5,900']);

$router->group('/account', ['auth'], static function (Router $router): void {
    $router->get('', [AccountController::class, 'index']);
    $router->get('/orders', [AccountController::class, 'orders']);
    $router->get('/orders/{reference}', [AccountController::class, 'order']);
    $router->get('/bookings', [AccountController::class, 'bookings']);
    $router->get('/transport', [AccountController::class, 'transport']);
    $router->get('/profile', [AccountController::class, 'profile']);
    $router->post('/profile', [AccountController::class, 'updateProfile']);
    $router->post('/password', [AccountController::class, 'updatePassword'], ['throttle:10,900']);
    $router->get('/invoice/{reference}', [AccountController::class, 'invoice']);
});

/* ----------------------------------------------------------------- legal */

$router->get('/privacy-policy', [PageController::class, 'legal']);
$router->get('/refund-policy', [PageController::class, 'legal']);
$router->get('/terms', [PageController::class, 'legal']);
$router->get('/code-of-conduct', [PageController::class, 'legal']);
$router->get('/accommodation-terms', [PageController::class, 'legal']);
$router->get('/transport-terms', [PageController::class, 'legal']);
$router->get('/merchandise-terms', [PageController::class, 'legal']);
$router->get('/photo-anonymity-notice', [PageController::class, 'legal']);

/* ------------------------------------------------------------------- seo */

$router->get('/sitemap.xml', [SitemapController::class, 'index']);
$router->get('/robots.txt', [SitemapController::class, 'robots']);

/* ----------------------------------------------------------------- admin */

$router->group('/admin', ['admin'], static function (Router $router): void {
    $router->get('', [Admin\DashboardController::class, 'index']);

    // Orders, payments, customers
    $router->get('/orders', [Admin\OrderController::class, 'index'], ['admin:orders']);
    $router->get('/orders/{id}', [Admin\OrderController::class, 'show'], ['admin:orders']);
    $router->post('/orders/{id}/status', [Admin\OrderController::class, 'updateStatus'], ['admin:orders']);
    $router->post('/orders/{id}/note', [Admin\OrderController::class, 'saveNote'], ['admin:orders']);
    $router->post('/orders/{id}/resend', [Admin\OrderController::class, 'resendConfirmation'], ['admin:orders']);
    $router->get('/payments', [Admin\PaymentController::class, 'index'], ['admin:payments']);
    $router->get('/payments/logs', [Admin\PaymentController::class, 'logs'], ['admin:payments']);
    $router->get('/customers', [Admin\CustomerController::class, 'index'], ['admin:customers']);
    $router->get('/customers/{id}', [Admin\CustomerController::class, 'show'], ['admin:customers']);
    $router->post('/customers/{id}/roles', [Admin\CustomerController::class, 'updateRoles'], ['admin:*']);
    $router->post('/customers/{id}/status', [Admin\CustomerController::class, 'updateStatus'], ['admin:customers']);

    // Finance — the treasurer's section
    $router->get('/finance', [Admin\FinanceController::class, 'overview'], ['admin:finance']);
    $router->get('/finance/income', [Admin\FinanceController::class, 'income'], ['admin:finance']);
    $router->get('/finance/expenses', [Admin\FinanceController::class, 'expenses'], ['admin:finance']);
    $router->post('/finance/expenses', [Admin\FinanceController::class, 'saveExpense'], ['admin:finance']);
    $router->post('/finance/expenses/{id}/delete', [Admin\FinanceController::class, 'deleteExpense'], ['admin:finance']);
    $router->get('/finance/budget', [Admin\FinanceController::class, 'budget'], ['admin:finance']);
    $router->post('/finance/budget', [Admin\FinanceController::class, 'saveBudgetLine'], ['admin:finance']);
    $router->post('/finance/budget/{id}/delete', [Admin\FinanceController::class, 'deleteBudgetLine'], ['admin:finance']);
    $router->get('/finance/refunds', [Admin\FinanceController::class, 'refunds'], ['admin:finance']);
    $router->get('/finance/reconciliation', [Admin\FinanceController::class, 'reconciliation'], ['admin:finance']);
    $router->post('/finance/reconciliation', [Admin\FinanceController::class, 'saveReconciliation'], ['admin:finance']);
    $router->post('/finance/reconciliation/{id}/delete', [Admin\FinanceController::class, 'deleteReconciliation'], ['admin:finance']);
    $router->post('/orders/{id}/refund', [Admin\FinanceController::class, 'recordRefund'], ['admin:finance']);

    // Shop
    $router->get('/products', [Admin\ProductController::class, 'index'], ['admin:products']);
    $router->get('/products/create', [Admin\ProductController::class, 'create'], ['admin:products']);
    $router->post('/products', [Admin\ProductController::class, 'store'], ['admin:products']);
    $router->get('/products/{id}', [Admin\ProductController::class, 'edit'], ['admin:products']);
    $router->post('/products/{id}', [Admin\ProductController::class, 'update'], ['admin:products']);
    $router->post('/products/{id}/delete', [Admin\ProductController::class, 'destroy'], ['admin:products']);
    $router->post('/products/{id}/variants', [Admin\ProductController::class, 'saveVariant'], ['admin:products']);
    $router->post('/products/{id}/variants/{variantId}/delete', [Admin\ProductController::class, 'deleteVariant'], ['admin:products']);
    $router->post('/products/{id}/stock', [Admin\ProductController::class, 'adjustStock'], ['admin:products']);
    $router->get('/coupons', [Admin\CouponController::class, 'index'], ['admin:coupons']);
    $router->post('/coupons', [Admin\CouponController::class, 'store'], ['admin:coupons']);
    $router->post('/coupons/{id}/delete', [Admin\CouponController::class, 'destroy'], ['admin:coupons']);

    // Accommodation
    $router->get('/rooms', [Admin\RoomController::class, 'index'], ['admin:rooms']);
    $router->get('/rooms/create', [Admin\RoomController::class, 'create'], ['admin:rooms']);
    $router->post('/rooms', [Admin\RoomController::class, 'store'], ['admin:rooms']);
    $router->get('/rooms/{id}', [Admin\RoomController::class, 'edit'], ['admin:rooms']);
    $router->post('/rooms/{id}', [Admin\RoomController::class, 'update'], ['admin:rooms']);
    $router->post('/rooms/{id}/units', [Admin\RoomController::class, 'generateUnits'], ['admin:rooms']);
    $router->post('/rooms/{id}/rates', [Admin\RoomController::class, 'saveRates'], ['admin:rooms']);
    $router->post('/rooms/units/{unitId}/toggle', [Admin\RoomController::class, 'toggleUnit'], ['admin:rooms']);
    $router->post('/rooms/beds/{bedId}/toggle', [Admin\RoomController::class, 'toggleBed'], ['admin:rooms']);
    $router->get('/bookings', [Admin\BookingController::class, 'index'], ['admin:bookings']);
    $router->get('/bookings/board', [Admin\BookingController::class, 'board'], ['admin:bookings']);
    $router->post('/bookings/{id}/status', [Admin\BookingController::class, 'updateStatus'], ['admin:bookings']);
    $router->get('/bookings/holds', [Admin\BookingController::class, 'holds'], ['admin:bookings']);

    // Transport
    $router->get('/transport', [Admin\TransportController::class, 'index'], ['admin:transport']);
    $router->post('/transport/routes', [Admin\TransportController::class, 'saveRoute'], ['admin:transport']);
    $router->post('/transport/routes/{id}/delete', [Admin\TransportController::class, 'deleteRoute'], ['admin:transport']);
    $router->post('/transport/slots', [Admin\TransportController::class, 'saveSlot'], ['admin:transport']);
    $router->post('/transport/slots/{id}/delete', [Admin\TransportController::class, 'deleteSlot'], ['admin:transport']);
    $router->get('/transport/manifest/{slotId}', [Admin\TransportController::class, 'manifest'], ['admin:transport']);
    $router->post('/transport/passenger/{id}/checkin', [Admin\TransportController::class, 'checkIn'], ['admin:transport']);

    // Content
    $router->get('/content', [Admin\ContentController::class, 'index'], ['admin:content']);
    $router->post('/content/banners', [Admin\ContentController::class, 'saveBanner'], ['admin:content']);
    $router->post('/content/banners/{id}/delete', [Admin\ContentController::class, 'deleteBanner'], ['admin:content']);
    $router->post('/content/pages', [Admin\ContentController::class, 'savePage'], ['admin:content']);
    $router->post('/content/programme', [Admin\ContentController::class, 'saveProgramme'], ['admin:content']);
    $router->post('/content/programme/{id}/delete', [Admin\ContentController::class, 'deleteProgramme'], ['admin:content']);
    $router->post('/content/faqs', [Admin\ContentController::class, 'saveFaq'], ['admin:content']);
    $router->post('/content/faqs/{id}/delete', [Admin\ContentController::class, 'deleteFaq'], ['admin:content']);
    $router->post('/content/events', [Admin\ContentController::class, 'saveEvent'], ['admin:content']);
    $router->post('/content/events/{id}/delete', [Admin\ContentController::class, 'deleteEvent'], ['admin:content']);
    $router->get('/gallery', [Admin\GalleryController::class, 'index'], ['admin:gallery']);
    $router->post('/gallery', [Admin\GalleryController::class, 'store'], ['admin:gallery']);
    $router->post('/gallery/{id}/delete', [Admin\GalleryController::class, 'destroy'], ['admin:gallery']);

    // Applications, donations, messages
    $router->get('/applications', [Admin\ApplicationController::class, 'index'], ['admin:dashboard']);
    $router->get('/applications/{id}', [Admin\ApplicationController::class, 'show'], ['admin:dashboard']);
    $router->post('/applications/{id}', [Admin\ApplicationController::class, 'update'], ['admin:dashboard']);
    $router->post('/applications/{id}/email', [Admin\ApplicationController::class, 'email'], ['admin:dashboard']);
    $router->get('/donations', [Admin\DonationController::class, 'index'], ['admin:donations']);
    $router->get('/messages', [Admin\MessageController::class, 'index'], ['admin:dashboard']);
    $router->post('/messages/{id}', [Admin\MessageController::class, 'update'], ['admin:dashboard']);

    // Check-in
    $router->get('/checkin', [Admin\CheckinController::class, 'index'], ['admin:checkin']);
    $router->post('/checkin', [Admin\CheckinController::class, 'lookup'], ['admin:checkin']);
    $router->post('/checkin/{orderId}/confirm', [Admin\CheckinController::class, 'confirm'], ['admin:checkin']);

    // Settings and exports
    $router->get('/settings', [Admin\SettingsController::class, 'index'], ['admin:*']);
    $router->post('/settings', [Admin\SettingsController::class, 'update'], ['admin:*']);
    $router->get('/settings/email-templates', [Admin\SettingsController::class, 'emailTemplates'], ['admin:*']);
    $router->post('/settings/email-templates', [Admin\SettingsController::class, 'saveEmailTemplate'], ['admin:*']);
    $router->get('/settings/diagnostics', [Admin\SettingsController::class, 'diagnostics'], ['admin:*']);
    $router->post('/settings/test-email', [Admin\SettingsController::class, 'sendTestEmail'], ['admin:*']);
    $router->get('/logs', [Admin\SettingsController::class, 'logs'], ['admin:*']);
    $router->get('/export/{dataset}', [Admin\ExportController::class, 'download'], ['admin:exports']);
});

return $router;

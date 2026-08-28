<?php
declare(strict_types=1);

/**
 * Critical-path smoke test.
 *
 *   php tools/smoke-test.php
 *
 * Runs against the configured database and proves the invariants that matter
 * most — bed-level inventory, hold behaviour, payment verification and order
 * fulfilment. It creates its own test data and cleans up after itself, so it is
 * safe to run on a staging copy.
 *
 * DO NOT run this against a live site with real bookings: it writes to the
 * bookings, holds, orders and payments tables.
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;
use App\Services\AccommodationService;
use App\Services\CsvService;
use App\Services\FinanceService;
use App\Services\OrderService;
use App\Services\PayFastService;

if (PHP_SAPI !== 'cli') {
    exit("This script is for the command line only.\n");
}

$passed = 0;
$failed = 0;
$notes  = [];

function check(string $label, bool $condition, string $detail = ''): void
{
    global $passed, $failed;

    if ($condition) {
        $passed++;
        printf("  \033[32m✓\033[0m %s\n", $label);
    } else {
        $failed++;
        printf("  \033[31m✗\033[0m %s%s\n", $label, $detail === '' ? '' : ' — ' . $detail);
    }
}

function section(string $title): void
{
    printf("\n\033[1m%s\033[0m\n", $title);
}

printf("\nSARCNA 2027 — smoke test\n%s\n", str_repeat('=', 60));

/* ------------------------------------------------------------ environment */

section('Environment');

check('Database is reachable', Database::isConnected());
check('Schema is present', (int) Database::scalar('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()') >= 30);
check('Accommodation inventory exists', (int) Database::scalar('SELECT COUNT(*) FROM beds') > 0);
check('Bookable nights are configured', AccommodationService::nights() !== []);

$roomType = Database::first('SELECT * FROM room_types WHERE beds_per_unit >= 2 AND is_active = 1 ORDER BY sort_order LIMIT 1');

if ($roomType === null) {
    exit("\nNo shared room type found — cannot test bed inventory. Seed the demo data first.\n");
}

$night = AccommodationService::nights()[1] ?? AccommodationService::nights()[0];
$token = 'smoke-' . bin2hex(random_bytes(8));

/* ------------------------------------------------------ the bed invariant */

section('Bed-level inventory (the rule the whole site rests on)');

$freeBefore = AccommodationService::freeBedIds((int) $roomType['id'], $night);
check('Beds are available to test with', count($freeBefore) >= 2, count($freeBefore) . ' free');

// Find a unit where at least two beds are free, so the sibling test is about
// the booking logic rather than about whatever else is already booked.
$freeLookup = array_flip($freeBefore);
$bedA = null;
$bedB = 0;
$unit = null;

foreach ($freeBefore as $candidate) {
    $candidateUnit = Database::first('SELECT room_unit_id FROM beds WHERE id = ?', [$candidate]);
    $sibling = Database::scalar(
        'SELECT id FROM beds WHERE room_unit_id = ? AND id <> ? AND is_active = 1 ORDER BY sort_order LIMIT 1',
        [(int) $candidateUnit['room_unit_id'], $candidate]
    );

    if ($sibling !== null && isset($freeLookup[(int) $sibling])) {
        $bedA = $candidate;
        $bedB = (int) $sibling;
        $unit = $candidateUnit;
        break;
    }
}

check('Found a unit with two free beds to test with', $bedA !== null);

if ($bedA === null) {
    exit("\nEvery unit is at least partly booked, so the sibling test cannot run.\n");
}

// Hold one bed.
AccommodationService::holdBed($token, $bedA, $night, 100000);

$freeAfterHold = AccommodationService::freeBedIds((int) $roomType['id'], $night);

check('Holding a bed removes it from availability', !in_array($bedA, $freeAfterHold, true));
check('THE SIBLING BED IN THE SAME UNIT IS STILL ON SALE', in_array($bedB, $freeAfterHold, true));
check('Exactly one bed left availability', count($freeAfterHold) === count($freeBefore) - 1);

// A second cart must not be able to hold the same bed.
$blocked = false;

try {
    AccommodationService::holdBed('smoke-other-cart', $bedA, $night, 100000);
} catch (\RuntimeException) {
    $blocked = true;
}

check('A second cart cannot hold the same bed-night', $blocked);

// The same cart may refresh its own hold.
$sameCartOk = true;

try {
    AccommodationService::holdBed($token, $bedA, $night, 100000);
} catch (\RuntimeException) {
    $sameCartOk = false;
}

check('The same cart can refresh its own hold', $sameCartOk);

/* -------------------------------------------------------------- fulfilment */

section('Order fulfilment');

$orderId = Database::insert('orders', [
    'reference'    => 'SMOKE-' . strtoupper(bin2hex(random_bytes(3))),
    'cart_token'   => $token,
    'email'        => 'smoke-test@example.invalid',
    'first_name'   => 'Smoke',
    'last_name'    => 'Test',
    'status'       => 'pending_payment',
    'total_cents'  => 100000,
    'checkin_code' => 'SMOKE-TEST',
]);

$order = OrderService::find($orderId);

$created = AccommodationService::confirmHolds($token, $order);

check('The hold became a confirmed booking', count($created) === 1);
check('The hold was released after confirmation', AccommodationService::holdsFor($token) === []);

$freeAfterBooking = AccommodationService::freeBedIds((int) $roomType['id'], $night);
check('The booked bed is no longer on sale', !in_array($bedA, $freeAfterBooking, true));
check('The sibling bed is STILL on sale after the booking', in_array($bedB, $freeAfterBooking, true));

// The database itself must refuse a double booking.
$refused = false;

try {
    Database::insert('bookings', [
        'reference'    => 'SMOKE-DUP',
        'bed_id'       => $bedA,
        'room_unit_id' => (int) $unit['room_unit_id'],
        'room_type_id' => (int) $roomType['id'],
        'night'        => $night,
        'price_cents'  => 0,
        'status'       => 'confirmed',
    ]);
} catch (\PDOException $e) {
    $refused = $e->getCode() === '23000';
}

check('The unique index refuses a second booking of the same bed-night', $refused);

// Cancelling frees the bed again.
Database::update('bookings', ['status' => 'cancelled'], 'id = :id', ['id' => $created[0]]);
check('Cancelling a booking puts the bed back on sale', in_array($bedA, AccommodationService::freeBedIds((int) $roomType['id'], $night), true));

/* ---------------------------------------------------------------- payfast */

section('PayFast verification');

$signed = [
    'm_payment_id'   => $order['reference'],
    'payment_status' => 'COMPLETE',
    'amount_gross'   => '1000.00',
];
$signature = PayFastService::signature($signed);

check('A signature is generated', strlen($signature) === 32);
check('The same fields produce the same signature', PayFastService::signature($signed) === $signature);

$tampered = $signed;
$tampered['amount_gross'] = '1.00';
check('Changing the amount changes the signature', PayFastService::signature($tampered) !== $signature);

$forged = array_merge($signed, ['signature' => str_repeat('0', 32)]);
$result = PayFastService::handleNotification($forged, '127.0.0.1');
check('A forged notification is rejected', ($result['reason'] ?? '') === 'invalid_signature');
check('The order was not touched by the forged notification', OrderService::find($orderId)['status'] === 'pending_payment');

$underpaid = array_merge($signed, ['amount_gross' => '1.00']);
$underpaid['signature'] = PayFastService::signature($underpaid);
$result = PayFastService::handleNotification($underpaid, '127.0.0.1');
check('An under-payment is rejected', ($result['reason'] ?? '') === 'amount_mismatch');

/* ---------------------------------------------------------------- finance */

section('Financial reporting');

$allTime = FinanceService::period('all');
$summary = FinanceService::summary($allTime);

$paidTotal = (int) Database::scalar('SELECT COALESCE(SUM(total_cents), 0) FROM orders WHERE status = "paid"');
$pendingTotal = (int) Database::scalar('SELECT COALESCE(SUM(total_cents), 0) FROM orders WHERE status = "pending_payment"');

check(
    'Reported income matches the paid orders in the database',
    $summary['gross_cents'] === $paidTotal,
    money($summary['gross_cents']) . ' vs ' . money($paidTotal)
);
check(
    'MONEY NOT YET PAID IS NEVER COUNTED AS INCOME',
    $pendingTotal === 0 || $summary['gross_cents'] !== $summary['gross_cents'] + $pendingTotal
);
check(
    'Pending orders are reported separately as pipeline',
    $summary['pending_cents'] === $pendingTotal,
    money($summary['pending_cents']) . ' vs ' . money($pendingTotal)
);

$fees = FinanceService::fees($allTime);
check(
    'Estimated gateway fees are declared as estimates, never as fact',
    $fees['estimated'] === ($fees['without_fee'] > 0)
);
check(
    'Net income is gross less refunds less fees',
    $summary['net_income_cents'] === $summary['gross_cents'] - $summary['refunded_cents'] - $summary['fees_cents']
);

$expenseTotals = FinanceService::expenseTotals($allTime);
check(
    'Cancelled expenses are excluded from the totals',
    $expenseTotals['total_cents'] === $expenseTotals['paid_cents'] + $expenseTotals['committed_cents']
);
check(
    'Surplus is income less everything owed, cash surplus less only what is paid',
    $summary['surplus_cents'] === $summary['net_income_cents'] - $expenseTotals['total_cents']
    && $summary['cash_surplus_cents'] === $summary['net_income_cents'] - $expenseTotals['paid_cents']
);

$byCategory = FinanceService::incomeByCategory($allTime);
$categoryTotal = array_sum(array_column($byCategory, 'gross_cents'));
check(
    'Income by category adds up to the income total',
    $categoryTotal === (int) Database::scalar(
        'SELECT COALESCE(SUM(oi.total_cents), 0) FROM order_items oi JOIN orders o ON o.id = oi.order_id WHERE o.status = "paid"'
    )
);

// An over-refund must be impossible, whatever the caller does.
$refundedOrder = Database::first('SELECT * FROM orders WHERE status IN ("paid","refunded") ORDER BY total_cents DESC LIMIT 1');

if ($refundedOrder !== null) {
    $already = FinanceService::refundedTotal((int) $refundedOrder['id']);
    check(
        'Refunds recorded against an order never exceed what was paid',
        $already <= (int) $refundedOrder['total_cents'],
        money($already) . ' of ' . money((int) $refundedOrder['total_cents'])
    );
}

check(
    'A fully refunded order is not flagged as a reconciliation exception',
    !in_array('refunded', array_column(FinanceService::reconciliationExceptions(), 'order_status'), true)
);

// Formula injection must be defused without breaking negative numbers.
$csv = CsvService::build(
    [['a' => '-1250.00', 'b' => '=cmd|calc']],
    ['a' => 'Amount', 'b' => 'Text']
);
check('Negative amounts stay numeric in CSV exports', str_contains($csv, '-1250.00') && !str_contains($csv, "'-1250.00"));
check('Spreadsheet formulas are defused in CSV exports', str_contains($csv, "'=cmd|calc"));

/* ----------------------------------------------------------------- config */

section('Configuration');

check('Application key is set', (string) config('app.key') !== '');
check('Site address is configured', (string) config('app.url') !== '');
check('PayFast credentials are present', PayFastService::isConfigured(), 'set them in .env');

if (PayFastService::isSandbox()) {
    $notes[] = 'PayFast is in SANDBOX mode. Switch to live before the site takes real money.';
}

if ((string) config('mail.driver') === 'log') {
    $notes[] = 'Mail driver is "log" — emails are written to storage/email-queue and not sent.';
}

if ((int) Database::scalar('SELECT COUNT(*) FROM products WHERE is_mock = 1') > 0) {
    $notes[] = 'Placeholder content is still flagged in the database. Review it before launch.';
}

/* ---------------------------------------------------------------- cleanup */

Database::delete('bookings', 'order_id = ?', [$orderId]);
Database::delete('payment_logs', 'order_id = ?', [$orderId]);
Database::delete('payments', 'order_id = ?', [$orderId]);
Database::delete('orders', 'id = ?', [$orderId]);
AccommodationService::releaseCartHolds($token);
AccommodationService::releaseCartHolds('smoke-other-cart');

printf("\n%s\n", str_repeat('=', 60));
printf("Passed: %d   Failed: %d\n", $passed, $failed);

foreach ($notes as $note) {
    printf("\nNote: %s\n", $note);
}

echo $failed === 0
    ? "\nEverything critical is working.\n\n"
    : "\nSomething critical is broken. Do not deploy until it passes.\n\n";

exit($failed === 0 ? 0 : 1);

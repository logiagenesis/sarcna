<?php
declare(strict_types=1);

/**
 * The full site audit.
 *
 *   php tools/audit.php [base-url]
 *
 * One command that walks the whole checklist: every public route, every admin
 * screen, every CSV export, every form endpoint, the booking invariants, the
 * payment rules and the finance arithmetic — and prints PASS or FAIL for each
 * line with a summary at the end. Exit code 0 only when everything passes.
 *
 * Run it before every deploy. It needs a running site (default
 * http://127.0.0.1:8000) with the admin password in AUDIT_ADMIN_PASSWORD or
 * passed interactively.
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;

if (PHP_SAPI !== 'cli') {
    exit("Command line only.\n");
}

$base    = rtrim($argv[1] ?? 'http://127.0.0.1:8000', '/');
$results = [];
$cookies = tempnam(sys_get_temp_dir(), 'audit-cookies-');
$stub    = null;

/**
 * PayFast's server-to-server confirmation is a third-party call that a test
 * machine legitimately cannot make. When payfast.validate_url points at a
 * local address, the audit starts that stub itself so the whole notification
 * pipeline — signature, order, amount, source, confirmation — runs for real
 * against our own code. Everything else about the payment path is genuine.
 */
$validateUrl = (string) \App\Core\Config::get('payfast.validate_url', '');

if ($validateUrl !== '' && preg_match('#^http://(127\.0\.0\.1|localhost):(\d+)#', $validateUrl, $m) === 1) {
    $probe = @fsockopen($m[1], (int) $m[2], $errno, $errstr, 1);

    if ($probe === false) {
        $stubFile = sys_get_temp_dir() . '/audit-payfast-stub.php';
        file_put_contents($stubFile, "<?php echo 'VALID';\n");

        $stub = proc_open(
            sprintf('exec php -S %s:%d %s', $m[1], (int) $m[2], escapeshellarg($stubFile)),
            [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']],
            $pipes
        );

        usleep(600000);
    } else {
        fclose($probe);
    }
}

function record(string $section, string $label, bool $pass, string $detail = ''): void
{
    global $results;

    $results[] = ['section' => $section, 'label' => $label, 'pass' => $pass, 'detail' => $detail];
    printf("  %s %s%s\n", $pass ? "\033[32mPASS\033[0m" : "\033[31mFAIL\033[0m", $label, $detail !== '' ? " — {$detail}" : '');
}

function fetch(string $url, array $post = []): array
{
    global $cookies;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEJAR      => $cookies,
        CURLOPT_COOKIEFILE     => $cookies,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_PROXY          => '',
    ]);

    if ($post !== []) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    }

    $body   = (string) curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $redirect = (string) curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    curl_close($ch);

    return [$status, $body, $redirect];
}

function token(string $body): string
{
    return preg_match('/name="_token" value="([^"]+)"/', $body, $m) === 1 ? $m[1] : '';
}

function section(string $title): void
{
    printf("\n\033[1m%s\033[0m\n", $title);
}

/**
 * Sign the current session out and prove it. The logout form only exists on
 * pages behind a login, so the token has to come from one of those — the
 * public home page carries no CSRF field.
 */
function signOut(string $base): bool
{
    foreach (['/admin', '/account', '/cart'] as $path) {
        [, $body] = fetch($base . $path);
        $token    = token($body);

        if ($token !== '') {
            fetch($base . '/logout', ['_token' => $token]);

            break;
        }
    }

    [$status] = fetch($base . '/admin');

    return $status !== 200;
}

printf("\nSARCNA 2027 — full site audit against %s\n%s\n", $base, str_repeat('=', 64));

/* --------------------------------------------------- 1. public pages */

section('1. Every public page');

$publicPages = [
    '/', '/convention', '/programme', '/venue', '/venue/history', '/accommodation',
    '/transport', '/shop', '/shop/registration', '/shop/merchandise', '/donations',
    '/gallery', '/faq', '/contact', '/service', '/login', '/register',
    '/privacy-policy', '/terms', '/refund-policy', '/accommodation-terms',
    '/sitemap.xml', '/robots.txt',
];

foreach ($publicPages as $path) {
    [$status] = fetch($base . $path);
    record('Public pages', $path, $status === 200, "HTTP {$status}");
}

// Every active room type, route and product detail page.
foreach (Database::select('SELECT slug FROM room_types WHERE is_active = 1') as $row) {
    [$status] = fetch($base . '/accommodation/' . $row['slug']);
    record('Public pages', '/accommodation/' . $row['slug'], $status === 200, "HTTP {$status}");
}

foreach (Database::select('SELECT slug FROM transport_routes WHERE is_active = 1') as $row) {
    [$status] = fetch($base . '/transport/' . $row['slug']);
    record('Public pages', '/transport/' . $row['slug'], $status === 200, "HTTP {$status}");
}

foreach (Database::select('SELECT slug FROM products WHERE is_active = 1') as $row) {
    [$status] = fetch($base . '/shop/' . $row['slug']);
    record('Public pages', '/shop/' . $row['slug'], $status === 200, "HTTP {$status}");
}

/* ------------------------------------------- 2. the customer journey */

section('2. The customer journey (a real order, through the real forms)');

$email = 'audit-' . time() . '@example.invalid';

[, $body] = fetch($base . '/register');
[$status, , $redirect] = fetch($base . '/register', [
    '_token' => token($body), 'first_name' => 'Audit', 'last_name' => 'Runner',
    'email' => $email, 'phone' => '0821234567',
    'password' => 'AuditRun2027!', 'password_confirmation' => 'AuditRun2027!', 'terms' => '1',
]);
record('Journey', 'Create an account', $status === 302 && !str_contains($redirect, 'register'), $redirect);

$product = Database::first('SELECT slug FROM products WHERE type = "registration" AND is_active = 1 LIMIT 1');
[, $body] = fetch($base . '/shop/' . $product['slug']);
[$status] = fetch($base . '/shop/' . $product['slug'] . '/add', [
    '_token' => token($body), 'attendee_name' => 'Audit Runner',
]);
record('Journey', 'Add a registration to the cart', $status === 302);

$roomType = Database::first('SELECT slug FROM room_types WHERE is_active = 1 ORDER BY sort_order LIMIT 1');
[, $body] = fetch($base . '/accommodation/' . $roomType['slug']);
preg_match('/name="nights\[\]" value="([^"]+)"/', $body, $m);
[$status, , $redirect] = fetch($base . '/accommodation/' . $roomType['slug'] . '/book', [
    '_token' => token($body), 'mode' => 'bed', 'beds' => '1',
    'nights' => [$m[1] ?? ''], 'guest_name' => 'Audit Runner',
]);
record('Journey', 'Hold a bed', $status === 302 && str_contains($redirect, '/cart'), $redirect);

$liveHolds = (int) Database::scalar('SELECT COUNT(*) FROM booking_holds WHERE expires_at > NOW()');
record('Journey', 'The bed is actually held (15-minute hold written)', $liveHolds >= 1, "{$liveHolds} live hold(s)");

$route = Database::first('SELECT slug FROM transport_routes WHERE is_active = 1 AND requires_flight_number = 0 LIMIT 1');
[, $body] = fetch($base . '/transport/' . $route['slug']);
preg_match('/<option value="(\d+)"/', $body, $m);
[$status, , $redirect] = fetch($base . '/transport/' . $route['slug'] . '/book', [
    '_token' => token($body), 'slot_id' => $m[1] ?? '', 'seats' => '1',
    'passenger_name' => 'Audit Runner', 'phone' => '0821234567', 'email' => $email, 'luggage_count' => '1',
]);
record('Journey', 'Book a shuttle seat', $status === 302 && str_contains($redirect, '/cart'), $redirect);

[, $body] = fetch($base . '/checkout');
record('Journey', 'Checkout page renders with the order form', str_contains($body, 'name="first_name"'));
record('Journey', 'Flight & car-hire buttons on checkout (and only there)', substr_count($body, 'travel-btn') === 2);

[$status, , $redirect] = fetch($base . '/checkout', [
    '_token' => token($body), 'first_name' => 'Audit', 'last_name' => 'Runner',
    'email' => $email, 'phone' => '0821234567', 'terms' => '1',
]);
$reference = '';
if (preg_match('#/checkout/pay/([A-Z0-9-]+)#', $redirect, $m) === 1) {
    $reference = $m[1];
}
record('Journey', 'Order placed, redirected to the payment page', $reference !== '', $reference);

[, $body] = fetch($base . '/checkout/pay/' . $reference);
record('Journey', 'PayFast handoff form present and signed', str_contains($body, 'payfast.co.za') && str_contains($body, 'signature'));

/* ------------------------------------------------- 3. payment rules */

section('3. Payment rules (the ones that protect the money)');

$order = Database::first('SELECT * FROM orders WHERE reference = ?', [$reference]);

[$status] = fetch($base . '/payment/success?reference=' . $reference);
$fresh = Database::first('SELECT status FROM orders WHERE reference = ?', [$reference]);
record('Payments', 'Landing on the success URL does NOT mark the order paid', $fresh['status'] === 'pending_payment', 'status stayed ' . $fresh['status']);

$forged = [
    'm_payment_id' => $reference, 'payment_status' => 'COMPLETE',
    'amount_gross' => number_format($order['total_cents'] / 100, 2, '.', ''),
    'signature'    => str_repeat('0', 32),
];
fetch($base . '/payment/notify', $forged);
$fresh = Database::first('SELECT status FROM orders WHERE reference = ?', [$reference]);
record('Payments', 'A forged signature is rejected', $fresh['status'] === 'pending_payment');

$fields = [
    'm_payment_id' => $reference, 'pf_payment_id' => (string) random_int(1000000, 9999999),
    'payment_status' => 'COMPLETE',
    'amount_gross' => number_format($order['total_cents'] / 100, 2, '.', ''),
    'email_address' => $email,
];
$fields['signature'] = \App\Services\PayFastService::signature($fields);
fetch($base . '/payment/notify', $fields);
$fresh = Database::first('SELECT status FROM orders WHERE reference = ?', [$reference]);
$paid  = $fresh['status'] === 'paid';
record('Payments', 'A correctly signed notification marks the order paid', $paid, 'status ' . $fresh['status']
    . ($paid ? '' : ' (needs PAYFAST_VALIDATE_URL stub + payfast_skip_ip_check on a machine that cannot reach PayFast)'));

if ($paid) {
    $bookings = (int) Database::scalar('SELECT COUNT(*) FROM bookings WHERE order_id = ? AND status = "confirmed"', [(int) $order['id']]);
    record('Payments', 'Fulfilment allocated the bed', $bookings >= 1, "{$bookings} booking(s)");

    $passengers = (int) Database::scalar('SELECT COUNT(*) FROM transport_bookings WHERE order_id = ?', [(int) $order['id']]);
    record('Payments', 'Fulfilment wrote the shuttle passenger', $passengers >= 1);
}

/* ------------------------------------------------ 4. bed invariants */

section('4. The bed rules (straight from the smoke test)');

exec('php ' . escapeshellarg(__DIR__ . '/smoke-test.php') . ' 2>&1', $smokeOut, $smokeCode);
$smokeSummary = '';

foreach ($smokeOut as $line) {
    if (str_contains($line, 'Passed:')) {
        $smokeSummary = trim(preg_replace('/\033\[[0-9;]*m/', '', $line));
    }
}

record('Bed rules', 'Smoke test (38 checks: bed invariant, holds, forged ITN, finance arithmetic, CSV safety)', $smokeCode === 0, $smokeSummary);

/* ---------------------------------------------------- 5. the admin */

section('5. Every admin screen and every export');

$adminPassword = getenv('AUDIT_ADMIN_PASSWORD') ?: '';

if ($adminPassword === '') {
    record('Admin', 'Admin checks', false, 'set AUDIT_ADMIN_PASSWORD to run the admin half');
} else {
    // The journey above signed us in as a delegate. Sign out first: /login is
    // guest-only, so an already-authenticated session cannot reach it.
    record('Admin', 'Signing out works, and blocks the admin again', signOut($base));

    [, $body] = fetch($base . '/login');
    [$status, , $redirect] = fetch($base . '/login', [
        '_token' => token($body), 'email' => 'admin@sarcna.org.za', 'password' => $adminPassword,
    ]);
    record('Admin', 'Admin sign-in', str_contains($redirect, '/admin'), $redirect);

    $adminPages = [
        '/admin', '/admin/checkin',
        '/admin/finance', '/admin/finance/income', '/admin/finance/expenses',
        '/admin/finance/budget', '/admin/finance/reconciliation', '/admin/finance/refunds',
        '/admin/orders', '/admin/payments', '/admin/payments/logs', '/admin/donations', '/admin/coupons',
        '/admin/bookings/operations', '/admin/bookings', '/admin/bookings/board',
        '/admin/bookings/run-sheet', '/admin/bookings/holds', '/admin/rooms',
        '/admin/transport', '/admin/products', '/admin/customers', '/admin/applications',
        '/admin/messages', '/admin/content', '/admin/gallery', '/admin/settings',
        '/admin/settings/email-templates', '/admin/settings/diagnostics', '/admin/logs',
    ];

    foreach ($adminPages as $path) {
        [$status] = fetch($base . $path);
        record('Admin', $path, $status === 200, "HTTP {$status}");
    }

    $exports = ['orders', 'order-items', 'attendees', 'bookings', 'rooming-list', 'transport',
                'donations', 'applications', 'messages', 'customers', 'stock', 'payments',
                'expenses', 'refunds', 'budget', 'finance-pack'];

    foreach ($exports as $dataset) {
        [$status, $body] = fetch($base . '/admin/export/' . $dataset);
        record('Exports', $dataset . '.csv', $status === 200 && strlen($body) > 40, "HTTP {$status}, " . strlen($body) . ' bytes');
    }
}

/* ----------------------------------------------------- 6. security */

section('6. Security basics');

// Sign out so these are genuinely anonymous requests.
record('Security', 'Signed out cleanly before the anonymous checks', signOut($base));

foreach (['/admin', '/admin/finance', '/admin/orders', '/admin/bookings/operations'] as $guarded) {
    [$status, , $redirect] = fetch($base . $guarded);
    $blocked = $status !== 200;
    record('Security', "A signed-out visitor cannot reach {$guarded}", $blocked, "HTTP {$status}" . ($redirect !== '' ? ' → ' . $redirect : ''));
}

[$status] = fetch($base . '/checkout', ['first_name' => 'NoToken', 'email' => 'x@example.invalid']);
record('Security', 'A POST without a CSRF token is refused', $status === 419 || $status === 403, "HTTP {$status}");

foreach (['/.env', '/app/Config/config.php', '/database/schema.sql', '/storage/logs', '/.git/config'] as $secret) {
    [$status] = fetch($base . $secret);
    record('Security', "{$secret} is not served over the web", $status !== 200, "HTTP {$status}");
}

/* -------------------------------------------------------- summary */

$pass = count(array_filter($results, static fn (array $r): bool => $r['pass']));
$fail = count($results) - $pass;

printf("\n%s\nTOTAL: %d checks — %d passed, %d failed.\n", str_repeat('=', 64), count($results), $pass, $fail);

@unlink($cookies);

if (is_resource($stub)) {
    proc_terminate($stub);
    proc_close($stub);
}

exit($fail === 0 ? 0 : 1);

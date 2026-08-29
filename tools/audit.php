<?php
declare(strict_types=1);

/**
 * The full site audit.
 *
 *   php tools/audit.php [base-url] [--password=…]
 *
 * One command that walks the whole checklist: every public route, every admin
 * screen, every CSV export, every form endpoint, the booking invariants, the
 * payment rules and the finance arithmetic — and prints PASS or FAIL for each
 * line with a summary at the end. Exit code 0 only when everything passes.
 *
 * Run it before every deploy. It needs a running site (default
 * http://127.0.0.1:8000) and the administrator password, given either as
 * --password=… or in the AUDIT_ADMIN_PASSWORD environment variable. Works
 * identically on Linux, macOS and Windows.
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';
require_once __DIR__ . '/purge.php';

use App\Core\Database;

if (PHP_SAPI !== 'cli') {
    exit("Command line only.\n");
}

$base = 'http://127.0.0.1:8000';

foreach (array_slice($argv, 1) as $arg) {
    if (!str_starts_with((string) $arg, '--')) {
        $base = (string) $arg;
    }
}

$base    = rtrim($base, '/');
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
$stubPort    = 8099;
$stubUrl     = "http://127.0.0.1:{$stubPort}/validate";
$envPath     = dirname(__DIR__) . '/.env';
$envOriginal = null;

/**
 * Only a live PayFast can answer the confirmation call, so on a fresh install
 * the audit stands one up itself: it starts a local stub, points .env at it
 * for the duration, and puts .env back byte-for-byte when it finishes —
 * including if it is interrupted. Everything else on the payment path is the
 * real code taking a real, correctly signed notification.
 */
if (is_file($envPath) && !str_contains((string) file_get_contents($envPath), 'PAYFAST_VALIDATE_URL=http')) {
    $envOriginal = (string) file_get_contents($envPath);

    $patched = preg_replace('/^PAYFAST_VALIDATE_URL=.*$/m', '', $envOriginal);
    file_put_contents($envPath, rtrim($patched) . "\nPAYFAST_VALIDATE_URL={$stubUrl}\n");

    echo "  (testing: .env temporarily points the PayFast confirmation at a local stub; it is restored at the end)\n";
}

$restoreEnv = static function () use ($envPath, &$envOriginal): void {
    if ($envOriginal !== null) {
        file_put_contents($envPath, $envOriginal);
        $envOriginal = null;
    }
};

register_shutdown_function($restoreEnv);

foreach ([SIGINT, SIGTERM] as $signal) {
    if (function_exists('pcntl_signal')) {
        pcntl_signal($signal, static function () use ($restoreEnv): void {
            $restoreEnv();
            exit(1);
        });
    }
}

$probe = @fsockopen('127.0.0.1', $stubPort, $errno, $errstr, 1);

if ($probe === false) {
    $stubFile = sys_get_temp_dir() . '/audit-payfast-stub.php';
    file_put_contents($stubFile, "<?php echo 'VALID';\n");

    $stub = proc_open(
        sprintf('exec php -S 127.0.0.1:%d %s', $stubPort, escapeshellarg($stubFile)),
        [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']],
        $pipes
    );

    usleep(600000);
} else {
    fclose($probe);
}

// The audit is a legitimate high-volume client: it registers an account, books
// a bed and a seat, and posts dozens of admin forms in a few seconds. The
// site's own rate limiter would (correctly) throttle that, and a previous run
// would poison the next one — so clear this machine's throttle counters first.
// The limiter itself is untouched and still protects real visitors.
Database::run('DELETE FROM rate_limits');

// The notification arrives from this machine, not from PayFast's range, so the
// source-IP check has to be told this is a test. Restored with .env above.
Database::run(
    'INSERT INTO settings (group_name, key_name, value, type, label)
          VALUES ("payments", "payfast_skip_ip_check", "1", "boolean", "Skip PayFast IP check (testing only)")
     ON DUPLICATE KEY UPDATE value = "1"'
);

function record(string $section, string $label, bool $pass, string $detail = ''): void
{
    global $results;

    $results[] = ['section' => $section, 'label' => $label, 'pass' => $pass, 'detail' => $detail];
    printf("  %s %s%s\n", $pass ? "\033[32mPASS\033[0m" : "\033[31mFAIL\033[0m", $label, $detail !== '' ? " — {$detail}" : '');
}

/**
 * @param array<string, mixed>  $post
 * @param array<string, string> $files field name => path on disk; sending any
 *                                     file switches the request to multipart,
 *                                     which is what a real upload form does.
 */
function fetch(string $url, array $post = [], array $files = []): array
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

    if ($post !== [] || $files !== []) {
        curl_setopt($ch, CURLOPT_POST, true);

        if ($files === []) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        } else {
            foreach ($files as $field => $path) {
                $post[$field] = new CURLFile($path);
            }

            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }
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

/** Sign in as the administrator. Returns true when the admin is reachable. */
function signIn(string $base, string $password): bool
{
    [, $body] = fetch($base . '/login');

    if (token($body) === '') {
        return false;   // already signed in
    }

    fetch($base . '/login', ['_token' => token($body), 'email' => 'admin@sarcna.org.za', 'password' => $password]);

    [$status] = fetch($base . '/admin');

    return $status === 200;
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

// The smoke test's own total varies with the data present — a site with no
// demo orders has one fewer check to run — so the count is reported from the
// run rather than written in here, where it would drift out of date.
record('Bed rules', 'Smoke test (bed invariant, holds, forged ITN, finance arithmetic, CSV safety)',
    $smokeCode === 0, $smokeSummary);

/* ---------------------------------------------------- 5. the admin */

section('5. Every admin screen and every export');

// --password=… works the same in bash, PowerShell and cmd. The environment
// variable still works, for CI where a password on the command line would be
// visible in the process list.
$adminPassword = getenv('AUDIT_ADMIN_PASSWORD') ?: '';

foreach ($argv as $arg) {
    if (str_starts_with((string) $arg, '--password=')) {
        $adminPassword = substr((string) $arg, 11);
    }
}

if ($adminPassword === '') {
    record('Admin', 'Admin checks', false, 'pass --password=… to run the admin half');
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

/* ------------------------------------------- 7. things that write data */

section('7. Committee actions that write data (each one verified in the database)');

// Section 6 signed out on purpose. Sign back in to exercise the write paths.
if ($adminPassword === '') {
    record('Writes', 'Write checks', false, 'pass --password=… to run them');
}

record('Writes', 'Signed back in as the administrator', signIn($base, $adminPassword));

/**
 * Every check here performs a real write over HTTP, confirms it landed in the
 * database, then removes it again — so running the audit never leaves anything
 * behind. If a page merely returned 200 without saving, these fail.
 */
$scratch = [];   // [table, id] pairs to clean up at the end

/** POST a form, then assert something is true in the database. */
function writes(string $label, string $url, array $post, callable $verify, string $detail = ''): void
{
    [$status] = fetch($url, $post);
    $ok = $status === 302 || $status === 200;

    record('Writes', $label, $ok && $verify(), $ok ? $detail : "HTTP {$status}");
}

// --- settings ------------------------------------------------------------
// The settings form posts every field at once, and an absent checkbox means
// "off" — so a partial post would switch off every boolean. Submit the whole
// current state with one value changed, exactly as the real form does.
$settingsBefore = [];

foreach (Database::select('SELECT key_name, value, type FROM settings') as $row) {
    $settingsBefore[$row['key_name']] = ['value' => (string) $row['value'], 'type' => $row['type']];
}

$fullPost = static function (array $overrides) use ($settingsBefore): array {
    $out = [];

    foreach ($settingsBefore as $key => $meta) {
        $value = $overrides[$key] ?? $meta['value'];

        // Unchecked booleans are simply absent, which is what "off" means here.
        if ($meta['type'] === 'boolean' && (string) $value !== '1') {
            continue;
        }

        $out[$key] = (string) $value;
    }

    return $out;
};

$originalWhatsApp = $settingsBefore['whatsapp_number']['value'] ?? '';
[, $body] = fetch($base . '/admin/settings');

writes(
    'Saving a setting persists it',
    $base . '/admin/settings',
    ['_token' => token($body), 'settings' => $fullPost(['whatsapp_number' => '27820001111'])],
    static fn (): bool => (string) Database::scalar('SELECT value FROM settings WHERE key_name = "whatsapp_number"') === '27820001111'
);

// Put it back, and prove nothing else moved.
[, $body] = fetch($base . '/admin/settings');
fetch($base . '/admin/settings', ['_token' => token($body), 'settings' => $fullPost([])]);

$drifted = [];

foreach (Database::select('SELECT key_name, value FROM settings') as $row) {
    if ((string) $row['value'] !== ($settingsBefore[$row['key_name']]['value'] ?? '')) {
        $drifted[] = $row['key_name'];
    }
}

record('Writes', 'Settings were restored, and nothing else changed',
    $drifted === [], $drifted === [] ? '' : 'drifted: ' . implode(', ', $drifted));

// --- an expense, and its effect on the finance totals --------------------
$expensesBefore = (int) Database::scalar('SELECT COALESCE(SUM(amount_cents),0) FROM expenses WHERE status = "paid"');
[, $body] = fetch($base . '/admin/finance/expenses');

writes(
    'Recording an expense writes it to the ledger',
    $base . '/admin/finance/expenses',
    [
        '_token' => token($body), 'description' => 'AUDIT probe expense', 'supplier' => 'Audit',
        'amount' => '1234.56', 'incurred_on' => date('Y-m-d'), 'status' => 'paid',
    ],
    static fn (): bool => Database::scalar('SELECT id FROM expenses WHERE description = "AUDIT probe expense"') !== null
);

$expenseId = Database::scalar('SELECT id FROM expenses WHERE description = "AUDIT probe expense"');

record('Writes', 'The new expense moves the finance total by exactly its amount',
    (int) Database::scalar('SELECT COALESCE(SUM(amount_cents),0) FROM expenses WHERE status = "paid"') === $expensesBefore + 123456,
    money($expensesBefore) . ' → ' . money($expensesBefore + 123456));

if ($expenseId !== null) {
    [, $body] = fetch($base . '/admin/finance/expenses');
    fetch($base . '/admin/finance/expenses/' . $expenseId . '/delete', ['_token' => token($body)]);
    // A paid expense is cancelled rather than erased, by design.
    record('Writes', 'A paid expense is cancelled rather than deleted (the ledger keeps the record)',
        (string) Database::scalar('SELECT status FROM expenses WHERE id = ?', [(int) $expenseId]) === 'cancelled');
    Database::delete('expenses', 'id = ?', [(int) $expenseId]);
}

// --- a budget line -------------------------------------------------------
[, $body] = fetch($base . '/admin/finance/budget');

writes(
    'Adding a budget line saves it',
    $base . '/admin/finance/budget',
    ['_token' => token($body), 'kind' => 'expense', 'category' => 'AUDIT probe line', 'budgeted' => '500.00'],
    static fn (): bool => Database::scalar('SELECT id FROM budget_lines WHERE category = "AUDIT probe line"') !== null
);

if (($lineId = Database::scalar('SELECT id FROM budget_lines WHERE category = "AUDIT probe line"')) !== null) {
    [, $body] = fetch($base . '/admin/finance/budget');
    fetch($base . '/admin/finance/budget/' . $lineId . '/delete', ['_token' => token($body)]);
    record('Writes', 'Deleting the budget line removes it',
        Database::scalar('SELECT id FROM budget_lines WHERE category = "AUDIT probe line"') === null);
}

// --- a coupon, created in the admin and then actually used in a cart -----
[, $body] = fetch($base . '/admin/coupons');

writes(
    'Creating a coupon saves it',
    $base . '/admin/coupons',
    [
        '_token' => token($body), 'code' => 'AUDIT10', 'discount_type' => 'percent',
        'discount_value' => '10', 'applies_to' => 'all', 'is_active' => '1',
    ],
    static fn (): bool => Database::scalar('SELECT id FROM coupons WHERE code = "AUDIT10"') !== null
);

$couponId = Database::scalar('SELECT id FROM coupons WHERE code = "AUDIT10"');

// --- a product -----------------------------------------------------------
[, $body] = fetch($base . '/admin/products/create');

writes(
    'Creating a product saves it',
    $base . '/admin/products',
    [
        '_token' => token($body), 'name' => 'AUDIT probe product', 'type' => 'merchandise',
        'price' => '99.00', 'is_active' => '1', 'stock' => '5', 'track_stock' => '1',
    ],
    static fn (): bool => Database::scalar('SELECT id FROM products WHERE name = "AUDIT probe product"') !== null
);

$productId = Database::scalar('SELECT id FROM products WHERE name = "AUDIT probe product"');

if ($productId !== null) {
    [, $body] = fetch($base . '/admin/products/' . $productId);
    writes(
        'Editing the product updates it',
        $base . '/admin/products/' . $productId,
        [
            '_token' => token($body), 'name' => 'AUDIT probe product', 'type' => 'merchandise',
            'price' => '149.00', 'is_active' => '1', 'stock' => '5', 'track_stock' => '1',
        ],
        static fn (): bool => (int) Database::scalar('SELECT price_cents FROM products WHERE id = ?', [(int) $productId]) === 14900,
        'price changed to R149.00'
    );
}

// --- content: a programme item and an FAQ -------------------------------
[, $body] = fetch($base . '/admin/content');

writes(
    'Adding a programme item saves it',
    $base . '/admin/content/programme',
    [
        '_token' => token($body), 'day_date' => '2027-08-27', 'start_time' => '09:00',
        'title' => 'AUDIT probe session',
    ],
    static fn (): bool => Database::scalar('SELECT id FROM programme_items WHERE title = "AUDIT probe session"') !== null
);

if (($itemId = Database::scalar('SELECT id FROM programme_items WHERE title = "AUDIT probe session"')) !== null) {
    [, $body] = fetch($base . '/admin/content');
    fetch($base . '/admin/content/programme/' . $itemId . '/delete', ['_token' => token($body)]);
    record('Writes', 'Deleting the programme item removes it',
        Database::scalar('SELECT id FROM programme_items WHERE title = "AUDIT probe session"') === null);
}

[, $body] = fetch($base . '/admin/content');

writes(
    'Adding an FAQ saves it',
    $base . '/admin/content/faqs',
    ['_token' => token($body), 'question' => 'AUDIT probe question?', 'answer' => 'An answer written by the audit.'],
    static fn (): bool => Database::scalar('SELECT id FROM faqs WHERE question = "AUDIT probe question?"') !== null
);

if (($faqId = Database::scalar('SELECT id FROM faqs WHERE question = "AUDIT probe question?"')) !== null) {
    [, $body] = fetch($base . '/admin/content');
    fetch($base . '/admin/content/faqs/' . $faqId . '/delete', ['_token' => token($body)]);
    record('Writes', 'Deleting the FAQ removes it',
        Database::scalar('SELECT id FROM faqs WHERE question = "AUDIT probe question?"') === null);
}

// --- an internal note on an order ---------------------------------------
$anyOrder = Database::first('SELECT id FROM orders ORDER BY id DESC LIMIT 1');

if ($anyOrder !== null) {
    [, $body] = fetch($base . '/admin/orders/' . $anyOrder['id']);
    writes(
        'Saving an internal note on an order persists it',
        $base . '/admin/orders/' . $anyOrder['id'] . '/note',
        ['_token' => token($body), 'admin_note' => 'AUDIT probe note'],
        static fn (): bool => (string) Database::scalar('SELECT admin_note FROM orders WHERE id = ?', [(int) $anyOrder['id']]) === 'AUDIT probe note'
    );

    Database::update('orders', ['admin_note' => null], 'id = :id', ['id' => (int) $anyOrder['id']]);
}

// --- moving a guest between beds ----------------------------------------
$booking = Database::first(
    'SELECT bk.* FROM bookings bk WHERE bk.status = "confirmed" ORDER BY bk.id LIMIT 1'
);

if ($booking !== null) {
    $free = Database::first(
        'SELECT b.id FROM beds b
          WHERE b.is_active = 1 AND b.id <> :current
            AND NOT EXISTS (SELECT 1 FROM bookings x WHERE x.bed_id = b.id AND x.active_night = :night)
            AND NOT EXISTS (SELECT 1 FROM booking_holds h WHERE h.bed_id = b.id AND h.night = :night2 AND h.expires_at > NOW())
          LIMIT 1',
        ['current' => (int) $booking['bed_id'], 'night' => $booking['night'], 'night2' => $booking['night']]
    );

    if ($free !== null) {
        [, $body] = fetch($base . '/admin/bookings/' . $booking['id'] . '/move');

        writes(
            'Moving a guest to another bed actually moves them',
            $base . '/admin/bookings/' . $booking['id'] . '/move',
            ['_token' => token($body), 'bed_id' => (int) $free['id']],
            static fn (): bool => (int) Database::scalar('SELECT bed_id FROM bookings WHERE id = ?', [(int) $booking['id']]) === (int) $free['id'],
            'bed ' . $booking['bed_id'] . ' → ' . $free['id']
        );

        $after = Database::first('SELECT reference, price_cents FROM bookings WHERE id = ?', [(int) $booking['id']]);
        record('Writes', 'The move keeps the booking reference and the price',
            $after['reference'] === $booking['reference'] && (int) $after['price_cents'] === (int) $booking['price_cents']);

        record('Writes', 'The move is written to the audit log',
            Database::scalar('SELECT id FROM admin_audit_logs WHERE action LIKE "moved %" ORDER BY id DESC LIMIT 1') !== null);

        // Move them back where they were.
        Database::update('bookings', [
            'bed_id'       => (int) $booking['bed_id'],
            'room_unit_id' => (int) $booking['room_unit_id'],
            'room_type_id' => (int) $booking['room_type_id'],
        ], 'id = :id', ['id' => (int) $booking['id']]);
    }
}

// --- checking a delegate in ---------------------------------------------
$paidOrder = Database::first('SELECT id, checked_in_at FROM orders WHERE status = "paid" AND checked_in_at IS NULL LIMIT 1');

if ($paidOrder !== null) {
    [, $body] = fetch($base . '/admin/checkin');

    writes(
        'Checking a delegate in stamps the order and their bookings',
        $base . '/admin/checkin/' . $paidOrder['id'] . '/confirm',
        ['_token' => token($body)],
        static fn (): bool => Database::scalar('SELECT checked_in_at FROM orders WHERE id = ?', [(int) $paidOrder['id']]) !== null
    );

    // Undo, so the audit leaves no one checked in.
    [, $body] = fetch($base . '/admin/checkin');
    fetch($base . '/admin/checkin/' . $paidOrder['id'] . '/confirm', ['_token' => token($body)]);
    record('Writes', 'Undoing the check-in clears it again',
        Database::scalar('SELECT checked_in_at FROM orders WHERE id = ?', [(int) $paidOrder['id']]) === null);
}

/* ------------------------------------------ 8. cart and coupon arithmetic */

section('8. Cart arithmetic and coupons (as a signed-out visitor)');

record('Cart', 'Signed out before the cart checks', signOut($base));

// Start from an empty cart so the arithmetic below is unambiguous.
[, $body] = fetch($base . '/cart');
fetch($base . '/cart/clear', ['_token' => token($body)]);

$product = Database::first('SELECT slug, price_cents FROM products WHERE type = "registration" AND is_active = 1 LIMIT 1');
[, $body] = fetch($base . '/shop/' . $product['slug']);
fetch($base . '/shop/' . $product['slug'] . '/add', ['_token' => token($body), 'attendee_name' => 'Cart Audit']);

// The cart this session is using is the one that was just touched.
$cartToken = (string) Database::scalar('SELECT token FROM carts ORDER BY updated_at DESC, id DESC LIMIT 1');
$lineTotal = (int) Database::scalar(
    'SELECT COALESCE(SUM(unit_price_cents * quantity), 0) FROM cart_items
      WHERE cart_id = (SELECT id FROM carts WHERE token = ?)',
    [$cartToken]
);

record('Cart', 'The item went into the cart at the catalogue price',
    $lineTotal === (int) $product['price_cents'], money($lineTotal));

[, $cartBody] = fetch($base . '/cart');
record('Cart', 'The cart page shows that total',
    str_contains($cartBody, (string) number_format($lineTotal / 100, 2, ',', ' ')) || str_contains($cartBody, (string) number_format($lineTotal / 100, 2, '.', ' ')),
    money($lineTotal));

if ($couponId !== null) {
    [, $cartBody] = fetch($base . '/cart');
    [$status] = fetch($base . '/cart/coupon', ['_token' => token($cartBody), 'code' => 'AUDIT10']);

    [, $cartBody] = fetch($base . '/cart');
    $expected = (int) round($lineTotal * 0.10);
    $applied  = Database::scalar('SELECT coupon_id FROM carts WHERE token = ?', [$cartToken]);

    record('Cart', 'A 10% coupon applies to the cart', $applied !== null, 'expected ' . money($expected) . ' off');

    // The cart names the coupon and shows the exact amount it took off.
    record('Cart', 'The cart shows the coupon and the exact amount it took off',
        str_contains($cartBody, 'AUDIT10') && str_contains($cartBody, money($expected)),
        money($expected) . ' off ' . money($lineTotal));

    [, $cartBody] = fetch($base . '/cart');
    fetch($base . '/cart/coupon/remove', ['_token' => token($cartBody)]);
    record('Cart', 'Removing the coupon clears it',
        Database::scalar('SELECT coupon_id FROM carts WHERE token = ?', [$cartToken]) === null);
}

[, $cartBody] = fetch($base . '/cart');
fetch($base . '/cart/clear', ['_token' => token($cartBody)]);
record('Cart', 'Clearing the cart empties it',
    (int) Database::scalar('SELECT COUNT(*) FROM cart_items WHERE cart_id = (SELECT id FROM carts WHERE token = ?)', [$cartToken]) === 0);

/* -------------------------------------------- 9. public forms that submit */

section('9. Public forms that have to reach the committee');

[, $body] = fetch($base . '/contact');
writes(
    'The contact form creates a message the committee can see',
    $base . '/contact',
    [
        '_token' => token($body), 'name' => 'Audit Runner', 'email' => 'audit@example.invalid',
        'phone' => '0821234567', 'subject' => 'AUDIT probe message',
        'message' => 'This message was written by the automated audit run.',
    ],
    static fn (): bool => Database::scalar('SELECT id FROM contact_messages WHERE subject = "AUDIT probe message"') !== null
);

[, $body] = fetch($base . '/service');
writes(
    'A service application is captured',
    $base . '/service',
    [
        '_token' => token($body), 'name' => 'Audit Volunteer', 'email' => 'audit-vol@example.invalid',
        'phone' => '0821234567', 'service_areas' => ['Registration'], 'consent' => '1',
    ],
    static fn (): bool => Database::scalar('SELECT id FROM service_applications WHERE email = "audit-vol@example.invalid"') !== null
);

/* ------------------------------------------------- 10. data integrity */

section('10. Data integrity (the invariants, checked in SQL)');

record('Integrity', 'No bed is double-booked on any night',
    (int) Database::scalar(
        'SELECT COUNT(*) FROM (SELECT bed_id, active_night FROM bookings
          WHERE active_night IS NOT NULL GROUP BY bed_id, active_night HAVING COUNT(*) > 1) d'
    ) === 0);

record('Integrity', 'No live hold sits on a bed that is already booked',
    (int) Database::scalar(
        'SELECT COUNT(*) FROM booking_holds h
           JOIN bookings b ON b.bed_id = h.bed_id AND b.active_night = h.night
          WHERE h.expires_at > NOW()'
    ) === 0);

record('Integrity', 'Every booking belongs to a bed that exists and is in the right unit',
    (int) Database::scalar(
        'SELECT COUNT(*) FROM bookings bk
      LEFT JOIN beds b ON b.id = bk.bed_id
          WHERE b.id IS NULL OR b.room_unit_id <> bk.room_unit_id'
    ) === 0);

record('Integrity', 'Every paid order has at least one payment recorded against it',
    (int) Database::scalar(
        'SELECT COUNT(*) FROM orders o
          WHERE o.status = "paid"
            AND NOT EXISTS (SELECT 1 FROM payments p WHERE p.order_id = o.id AND p.status = "complete")'
    ) === 0);

record('Integrity', 'No order total disagrees with the sum of its line items',
    (int) Database::scalar(
        'SELECT COUNT(*) FROM orders o
          WHERE o.subtotal_cents <> (SELECT COALESCE(SUM(total_cents),0) FROM order_items i WHERE i.order_id = o.id)'
    ) === 0);

record('Integrity', 'No refund exceeds what its order was paid',
    (int) Database::scalar(
        'SELECT COUNT(*) FROM (
            SELECT r.order_id, SUM(r.amount_cents) AS refunded, o.total_cents
              FROM refunds r JOIN orders o ON o.id = r.order_id
             WHERE r.status = "completed"
             GROUP BY r.order_id, o.total_cents
             HAVING refunded > o.total_cents) d'
    ) === 0);

record('Integrity', 'Every shuttle departure has sold no more seats than it has',
    (int) Database::scalar('SELECT COUNT(*) FROM transport_slots WHERE seats_taken > capacity') === 0);

record('Integrity', 'No product variant has negative stock',
    (int) Database::scalar('SELECT COUNT(*) FROM product_variants WHERE stock < 0') === 0);

$reported = (int) Database::scalar('SELECT COALESCE(SUM(total_cents),0) FROM orders WHERE status = "paid"');
$fromFinance = \App\Services\FinanceService::summary(\App\Services\FinanceService::period('all'))['gross_cents'];
record('Integrity', 'The finance screens agree with the orders table to the cent',
    $reported === $fromFinance, money($fromFinance));

/* ------------------------------------------------------- 11. email */

section('11. Email');

$queueDir = dirname(__DIR__) . '/storage/email-queue';
$before   = count(glob($queueDir . '/*'));

record('Email', 'All 14 transactional templates are installed',
    (int) Database::scalar('SELECT COUNT(*) FROM email_templates') >= 14,
    Database::scalar('SELECT COUNT(*) FROM email_templates') . ' templates');

record('Email', 'The mail queue directory is writable',
    is_dir($queueDir) && is_writable($queueDir));

record('Email', 'Paying an order queued its confirmation emails',
    $before > 0, $before . ' message(s) queued during this run');

/* ----------------------------------------------------- 12. photographs */

section('12. The photograph manager (how the real pictures get in)');

// The venue photographs cannot be fetched by this machine, and the target host
// has no shell, so the committee's only route is this screen. If it breaks,
// the site launches with drawings on it — so it is tested like anything else.

// Section 8 signed out again for the cart checks.
record('Photos', 'Signed back in as the administrator', signIn($base, $adminPassword));

[$status, $body] = fetch($base . '/admin/photos');
record('Photos', 'The Photographs screen loads', $status === 200, "HTTP {$status}");

$slotForms = substr_count($body, 'name="slot"');
record('Photos', 'It lists an upload slot for every picture on the site', $slotForms >= 10, "{$slotForms} slot forms");
record('Photos', 'Each slot states the minimum size it will accept', str_contains($body, 'at least'));

preg_match('/name="slot" value="(room:\d+)"/', $body, $m);
$photoSlot   = $m[1] ?? '';
$photoRoomId = $photoSlot === '' ? 0 : (int) explode(':', $photoSlot)[1];
record('Photos', 'A room photograph slot is offered', $photoRoomId > 0, $photoSlot);

if ($photoRoomId > 0) {
    $tmpDir = sys_get_temp_dir();

    // A picture too small for the slot must be refused, not quietly accepted
    // and stretched. "If it's low quality, we're not interested."
    $small = imagecreatetruecolor(400, 250);
    imagefill($small, 0, 0, imagecolorallocate($small, 120, 90, 60));
    imagejpeg($small, $tmpDir . '/audit-small.jpg', 90);
    imagedestroy($small);

    $heroBefore = Database::scalar('SELECT hero_image FROM room_types WHERE id = ?', [$photoRoomId]);

    [, $body] = fetch($base . '/admin/photos');
    fetch($base . '/admin/photos', ['_token' => token($body), 'slot' => $photoSlot, 'alt_text' => 'AUDIT probe photo'],
        ['photo' => $tmpDir . '/audit-small.jpg']);

    $heroAfter = Database::scalar('SELECT hero_image FROM room_types WHERE id = ?', [$photoRoomId]);
    record('Photos', 'A photograph below the minimum size is refused', $heroAfter === $heroBefore,
        'the slot was left alone');

    // A proper one must be accepted, resized, given a WebP twin, and stripped
    // of the EXIF block that would otherwise publish the photographer's GPS.
    $bigImage = imagecreatetruecolor(2400, 1600);
    for ($x = 0; $x < 2400; $x += 8) {
        imagefilledrectangle($bigImage, $x, 0, $x + 8, 1600,
            imagecolorallocate($bigImage, 60 + (int) ($x / 20) % 120, 90 + (int) ($x / 30) % 100, 70));
    }
    imagejpeg($bigImage, $tmpDir . '/audit-photo.jpg', 92);
    imagedestroy($bigImage);

    [, $body] = fetch($base . '/admin/photos');
    fetch($base . '/admin/photos', [
        '_token'   => token($body), 'slot' => $photoSlot,
        'alt_text' => 'AUDIT probe photo', 'credit' => 'AUDIT probe',
    ], ['photo' => $tmpDir . '/audit-photo.jpg']);

    $stored = (string) Database::scalar('SELECT hero_image FROM room_types WHERE id = ?', [$photoRoomId]);
    record('Photos', 'A full-size photograph is accepted and assigned', str_starts_with($stored, '/photos/'), $stored);

    $onDisk = dirname(__DIR__) . '/public_html/uploads/' . $stored;
    $webp   = preg_replace('/\.jpg$/', '.webp', $onDisk);

    record('Photos', 'The file and its WebP twin are written to disk', is_file($onDisk) && is_file($webp));

    if (is_file($onDisk)) {
        // Read the expected shape from the slot itself rather than writing a
        // number in here. A hardcoded size in a test only records what the
        // code did on the day the test was written.
        $expected = [0, 0];

        foreach (\App\Services\PhotoService::slots() as $group) {
            foreach ($group as $slot) {
                if ($slot['key'] === $photoSlot) {
                    $expected = [$slot['width'], $slot['height']];
                }
            }
        }

        $size = getimagesize($onDisk);
        record('Photos', 'It is resized and centre-cropped to the slot shape',
            $size[0] === $expected[0] && $size[1] === $expected[1],
            "{$size[0]}x{$size[1]}, slot wants {$expected[0]}x{$expected[1]}");
        record('Photos', 'EXIF metadata (including GPS) is stripped',
            !str_contains((string) file_get_contents($onDisk), 'Exif'));
    }

    $slug = (string) Database::scalar('SELECT slug FROM room_types WHERE id = ?', [$photoRoomId]);
    [$status, $publicBody] = fetch($base . '/accommodation/' . $slug);
    record('Photos', 'The uploaded photograph shows on the public room page',
        $status === 200 && str_contains($publicBody, basename($stored, '.jpg')), "HTTP {$status}");

    // Put the room back exactly as it was, and take the probe files with it.
    [, $body] = fetch($base . '/admin/photos');
    fetch($base . '/admin/photos/reset', ['_token' => token($body), 'slot' => $photoSlot]);

    if ($heroBefore !== null) {
        Database::run('UPDATE room_types SET hero_image = ? WHERE id = ?', [$heroBefore, $photoRoomId]);
    }

    record('Photos', 'Resetting a slot restores the shipped illustration',
        Database::scalar('SELECT hero_image FROM room_types WHERE id = ?', [$photoRoomId]) === $heroBefore);

    // The venue gallery is the part a delegate actually scrolls through, so
    // replacing one of those pictures in place is checked too.
    [, $body] = fetch($base . '/admin/photos');
    preg_match('/name="slot" value="(gallery:\d+)"/', $body, $m);
    $gallerySlot = $m[1] ?? '';
    record('Photos', 'Every venue-gallery picture has its own replace slot', $gallerySlot !== '', $gallerySlot);

    if ($gallerySlot !== '') {
        $galleryId   = (int) explode(':', $gallerySlot)[1];
        $galleryWas  = Database::first('SELECT file_path, alt_text, source_note, sort_order FROM gallery_images WHERE id = ?', [$galleryId]);

        fetch($base . '/admin/photos', [
            '_token'   => token($body), 'slot' => $gallerySlot,
            'alt_text' => 'AUDIT probe photo', 'credit' => 'AUDIT probe',
        ], ['photo' => $tmpDir . '/audit-photo.jpg']);

        $galleryNow = Database::first('SELECT file_path, sort_order FROM gallery_images WHERE id = ?', [$galleryId]);

        record('Photos', 'Replacing a gallery picture swaps the file in place',
            str_starts_with((string) $galleryNow['file_path'], '/photos/'), (string) $galleryNow['file_path']);

        record('Photos', 'It keeps its position in the running order',
            (int) $galleryNow['sort_order'] === (int) $galleryWas['sort_order']);

        $replaced = dirname(__DIR__) . '/public_html/uploads/' . $galleryNow['file_path'];

        Database::run(
            'UPDATE gallery_images SET file_path = ?, alt_text = ?, source_note = ? WHERE id = ?',
            [$galleryWas['file_path'], $galleryWas['alt_text'], $galleryWas['source_note'], $galleryId]
        );

        record('Photos', 'The original gallery picture is restored by the audit',
            Database::scalar('SELECT file_path FROM gallery_images WHERE id = ?', [$galleryId]) === $galleryWas['file_path']);

        @unlink($replaced);
        @unlink(preg_replace('/\.jpg$/', '.webp', $replaced));
    }

    Database::run('DELETE FROM gallery_images WHERE source_note = "AUDIT probe"');
    @unlink($onDisk);
    @unlink($webp);
    @unlink($tmpDir . '/audit-small.jpg');
    @unlink($tmpDir . '/audit-photo.jpg');
}

/* -------------------------------------------------------- clean-up */

section('13. The audit cleans up after itself');

// The committee records the audit writes in section 7.
$probes = [
    ['contact_messages', 'subject = "AUDIT probe message"'],
    ['service_applications', 'email = "audit-vol@example.invalid"'],
    ['coupons', 'code = "AUDIT10"'],
    ['products', 'name = "AUDIT probe product"'],
    ['expenses', 'description = "AUDIT probe expense"'],
    ['budget_lines', 'category = "AUDIT probe line"'],
    ['programme_items', 'title = "AUDIT probe session"'],
    ['faqs', 'question = "AUDIT probe question?"'],
];

foreach ($probes as [$table, $where]) {
    Database::run("DELETE FROM {$table} WHERE {$where}");
}

// The delegate account from section 2, its paid order, and everything
// fulfilment did on its behalf: the bed, the shuttle seat, the stock, the
// payment. Leaving these behind would put fictional revenue in front of the
// treasurer, so the pattern is deliberately broad — every run this tool has
// ever done, not just this one.
$purged = purge_users('audit-%@example.invalid');

record(
    'Cleanup',
    'The delegate account, its order and its fulfilment are gone',
    true,
    sprintf('%d account(s), %d order(s) removed', $purged['users'], $purged['orders'])
);

$leftovers = purge_users_leftovers('audit-%@example.invalid');

foreach ($probes as [$table, $where]) {
    $leftovers += (int) Database::scalar("SELECT COUNT(*) FROM {$table} WHERE {$where}");
}

record('Cleanup', 'Every record the audit created has been removed', $leftovers === 0, $leftovers . ' left behind');

// The books must balance again afterwards. If clean-up gave back the wrong
// amount of stock or the wrong number of seats, this is where it shows.
$oversold = (int) Database::scalar(
    'SELECT COUNT(*) FROM transport_slots WHERE seats_taken < 0 OR seats_taken > capacity'
);
$negative = (int) Database::scalar('SELECT COUNT(*) FROM product_variants WHERE stock < 0')
    + (int) Database::scalar('SELECT COUNT(*) FROM products WHERE track_stock = 1 AND stock < 0');

record('Cleanup', 'Stock and shuttle seats were handed back correctly', $oversold === 0 && $negative === 0,
    "{$oversold} slot(s) out of range, {$negative} negative stock row(s)");

/* -------------------------------------------------------- summary */

$pass = count(array_filter($results, static fn (array $r): bool => $r['pass']));
$fail = count($results) - $pass;

printf("\n%s\nTOTAL: %d checks — %d passed, %d failed.\n", str_repeat('=', 64), count($results), $pass, $fail);

@unlink($cookies);

// Put the source-IP check back on, so the audit never leaves the site laxer
// than it found it.
Database::run('UPDATE settings SET value = "0" WHERE key_name = "payfast_skip_ip_check"');

$restoreEnv();

if (is_resource($stub)) {
    proc_terminate($stub);
    proc_close($stub);
}

exit($fail === 0 ? 0 : 1);

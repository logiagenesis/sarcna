<?php
/**
 * Security test — the questions the audit does not ask.
 *
 * tools/audit.php proves the site works. This proves it cannot be made to
 * misbehave: that one delegate cannot read another's order, that a customer
 * cannot set their own price, that a forged payment notification cannot mark
 * an order paid or destroy someone else's booking, and that each of the seven
 * admin roles reaches exactly its own routes and no others.
 *
 * Every answer here is an HTTP status or a row in the database. Nothing is
 * read off the source, because source is what you check when you already
 * believe the answer.
 *
 *   php tools/security-test.php http://127.0.0.1:8000
 *
 * Run it against a development site only: it registers accounts, places
 * orders and posts forged notifications, then removes everything it made.
 */
declare(strict_types=1);

$root = dirname(__DIR__);

require $root . '/app/bootstrap.php';
require $root . '/tools/purge.php';

use App\Core\Database;
use App\Services\AuthService;
use App\Services\PayFastService;

// bootstrap.php turns display_errors off for the site. A test that dies
// silently is worse than no test, so put the message back.
ini_set('display_errors', '1');

set_exception_handler(static function (Throwable $e): void {
    fwrite(STDERR, "\n\033[31mABORTED\033[0m {$e->getMessage()}\n  at {$e->getFile()}:{$e->getLine()}\n");
    exit(2);
});

$base = rtrim($argv[1] ?? 'http://127.0.0.1:8000', '/');

if (!preg_match('~^https?://(127\.0\.0\.1|localhost|\[::1\])(:\d+)?$~', $base)) {
    fwrite(STDERR, "Refusing to run against {$base}. This test forges payments and places orders; point it at a local development site.\n");
    exit(2);
}

$stamp    = time();
$pass     = 0;
$fail     = 0;
$findings = [];
$made     = [];

function jar(string $name): string
{
    return sys_get_temp_dir() . "/sarcna-sec-{$name}.jar";
}

/** @param array<string,mixed>|null $post @return array{0:int,1:string} */
function req(string $jar, string $url, ?array $post = null): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR      => $jar,
        CURLOPT_COOKIEFILE     => $jar,
        CURLOPT_PROXY          => '',
        CURLOPT_TIMEOUT        => 25,
        CURLOPT_FOLLOWLOCATION => false,
    ]);

    if ($post !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    }

    $body = (string) curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    return [$code, $body];
}

function tok(string $body): string
{
    return preg_match('/name="_token" value="([^"]+)"/', $body, $m) ? $m[1] : '';
}

function check(string $label, bool $ok, string $detail = '', string $severity = 'MEDIUM'): void
{
    global $pass, $fail, $findings;

    $ok ? $pass++ : $fail++;

    if (!$ok) {
        $findings[] = "[{$severity}] {$label} — {$detail}";
    }

    printf("  %s %s%s\n", $ok ? "\033[32mPASS\033[0m" : "\033[31mFAIL\033[0m", $label, $detail === '' ? '' : " — {$detail}");
}

// The test is a legitimate high-volume client: it registers nine accounts and
// walks hundreds of routes in a few seconds. The site's own rate limiter would
// correctly throttle that. The limiter itself is untouched.
Database::run('DELETE FROM rate_limits');

/* ================================================================ payments */
/*
 * This machine cannot reach PayFast. Without the same stand-ins the audit
 * uses, every notification stops at "not confirmed" and the paid path is never
 * exercised at all. Both are undone on the way out, and the forgery tests below
 * deliberately take them away again first.
 */
$stubPort    = 8099;
$envPath     = $root . '/.env';
$envOriginal = null;

if (is_file($envPath) && !str_contains((string) file_get_contents($envPath), 'PAYFAST_VALIDATE_URL=http')) {
    $envOriginal = (string) file_get_contents($envPath);
    file_put_contents(
        $envPath,
        rtrim((string) preg_replace('/^PAYFAST_VALIDATE_URL=.*$/m', '', $envOriginal))
            . "\nPAYFAST_VALIDATE_URL=http://127.0.0.1:{$stubPort}/validate\n"
    );
}

$skipIpCheck = static function (bool $on): void {
    Database::run(
        'INSERT INTO settings (group_name, key_name, value, type, label)
              VALUES ("payments", "payfast_skip_ip_check", ?, "boolean", "Skip PayFast IP check (testing only)")
         ON DUPLICATE KEY UPDATE value = VALUES(value)',
        [$on ? '1' : '0']
    );
};

$skipIpCheck(true);

register_shutdown_function(static function () use ($envPath, &$envOriginal): void {
    if ($envOriginal !== null) {
        file_put_contents($envPath, $envOriginal);
        $envOriginal = null;
    }

    Database::run('UPDATE settings SET value = "0" WHERE key_name = "payfast_skip_ip_check"');
});

if (@fsockopen('127.0.0.1', $stubPort, $errno, $errstr, 1) === false) {
    $stubFile = sys_get_temp_dir() . '/sarcna-payfast-stub.php';
    file_put_contents($stubFile, "<?php echo 'VALID';\n");
    proc_open(
        sprintf('exec php -S 127.0.0.1:%d %s', $stubPort, escapeshellarg($stubFile)),
        [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']],
        $pipes
    );
    usleep(600000);
}

/* ============================================== 1. one delegate, then another */

echo "\n\033[1mAUTHORIZATION BETWEEN TWO REAL DELEGATES\033[0m\n";

$password = 'SecurityTest2027!';
$accounts = [];

foreach (['a', 'b'] as $who) {
    $email = "sectest-{$who}-{$stamp}@example.invalid";
    $jar   = jar($who);
    @unlink($jar);

    [, $body] = req($jar, "{$base}/register");
    req($jar, "{$base}/register", [
        '_token'                => tok($body),
        'first_name'            => 'Delegate',
        'last_name'             => strtoupper($who),
        'email'                 => $email,
        'phone'                 => '0820000000',
        'password'              => $password,
        'password_confirmation' => $password,
        'terms'                 => '1',
    ]);

    [$code] = req($jar, "{$base}/account");
    $accounts[$who] = ['jar' => $jar, 'email' => $email];
    $made[] = $email;

    check('Delegate ' . strtoupper($who) . ' registered and signed in', $code === 200, "HTTP {$code}", 'BLOCKER');
}

$product = Database::first('SELECT slug FROM products WHERE type = "registration" AND is_active = 1 LIMIT 1');

/** Place a real order on a signed-in session, through the real pages. */
$placeOrder = static function (string $jar, string $email) use ($base, $product): ?array {
    [, $body] = req($jar, "{$base}/shop/{$product['slug']}");
    req($jar, "{$base}/shop/{$product['slug']}/add", ['_token' => tok($body), 'attendee_name' => 'Security Test']);

    [, $body] = req($jar, "{$base}/checkout");
    req($jar, "{$base}/checkout", [
        '_token' => tok($body), 'first_name' => 'Security', 'last_name' => 'Test',
        'email'  => $email, 'phone' => '0820000000', 'terms' => '1',
    ]);

    return Database::first('SELECT * FROM orders WHERE email = ? ORDER BY id DESC LIMIT 1', [$email]);
};

$order = $placeOrder($accounts['a']['jar'], $accounts['a']['email']);
check("Delegate A placed a real order", $order !== null, $order['reference'] ?? 'none', 'BLOCKER');

if ($order === null) {
    exit("Cannot continue without an order.\n");
}

$ref  = $order['reference'];
$jarB = $accounts['b']['jar'];
$jarX = jar('anon');
@unlink($jarX);

echo "\n  Delegate B, then an anonymous visitor, reach for A's order {$ref}\n";

foreach (['b' => $jarB, 'anonymous' => $jarX] as $who => $jar) {
    foreach (["/account/orders/{$ref}" => 'order detail', "/account/invoice/{$ref}" => 'invoice'] as $path => $what) {
        [$code, $body] = req($jar, $base . $path);
        $leaked = $code === 200 && str_contains($body, $ref);
        check(ucfirst($who) . " cannot read A's {$what}", !$leaked, "HTTP {$code}", 'HIGH');
    }
}

echo "\n  Routes that take an order reference straight from the address bar\n";

// The handoff page embeds the buyer's name and email in the PayFast fields and
// writes to the payment log. A reference is not a secret — it travels in email
// and browser history — so neither may be reachable by whoever holds one.
[$code, $body] = req($jarX, "{$base}/checkout/pay/{$ref}");
check("Anonymous cannot open the payment handoff for a stranger's order",
    $code !== 200, "HTTP {$code}" . ($code === 200 && str_contains($body, 'email_address') ? ' AND THE BUYER\'S EMAIL IS IN THE PAGE' : ''), 'HIGH');

[$code] = req($jarB, "{$base}/checkout/pay/{$ref}");
check('A signed-in stranger cannot open it either', $code !== 200, "HTTP {$code}", 'HIGH');

foreach (['anonymous' => $jarX, 'a signed-in stranger' => $jarB] as $who => $jar) {
    $before = Database::scalar('SELECT status FROM orders WHERE reference = ?', [$ref]);
    req($jar, "{$base}/payment/cancelled?reference={$ref}");
    $after = Database::scalar('SELECT status FROM orders WHERE reference = ?', [$ref]);
    check("A GET to /payment/cancelled from {$who} cannot cancel it", $before === $after, "{$before} -> {$after}", 'HIGH');
}

req($jarX, "{$base}/payment/success?reference={$ref}");
$status = Database::scalar('SELECT status FROM orders WHERE reference = ?', [$ref]);
check('Landing on /payment/success never marks an order paid', $status !== 'paid', "status {$status}", 'CRITICAL');

/* ================================================================ 2. money */

echo "\n\033[1mMONEY INTEGRITY\033[0m\n\n  Can the customer set their own price?\n";

$jarA = $accounts['a']['jar'];
$p    = Database::first('SELECT * FROM products WHERE type = "registration" AND is_active = 1 LIMIT 1');

[, $body] = req($jarA, "{$base}/shop/{$p['slug']}");
req($jarA, "{$base}/shop/{$p['slug']}/add", [
    '_token' => tok($body), 'attendee_name' => 'Tamper Test',
    'price_cents' => '1', 'price' => '1', 'unit_price_cents' => '1', 'total_cents' => '1', 'amount' => '1',
]);

$line = Database::first('SELECT ci.* FROM cart_items ci WHERE ci.product_id = ? ORDER BY ci.id DESC LIMIT 1', [(int) $p['id']]);
check('Posted price fields are ignored; the catalogue price is used',
    $line !== null && (int) $line['unit_price_cents'] === (int) $p['price_cents'],
    'cart ' . (($line['unit_price_cents'] ?? 0) / 100) . ' vs catalogue ' . ($p['price_cents'] / 100), 'CRITICAL');

req($jarA, "{$base}/cart/update", ['_token' => tok($body), 'item_id' => (int) $line['id'], 'quantity' => '-5']);
$quantity = (int) Database::scalar('SELECT quantity FROM cart_items WHERE id = ?', [(int) $line['id']]);
check('A negative quantity cannot make a line worth negative money', $quantity >= 0, "quantity {$quantity}", 'HIGH');

$total = (int) Database::scalar('SELECT COALESCE(SUM(unit_price_cents * quantity), 0) FROM cart_items WHERE cart_id = ?', [(int) $line['cart_id']]);
check('The cart total is never negative', $total >= 0, 'total ' . ($total / 100), 'HIGH');

echo "\n  Coupon rules\n";

Database::run('DELETE FROM coupons WHERE code LIKE "SECTEST%"');

$coupons = [
    ['SECTESTUSED',    'past its usage limit',       ['max_uses' => 1, 'used_count' => 1]],
    ['SECTESTEXPIRED', 'expired',                    ['ends_at' => date('Y-m-d H:i:s', $stamp - 86400)]],
    ['SECTESTFUTURE',  'that has not started yet',   ['starts_at' => date('Y-m-d H:i:s', $stamp + 86400)]],
    ['SECTESTOFF',     'that has been deactivated',  ['is_active' => 0]],
];

foreach ($coupons as [$code, $why, $extra]) {
    Database::insert('coupons', $extra + ['code' => $code, 'discount_type' => 'percent', 'discount_value' => 10, 'is_active' => 1]);

    [, $cartBody] = req($jarA, "{$base}/cart");
    req($jarA, "{$base}/cart/coupon", ['_token' => tok($cartBody), 'code' => $code]);

    // Ask the database, not the page. A cart page that simply never prints the
    // code would make a broken refusal look like a good one.
    $attached = (int) Database::scalar(
        'SELECT COUNT(*) FROM carts c JOIN coupons k ON k.id = c.coupon_id WHERE c.id = ? AND k.code = ?',
        [(int) $line['cart_id'], $code]
    );

    check("A coupon {$why} is refused", $attached === 0, "attached={$attached}", 'HIGH');
}

Database::insert('coupons', ['code' => 'SECTESTBIG', 'discount_type' => 'fixed', 'discount_value' => 99999999, 'is_active' => 1]);
[, $cartBody] = req($jarA, "{$base}/cart");
req($jarA, "{$base}/cart/coupon", ['_token' => tok($cartBody), 'code' => 'SECTESTBIG']);
[, $cartBody] = req($jarA, "{$base}/cart");
$negative = preg_match('/R\s*-[\d\s,]+\.\d{2}/', $cartBody) === 1;
check('A discount larger than the cart cannot make the total negative', !$negative,
    $negative ? 'a NEGATIVE total is displayed' : 'the total floors at zero', 'CRITICAL');

Database::run('UPDATE carts SET coupon_id = NULL WHERE id = ?', [(int) $line['cart_id']]);

echo "\n  Forged, underpaid and replayed notifications\n";

// Guest checkout is off by default. The setting has to bind the POST, not only
// the page: an order placed around it lands with user_id NULL, invisible under
// /account/orders and an orphan on the booking chair's list.
$guestJar = jar('guest');
@unlink($guestJar);
$guestEmail = "sectest-guest-{$stamp}@example.invalid";
[, $shopBody] = req($guestJar, "{$base}/shop/{$p['slug']}");
req($guestJar, "{$base}/shop/{$p['slug']}/add", ['_token' => tok($shopBody), 'attendee_name' => 'Guest Test']);
[, $guestCart] = req($guestJar, "{$base}/cart");
req($guestJar, "{$base}/checkout", [
    '_token' => tok($guestCart), 'first_name' => 'Guest', 'last_name' => 'Test',
    'email'  => $guestEmail, 'phone' => '0820000000', 'terms' => '1',
]);
$made[] = $guestEmail;

$guestAllowed = (int) Database::scalar('SELECT COUNT(*) FROM settings WHERE key_name = "allow_guest_checkout" AND value IN ("1", "true")');
$guestOrder   = Database::first('SELECT id FROM orders WHERE email = ?', [$guestEmail]);
check('The guest-checkout setting binds the POST, not only the page',
    $guestAllowed === 1 || $guestOrder === null,
    $guestOrder === null ? 'no order created' : 'AN ORDER WAS CREATED WITH NO ACCOUNT', 'MEDIUM');

/** A correctly signed notification for this order, at this amount. */
$notification = static function (array $order, string $amount): array {
    $fields = [
        'm_payment_id'   => $order['reference'],
        'pf_payment_id'  => (string) random_int(1000000, 9999999),
        'payment_status' => 'COMPLETE',
        'amount_gross'   => $amount,
        'email_address'  => $order['email'],
        'custom_str2'    => (string) ($order['cart_token'] ?? ''),
    ];

    $fields['signature'] = PayFastService::signature($fields);

    return $fields;
};

$underpaid = $placeOrder($jarA, $accounts['a']['email']);
req($jarA, "{$base}/payment/notify", $notification($underpaid, '1.00'));
$status = Database::scalar('SELECT status FROM orders WHERE id = ?', [(int) $underpaid['id']]);
check('A correctly signed notification for R1.00 does not mark a larger order paid', $status !== 'paid', "status {$status}", 'CRITICAL');

/*
 * Above, the test stands in for PayFast itself: the IP check is waived and the
 * stub answers VALID, so refusing and failing the order is the right outcome.
 * The dangerous case is the attacker's — a forged notification from an address
 * PayFast does not own. Failing an order is destructive: it hands back the
 * shuttle seats and emails the delegate to say their payment failed. Take the
 * stand-ins away, and a forgery must leave the booking untouched.
 */
$skipIpCheck(false);
$victim = $placeOrder($jarA, $accounts['a']['email']);
req($jarA, "{$base}/payment/notify", $notification($victim, '1.00'));
$status = Database::scalar('SELECT status FROM orders WHERE id = ?', [(int) $victim['id']]);
$skipIpCheck(true);

check('A forged underpayment from a non-PayFast address cannot destroy a booking',
    $status === 'pending_payment',
    "the order is now '{$status}'" . ($status === 'pending_payment' ? '' : ' — seats released and the delegate emailed'), 'HIGH');

$paying = $placeOrder($jarA, $accounts['a']['email']);
$fields = $notification($paying, number_format($paying['total_cents'] / 100, 2, '.', ''));
req($jarA, "{$base}/payment/notify", $fields);
$status = Database::scalar('SELECT status FROM orders WHERE id = ?', [(int) $paying['id']]);
check('A correct notification does mark it paid', $status === 'paid', "status {$status}", 'BLOCKER');

$countsBefore = [
    'payments' => (int) Database::scalar('SELECT COUNT(*) FROM payments WHERE order_id = ? AND status = "complete"', [(int) $paying['id']]),
    'bookings' => (int) Database::scalar('SELECT COUNT(*) FROM bookings WHERE order_id = ?', [(int) $paying['id']]),
    'donations' => (int) Database::scalar('SELECT COUNT(*) FROM donations WHERE order_id = ?', [(int) $paying['id']]),
];

req($jarA, "{$base}/payment/notify", $fields);   // replay, byte for byte
req($jarA, "{$base}/payment/notify", $fields);   // and again

foreach ($countsBefore as $what => $before) {
    $table = $what === 'payments' ? 'payments WHERE order_id = ? AND status = "complete"' : "{$what} WHERE order_id = ?";
    $after = (int) Database::scalar("SELECT COUNT(*) FROM {$table}", [(int) $paying['id']]);
    check("Replaying the same notification does not duplicate {$what}", $before === $after, "{$before} -> {$after}",
        $what === 'payments' ? 'CRITICAL' : 'HIGH');
}

$paid = (int) Database::scalar('SELECT COALESCE(SUM(amount_cents), 0) FROM payments WHERE order_id = ? AND status = "complete"', [(int) $paying['id']]);
check('The recorded payment equals the order total exactly', $paid === (int) $paying['total_cents'],
    'paid ' . ($paid / 100) . ' vs order ' . ($paying['total_cents'] / 100), 'CRITICAL');

/* =========================================================== 3. invariants */

echo "\n  Ledger invariants across the whole database\n";

$invariants = [
    ['No shuttle departure is oversold',
     'SELECT COUNT(*) FROM transport_slots WHERE seats_taken > capacity', 'HIGH'],
    ['No stock is negative',
     'SELECT (SELECT COUNT(*) FROM product_variants WHERE stock < 0)
           + (SELECT COUNT(*) FROM products WHERE track_stock = 1 AND stock < 0)', 'HIGH'],
    ['No bed is double-booked on a night',
     'SELECT COUNT(*) FROM (SELECT bed_id FROM bookings WHERE active_night IS NOT NULL
        GROUP BY bed_id, active_night HAVING COUNT(*) > 1) x', 'CRITICAL'],
    ['No refund exceeds what its order was paid',
     'SELECT COUNT(*) FROM (SELECT r.order_id FROM refunds r GROUP BY r.order_id
        HAVING SUM(r.amount_cents) > (SELECT COALESCE(SUM(p.amount_cents), 0) FROM payments p
                                       WHERE p.order_id = r.order_id AND p.status = "complete")) x', 'CRITICAL'],
    ['Every paid order has a completed payment behind it',
     'SELECT COUNT(*) FROM orders o WHERE o.status = "paid"
        AND NOT EXISTS (SELECT 1 FROM payments p WHERE p.order_id = o.id AND p.status = "complete")', 'CRITICAL'],
    ['No order total disagrees with its line items',
     'SELECT COUNT(*) FROM orders o WHERE o.subtotal_cents
        <> (SELECT COALESCE(SUM(total_cents), 0) FROM order_items WHERE order_id = o.id)', 'HIGH'],
];

foreach ($invariants as [$label, $sql, $severity]) {
    $offending = (int) Database::scalar($sql);
    check($label, $offending === 0, "{$offending} offending row(s)", $severity);
}

/* ================================================================ 4. roles */

echo "\n\033[1mROLE BOUNDARIES\033[0m\n";

// Every admin GET route, read out of the router file rather than listed here,
// so a route added tomorrow is tested tomorrow.
$routes = [];
$source = (string) file_get_contents($root . '/app/Config/routes.php');
$adminGroup = substr($source, (int) strpos($source, "\$router->group('/admin'"));

preg_match_all("~\\\$router->get\(\s*'([^']*)'\s*,\s*\[[^\]]*\]\s*(?:,\s*\[([^\]]*)\])?\s*\)~", $adminGroup, $matches, PREG_SET_ORDER);

foreach ($matches as $match) {
    $path = $match[1] === '/' ? '/admin' : '/admin' . $match[1];

    if (str_contains($path, '{')) {
        continue;   // needs a real id; covered by the delegate tests above
    }

    $routes[$path] = preg_match('/admin:([a-z*]+)/', $match[2] ?? '', $m) ? $m[1] : null;
}

// Exports take a dataset name, and ExportController gates each dataset by its
// own capability rather than one blanket 'exports' permission. Walk the real
// map, so the test cannot pass by assuming a weaker rule than the code applies.
$datasets = (new ReflectionClass(App\Controllers\Admin\ExportController::class))->getConstant('DATASET_CAPABILITIES');

foreach ((array) $datasets as $dataset => $capability) {
    $routes['/admin/export/' . $dataset] = $capability;
}

ksort($routes);

printf("  %d admin routes x %d roles = %d combinations\n\n", count($routes), count(AuthService::ROLE_PERMISSIONS), count($routes) * count(AuthService::ROLE_PERMISSIONS));

foreach (AuthService::ROLE_PERMISSIONS as $role => $permissions) {
    $email = "sectest-{$role}-{$stamp}@example.invalid";
    $id    = Database::insert('users', [
        'first_name'        => 'Role',
        'last_name'         => ucfirst(str_replace('_', ' ', $role)),
        'email'             => $email,
        'password_hash'     => password_hash($password, PASSWORD_DEFAULT),
        'status'            => 'active',
        'is_admin'          => 1,
        'email_verified_at' => date('Y-m-d H:i:s'),
    ]);
    Database::run('INSERT INTO user_roles (user_id, role) VALUES (?, ?)', [$id, $role]);
    $made[] = $email;

    $jar = jar($role);
    @unlink($jar);
    [, $body] = req($jar, "{$base}/login");
    req($jar, "{$base}/login", ['_token' => tok($body), 'email' => $email, 'password' => $password]);

    [$dashboard] = req($jar, "{$base}/admin");

    if ($dashboard !== 200) {
        check("{$role} signs in to the admin", false, "HTTP {$dashboard} on /admin", 'BLOCKER');
        continue;
    }

    $everything = in_array('*', $permissions, true);
    $wrong      = [];
    $allowed    = 0;

    foreach ($routes as $path => $permission) {
        $shouldReach = $everything || $permission === null || in_array($permission, $permissions, true);
        $allowed    += $shouldReach ? 1 : 0;

        [$code] = req($jar, $base . $path);
        $didReach = $code === 200;

        if ($didReach !== $shouldReach) {
            $wrong[] = sprintf('%s [%s] HTTP %d %s', $path, $permission ?? 'admin', $code,
                $didReach ? 'REACHED, must not' : 'refused, should be allowed');
        }
    }

    // Reaching a route you have no permission for is a breach. Being refused
    // one you should have is a bug, but it does not leak anything.
    $severity = str_contains(implode('', $wrong), 'REACHED') ? 'CRITICAL' : 'MEDIUM';
    check(sprintf('%-20s reaches its %2d routes and no others', $role, $allowed), $wrong === [], implode('; ', $wrong), $severity);

    @unlink($jar);
}

// A permission the router demands that no ordinary role holds is not a
// security hole, but it means only the Super Admin can do that job.
$granted = [];

foreach (AuthService::ROLE_PERMISSIONS as $role => $permissions) {
    if ($role !== 'super_admin') {
        $granted = array_merge($granted, $permissions);
    }
}

$superAdminOnly = array_values(array_diff(array_unique(array_filter(array_values($routes))), $granted, ['*']));

/* =============================================================== clean up */

Database::run('DELETE FROM coupons WHERE code LIKE "SECTEST%"');

foreach ($made as $email) {
    // The seven role accounts are admins. purge_users() refuses to touch an
    // admin unless told to, so that a careless pattern can never delete a real
    // committee member; this test made them, so it says so.
    purge_users($email, true);
}

foreach (array_merge(['a', 'b', 'anon', 'guest'], array_keys(AuthService::ROLE_PERMISSIONS)) as $name) {
    @unlink(jar($name));
}

// Count every kind of record the test creates, not just the convenient one.
// A cleanup check that only looks at orders passes happily while seven admin
// accounts sit in the customers list — which is how fictional data reached the
// finance screens in the first place.
$pattern    = "sectest-%-{$stamp}@example.invalid";
$leftBehind = (int) Database::scalar('SELECT COUNT(*) FROM orders WHERE email LIKE ?', [$pattern])
    + (int) Database::scalar('SELECT COUNT(*) FROM users WHERE email LIKE ?', [$pattern])
    + (int) Database::scalar('SELECT COUNT(*) FROM user_roles r WHERE NOT EXISTS (SELECT 1 FROM users u WHERE u.id = r.user_id)')
    + (int) Database::scalar('SELECT COUNT(*) FROM coupons WHERE code LIKE "SECTEST%"');

check('The test removed everything it made', $leftBehind === 0, "{$leftBehind} record(s) left behind", 'MEDIUM');

printf("\n%s\n%d passed, %d failed.\n", str_repeat('=', 70), $pass, $fail);

if ($superAdminOnly !== []) {
    printf("\n\033[1mROLE COVERAGE\033[0m\n  No role but Super Admin can reach: %s\n", implode(', ', $superAdminOnly));
}

if ($findings !== []) {
    echo "\n\033[1mFINDINGS\033[0m\n";

    foreach ($findings as $finding) {
        echo "  {$finding}\n";
    }
}

exit($fail === 0 ? 0 : 1);

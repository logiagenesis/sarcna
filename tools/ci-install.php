<?php
declare(strict_types=1);

/**
 * Install the site the way a real deployment does — through the web form.
 *
 *   php tools/ci-install.php [base-url]
 *
 * This exists for continuous integration, and it deliberately does NOT
 * reimplement the installer. It fills in the same form a human fills in at
 * /install and posts it, so every CI run also proves that the installer
 * itself still works. A second, parallel installer written for tests would
 * drift from the real one and hide exactly the kind of failure that cost the
 * first deployment attempt several hours.
 *
 * It refuses to run against anything but a local address, because it creates
 * an administrator with a known password.
 */

if (PHP_SAPI !== 'cli') {
    exit("Command line only.\n");
}

$base = rtrim($argv[1] ?? 'http://127.0.0.1:8000', '/');

if (!preg_match('#^https?://(127\.0\.0\.1|localhost)(:\d+)?$#', $base)) {
    exit("Refusing to run against {$base}. This tool is for a local CI server only.\n");
}

$root = dirname(__DIR__);

// The lock is what stops /install being re-run. On a fresh CI checkout there
// is none; remove it anyway so a cached workspace cannot skip the install.
@unlink($root . '/app/Config/installed.lock');

$cookies = tempnam(sys_get_temp_dir(), 'ci-install-');

$fetch = static function (string $url, array $post = []) use ($cookies): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEJAR      => $cookies,
        CURLOPT_COOKIEFILE     => $cookies,
        CURLOPT_TIMEOUT        => 120,
        CURLOPT_PROXY          => '',
    ]);

    if ($post !== []) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    }

    $body   = (string) curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    return [$status, $body];
};

echo "Installing through the real web installer at {$base}/install\n";

[$status, $body] = $fetch($base . '/install');

if ($status !== 200) {
    exit("The installer page returned HTTP {$status}. Is the site running?\n");
}

if (preg_match('/name="_token" value="([^"]+)"/', $body, $m) !== 1) {
    exit("Could not read a CSRF token from the installer page.\n");
}

// Read the database settings out of .env rather than restating them, so CI and
// the running site cannot disagree about which database they mean.
$env = [];

foreach (file($root . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
    if (!str_starts_with(trim($line), '#') && str_contains($line, '=')) {
        [$key, $value] = explode('=', $line, 2);
        $env[trim($key)] = trim($value, " \t\"'");
    }
}

[$status, $body] = $fetch($base . '/install', [
    '_token'                     => $m[1],
    'db_host'                    => $env['DB_HOST'] ?? '127.0.0.1',
    'db_port'                    => $env['DB_PORT'] ?? '3306',
    'db_name'                    => $env['DB_NAME'] ?? 'sarcna_ci',
    'db_user'                    => $env['DB_USER'] ?? 'root',
    'db_pass'                    => $env['DB_PASS'] ?? '',
    'app_url'                    => $base,
    'admin_first_name'           => 'Convention',
    'admin_last_name'            => 'Committee',
    'admin_email'                => 'admin@sarcna.org.za',
    'admin_password'             => 'Convention2027',
    'admin_password_confirmation' => 'Convention2027',
    'contact_email'              => 'info@sarcna.org.za',
    'contact_phone'              => '0210000000',
    'registration_email'         => 'registration@sarcna.org.za',
    'accommodation_email'        => 'accommodation@sarcna.org.za',
    'transport_email'            => 'transport@sarcna.org.za',
    'admin_notification_email'   => 'admin@sarcna.org.za',
    'mail_driver'                => 'log',
    'mail_from_address'          => 'noreply@sarcna.org.za',
    // Obvious, made-up test values — NOT anybody's real PayFast account.
    // They exist only so the payment handoff is exercisable: the signature is
    // computed over whatever merchant ID it is given, and tools/audit.php
    // points the confirmation call at its own local stub, so no request ever
    // reaches PayFast. Leaving these blank is what a fresh install does by
    // default, and the site then correctly refuses to take a payment.
    'payfast_mode'               => 'sandbox',
    'payfast_merchant_id'        => '10000000',
    'payfast_merchant_key'       => 'citestkeycitestkey',
    'payfast_passphrase'         => 'ci-test-passphrase',
    'whatsapp_number'            => '27210000000',
    'seed_demo'                  => '1',
]);

@unlink($cookies);

if ($status !== 200) {
    exit("The installer returned HTTP {$status}.\n");
}

// The installer reports what it did. Anything other than a finished install
// re-renders the form, so look for the evidence rather than trusting the 200.
$plain = strip_tags($body);

foreach (['Database tables created', 'Administrator account created'] as $expected) {
    if (!str_contains($plain, $expected)) {
        echo $plain, "\n";
        exit("The installer did not report \"{$expected}\". Install failed.\n");
    }
}

echo trim(preg_replace('/\n{3,}/', "\n", $plain)), "\n";

// And it must lock itself, or anybody could re-run it on a live site.
[$status] = $fetch($base . '/install');

if ($status !== 410) {
    exit("After installing, /install returned HTTP {$status}; it should be 410 (locked).\n");
}

echo "\nInstalled, and /install is locked (HTTP 410).\n";

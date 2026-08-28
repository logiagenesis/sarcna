<?php
declare(strict_types=1);

use App\Core\Env;

$root = dirname(__DIR__, 2);

/**
 * Fall back to the host actually serving the request when APP_URL is not set
 * yet (before the installer has run, or on a staging copy of the site).
 */
$detectedUrl = static function (): string {
    $host = (string) ($_SERVER['HTTP_HOST'] ?? '');

    if ($host === '' || preg_match('/^[A-Za-z0-9.\-:\[\]]+$/', $host) !== 1) {
        return 'http://localhost';
    }

    $secure = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';

    return ($secure ? 'https://' : 'http://') . $host;
};

return [
    'app' => [
        'name'      => (string) Env::get('APP_NAME', 'SARCNA 2027 Convention'),
        'env'       => (string) Env::get('APP_ENV', 'production'),
        'debug'     => (bool) Env::get('APP_DEBUG', false),
        'url'       => rtrim((string) Env::get('APP_URL', $detectedUrl()), '/'),
        'timezone'  => (string) Env::get('APP_TIMEZONE', 'Africa/Johannesburg'),
        'key'       => (string) Env::get('APP_KEY', ''),
        'locale'    => 'en_ZA',
        'currency'  => 'ZAR',
    ],

    'event' => [
        'title'          => 'SARCNA 2027 Convention',
        'slogan'         => 'Rooted in Recovery. Rising Together.',
        'theme'          => 'Rooted in Recovery',
        'supporting'     => 'A weekend of fellowship, service, renewal, and connection in the Cape Winelands.',
        'starts_at'      => '2027-08-27 14:00:00',
        'ends_at'        => '2027-08-29 12:00:00',
        'early_arrival'  => '2027-08-26',
        'dates_label'    => '27–29 August 2027',
        'venue_name'     => 'Boschendal Retreat Cottages & Conference Venue',
        'venue_region'   => 'Cape Winelands, Western Cape, South Africa',
        'nights'         => ['2027-08-26', '2027-08-27', '2027-08-28'],
    ],

    'database' => [
        'host'    => (string) Env::get('DB_HOST', 'localhost'),
        'port'    => (string) Env::get('DB_PORT', '3306'),
        'name'    => (string) Env::get('DB_NAME', ''),
        'user'    => (string) Env::get('DB_USER', ''),
        'pass'    => (string) Env::get('DB_PASS', ''),
        'charset' => (string) Env::get('DB_CHARSET', 'utf8mb4'),
    ],

    'payfast' => [
        'mode'         => (string) Env::get('PAYFAST_MODE', 'sandbox'),
        'merchant_id'  => (string) Env::get('PAYFAST_MERCHANT_ID', ''),
        'merchant_key' => (string) Env::get('PAYFAST_MERCHANT_KEY', ''),
        'passphrase'   => (string) Env::get('PAYFAST_PASSPHRASE', ''),
        'return_url'   => (string) Env::get('PAYFAST_RETURN_URL', ''),
        'cancel_url'   => (string) Env::get('PAYFAST_CANCEL_URL', ''),
        'notify_url'   => (string) Env::get('PAYFAST_NOTIFY_URL', ''),
    ],

    'mail' => [
        'driver'       => (string) Env::get('MAIL_DRIVER', 'smtp'),
        'host'         => (string) Env::get('MAIL_HOST', ''),
        'port'         => (int) Env::get('MAIL_PORT', 587),
        'encryption'   => (string) Env::get('MAIL_ENCRYPTION', 'tls'),
        'username'     => (string) Env::get('MAIL_USERNAME', ''),
        'password'     => (string) Env::get('MAIL_PASSWORD', ''),
        'from_address' => (string) Env::get('MAIL_FROM_ADDRESS', 'no-reply@sarcna.org.za'),
        'from_name'    => (string) Env::get('MAIL_FROM_NAME', 'SARCNA 2027 Convention'),
    ],

    'analytics' => [
        'ga_measurement_id'        => (string) Env::get('GA_MEASUREMENT_ID', ''),
        'google_site_verification' => (string) Env::get('GOOGLE_SITE_VERIFICATION', ''),
    ],

    'contact' => [
        'whatsapp_number' => (string) Env::get('WHATSAPP_NUMBER', ''),
        'email'           => (string) Env::get('CONTACT_EMAIL', 'info@sarcna.org.za'),
    ],

    'session' => [
        'name'     => (string) Env::get('SESSION_NAME', 'sarcna_session'),
        'lifetime' => (int) Env::get('SESSION_LIFETIME', 7200),
        'secure'   => (bool) Env::get('SESSION_SECURE', true),
    ],

    'booking' => [
        'hold_minutes' => (int) Env::get('BOOKING_HOLD_MINUTES', 15),
    ],

    'paths' => [
        'root'        => $root,
        'app'         => $root . '/app',
        'views'       => $root . '/app/Views',
        'public'      => $root . '/public_html',
        'uploads'     => $root . '/public_html/uploads',
        'storage'     => $root . '/storage',
        'logs'        => $root . '/storage/logs',
        'cache'       => $root . '/storage/cache',
        'backups'     => $root . '/storage/backups',
        'email_queue' => $root . '/storage/email-queue',
        'database'    => $root . '/database',
        'lock'        => $root . '/app/Config/installed.lock',
        'env'         => $root . '/.env',
    ],
];

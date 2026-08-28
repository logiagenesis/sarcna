<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Config;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Env;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Services\AuthService;
use PDO;

/**
 * One-run web installer.
 *
 * Upload the files, create a MySQL database in cPanel, visit /install, fill in
 * the form. The installer creates every table, seeds the demo content, creates
 * the first admin, writes .env, and then locks itself.
 */
final class InstallController extends Controller
{
    public function index(): string
    {
        return View::partial('install.form', [
            'checks' => $this->requirements(),
            'values' => $this->defaults(),
            'errors' => [],
        ]);
    }

    public function run(): string
    {
        $input = $this->request->all();

        $validator = Validator::make($input, [
            'db_host'          => 'required|max:190',
            'db_port'          => 'required|numeric',
            'db_name'          => 'required|max:190',
            'db_user'          => 'required|max:190',
            'app_url'          => 'required|url',
            'admin_first_name' => 'required|max:80',
            'admin_last_name'  => 'required|max:80',
            'admin_email'      => 'required|email|max:190',
            'admin_password'   => 'required|password|confirmed',
            'contact_email'    => 'required|email|max:190',
            'mail_driver'      => 'required|in:smtp,mail,log',
            'payfast_mode'     => 'required|in:sandbox,live',
        ], [
            'db_name'        => 'Database name',
            'db_user'        => 'Database user',
            'app_url'        => 'Website address',
            'admin_password' => 'Administrator password',
        ]);

        if ($validator->fails()) {
            return View::partial('install.form', [
                'checks' => $this->requirements(),
                'values' => $input,
                'errors' => $validator->errors(),
            ]);
        }

        // 1. Prove the database credentials work before writing anything.
        try {
            $pdo = Database::connectWith(
                (string) $input['db_host'],
                (string) $input['db_port'],
                (string) $input['db_name'],
                (string) $input['db_user'],
                (string) ($input['db_pass'] ?? '')
            );
        } catch (\PDOException $e) {
            return View::partial('install.form', [
                'checks' => $this->requirements(),
                'values' => $input,
                'errors' => ['db_name' => ['Could not connect: ' . $e->getMessage()]],
            ]);
        }

        Database::setConnection($pdo);

        // 2. Schema, then seed data.
        $log = [];

        try {
            $log[] = $this->runSqlFile($pdo, Config::get('paths.database') . '/schema.sql', 'Database tables created');
            $log[] = $this->runSqlFile($pdo, Config::get('paths.database') . '/seed.sql', 'Settings and email templates seeded');

            if (($input['seed_demo'] ?? '1') === '1') {
                $log[] = $this->runSqlFile($pdo, Config::get('paths.database') . '/demo-data.sql', 'Demo content loaded');
                $log[] = $this->generateInventory($pdo);
            }
        } catch (\Throwable $e) {
            return View::partial('install.form', [
                'checks' => $this->requirements(),
                'values' => $input,
                'errors' => ['db_name' => ['The database import failed: ' . $e->getMessage()]],
            ]);
        }

        // 3. First administrator.
        $adminId = $this->createAdmin($pdo, $input);
        $log[]   = 'Administrator account created for ' . $input['admin_email'];

        // 4. Public settings that the committee will edit later.
        $this->applySettings($pdo, $input);
        $log[] = 'Site settings saved';

        // 5. Write .env.
        $envPath = (string) Config::get('paths.env');
        $written = Env::write($envPath, $this->buildEnv($input));

        if (!$written) {
            return View::partial('install.manual-env', [
                'contents' => $this->envPreview($input),
                'path'     => $envPath,
                'log'      => $log,
            ]);
        }

        $log[] = '.env written to the application root';

        // 6. Lock the installer.
        @file_put_contents((string) Config::get('paths.lock'), json_encode([
            'installed_at' => date('c'),
            'version'      => '1.0.0',
            'php'          => PHP_VERSION,
        ], JSON_PRETTY_PRINT));

        $log[] = 'Installer locked (app/Config/installed.lock)';

        return View::partial('install.complete', [
            'log'        => $log,
            'adminEmail' => (string) $input['admin_email'],
            'appUrl'     => rtrim((string) $input['app_url'], '/'),
            'demoSeeded' => ($input['seed_demo'] ?? '1') === '1',
        ]);
    }

    /* ------------------------------------------------------------ helpers */

    private function requirements(): array
    {
        $root = (string) Config::get('paths.root');

        return [
            ['PHP 8.1 or newer', version_compare(PHP_VERSION, '8.1.0', '>='), 'Running PHP ' . PHP_VERSION],
            ['PDO MySQL driver', extension_loaded('pdo_mysql'), 'Required to talk to MySQL'],
            ['mbstring extension', extension_loaded('mbstring'), 'Required for UTF-8 handling'],
            ['OpenSSL extension', extension_loaded('openssl'), 'Required for secure SMTP and signatures'],
            ['GD or Imagick', extension_loaded('gd') || extension_loaded('imagick'), 'Used for image resizing on upload'],
            ['Application root is writable', is_writable($root), 'The installer writes .env here'],
            ['/storage is writable', is_writable($root . '/storage'), 'Logs, cache and the email queue'],
            ['/public_html/uploads is writable', is_writable($root . '/public_html/uploads'), 'Admin image uploads'],
        ];
    }

    private function defaults(): array
    {
        $host   = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $scheme = Session::isHttps() ? 'https' : 'http';

        return [
            'db_host'       => 'localhost',
            'db_port'       => '3306',
            'app_url'       => $scheme . '://' . $host,
            'mail_driver'   => 'smtp',
            'mail_port'     => '587',
            'mail_encryption' => 'tls',
            'mail_host'     => 'mail.' . preg_replace('/^www\./', '', $host),
            'payfast_mode'  => 'sandbox',
            'contact_email' => 'info@' . preg_replace('/^www\./', '', $host),
            'seed_demo'     => '1',
        ];
    }

    /** Execute a .sql file statement by statement. */
    private function runSqlFile(PDO $pdo, string $path, string $label): string
    {
        if (!is_readable($path)) {
            throw new \RuntimeException('Missing SQL file: ' . basename($path));
        }

        $statements = $this->splitStatements((string) file_get_contents($path));
        $count      = 0;

        foreach ($statements as $statement) {
            $pdo->exec($statement);
            $count++;
        }

        return $label . ' (' . $count . ' statements)';
    }

    /**
     * Split a SQL file into statements. Quote- and comment-aware, because the
     * seeded policy pages contain semicolons and apostrophes inside their HTML.
     *
     * @return string[]
     */
    private function splitStatements(string $sql): array
    {
        $statements = [];
        $current    = '';
        $length     = strlen($sql);
        $quote      = null;

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            $next = $sql[$i + 1] ?? '';

            if ($quote === null) {
                // Line comment: -- or #
                if (($char === '-' && $next === '-') || $char === '#') {
                    $newline = strpos($sql, "\n", $i);
                    $i       = $newline === false ? $length : $newline;
                    continue;
                }

                // Block comment
                if ($char === '/' && $next === '*') {
                    $end = strpos($sql, '*/', $i + 2);
                    $i   = $end === false ? $length : $end + 1;
                    continue;
                }

                if ($char === "'" || $char === '"' || $char === '`') {
                    $quote = $char;
                }

                if ($char === ';') {
                    $statement = trim($current);

                    if ($statement !== '') {
                        $statements[] = $statement;
                    }

                    $current = '';
                    continue;
                }
            } else {
                // Inside a quoted string: honour backslash escapes and '' doubling.
                if ($char === '\\') {
                    $current .= $char . $next;
                    $i++;
                    continue;
                }

                if ($char === $quote) {
                    if ($next === $quote) {
                        $current .= $char . $next;
                        $i++;
                        continue;
                    }

                    $quote = null;
                }
            }

            $current .= $char;
        }

        $statement = trim($current);

        if ($statement !== '') {
            $statements[] = $statement;
        }

        return $statements;
    }

    /**
     * Build the room units and beds from each seeded room type. Bed-level
     * inventory is generated here rather than written out by hand in SQL.
     */
    private function generateInventory(PDO $pdo): string
    {
        $roomTypes = $pdo->query('SELECT * FROM room_types')->fetchAll();
        $units     = 0;
        $beds      = 0;

        $unitCounts = [
            'retreat-twin-cottage'     => 40,
            'garden-quad-cottage'      => 20,
            'mountain-view-farmhouse'  => 8,
            'accessible-twin-cottage'  => 4,
            'overflow-partner-lodge'   => 15,
        ];

        $insertUnit = $pdo->prepare('INSERT INTO room_units (room_type_id, name, code, sort_order) VALUES (?, ?, ?, ?)');
        $insertBed  = $pdo->prepare('INSERT INTO beds (room_unit_id, label, sort_order) VALUES (?, ?, ?)');

        foreach ($roomTypes as $roomType) {
            $existing = (int) $pdo->query('SELECT COUNT(*) FROM room_units WHERE room_type_id = ' . (int) $roomType['id'])->fetchColumn();

            if ($existing > 0) {
                continue;
            }

            $unitCount = $unitCounts[$roomType['slug']] ?? 10;
            $bedCount  = max(1, (int) $roomType['beds_per_unit']);

            for ($unit = 1; $unit <= $unitCount; $unit++) {
                $name = sprintf('%s %02d', $roomType['name'], $unit);
                $code = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $roomType['slug']) ?: 'UNIT', 0, 3)) . sprintf('%02d', $unit);

                $insertUnit->execute([(int) $roomType['id'], $name, $code, $unit]);
                $unitId = (int) $pdo->lastInsertId();
                $units++;

                for ($bed = 0; $bed < $bedCount; $bed++) {
                    $insertBed->execute([$unitId, 'Bed ' . chr(65 + $bed), $bed]);
                    $beds++;
                }
            }
        }

        return sprintf('Accommodation inventory generated (%d units, %d beds)', $units, $beds);
    }

    private function createAdmin(PDO $pdo, array $input): int
    {
        $statement = $pdo->prepare(
            'INSERT INTO users (first_name, last_name, email, password_hash, is_admin, status, email_verified_at)
             VALUES (?, ?, ?, ?, 1, "active", NOW())
             ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), is_admin = 1, email_verified_at = NOW()'
        );

        $statement->execute([
            (string) $input['admin_first_name'],
            (string) $input['admin_last_name'],
            strtolower(trim((string) $input['admin_email'])),
            AuthService::hash((string) $input['admin_password']),
        ]);

        $id = (int) $pdo->lastInsertId();

        if ($id === 0) {
            $id = (int) $pdo->query('SELECT id FROM users WHERE email = ' . $pdo->quote(strtolower(trim((string) $input['admin_email']))))->fetchColumn();
        }

        $pdo->prepare('INSERT IGNORE INTO user_roles (user_id, role) VALUES (?, "super_admin")')->execute([$id]);

        return $id;
    }

    private function applySettings(PDO $pdo, array $input): void
    {
        $settings = [
            'site_name'                => 'SARCNA 2027 Convention',
            'contact_email'            => (string) $input['contact_email'],
            'admin_notification_email' => (string) ($input['admin_notification_email'] ?: $input['contact_email']),
            'registration_email'       => (string) ($input['registration_email'] ?: $input['contact_email']),
            'accommodation_email'      => (string) ($input['accommodation_email'] ?: $input['contact_email']),
            'transport_email'          => (string) ($input['transport_email'] ?: $input['contact_email']),
            'contact_phone'            => (string) ($input['contact_phone'] ?? ''),
            'whatsapp_number'          => preg_replace('/\D+/', '', (string) ($input['whatsapp_number'] ?? '')) ?? '',
            'ga_measurement_id'        => (string) ($input['ga_measurement_id'] ?? ''),
            'google_site_verification' => (string) ($input['google_site_verification'] ?? ''),
        ];

        $statement = $pdo->prepare('UPDATE settings SET value = ? WHERE key_name = ?');

        foreach ($settings as $key => $value) {
            $statement->execute([$value, $key]);
        }
    }

    private function buildEnv(array $input): array
    {
        $appUrl = rtrim((string) $input['app_url'], '/');

        return [
            'APP_NAME'     => 'SARCNA 2027 Convention',
            'APP_ENV'      => 'production',
            'APP_DEBUG'    => 'false',
            'APP_URL'      => $appUrl,
            'APP_TIMEZONE' => 'Africa/Johannesburg',
            'APP_KEY'      => bin2hex(random_bytes(24)),

            'DB_HOST'    => (string) $input['db_host'],
            'DB_PORT'    => (string) $input['db_port'],
            'DB_NAME'    => (string) $input['db_name'],
            'DB_USER'    => (string) $input['db_user'],
            'DB_PASS'    => (string) ($input['db_pass'] ?? ''),
            'DB_CHARSET' => 'utf8mb4',

            'PAYFAST_MODE'         => (string) $input['payfast_mode'],
            'PAYFAST_MERCHANT_ID'  => (string) ($input['payfast_merchant_id'] ?? ''),
            'PAYFAST_MERCHANT_KEY' => (string) ($input['payfast_merchant_key'] ?? ''),
            'PAYFAST_PASSPHRASE'   => (string) ($input['payfast_passphrase'] ?? ''),
            'PAYFAST_RETURN_URL'   => $appUrl . '/payment/success',
            'PAYFAST_CANCEL_URL'   => $appUrl . '/payment/cancelled',
            'PAYFAST_NOTIFY_URL'   => $appUrl . '/payment/notify',

            'MAIL_DRIVER'       => (string) $input['mail_driver'],
            'MAIL_HOST'         => (string) ($input['mail_host'] ?? ''),
            'MAIL_PORT'         => (string) ($input['mail_port'] ?? '587'),
            'MAIL_ENCRYPTION'   => (string) ($input['mail_encryption'] ?? 'tls'),
            'MAIL_USERNAME'     => (string) ($input['mail_username'] ?? ''),
            'MAIL_PASSWORD'     => (string) ($input['mail_password'] ?? ''),
            'MAIL_FROM_ADDRESS' => (string) ($input['mail_from_address'] ?: $input['contact_email']),
            'MAIL_FROM_NAME'    => 'SARCNA 2027 Convention',

            'GA_MEASUREMENT_ID'        => (string) ($input['ga_measurement_id'] ?? ''),
            'GOOGLE_SITE_VERIFICATION' => (string) ($input['google_site_verification'] ?? ''),

            'WHATSAPP_NUMBER' => preg_replace('/\D+/', '', (string) ($input['whatsapp_number'] ?? '')) ?? '',
            'CONTACT_EMAIL'   => (string) $input['contact_email'],

            'SESSION_NAME'         => 'sarcna_session',
            'SESSION_LIFETIME'     => '7200',
            'SESSION_SECURE'       => str_starts_with($appUrl, 'https') ? 'true' : 'false',
            'BOOKING_HOLD_MINUTES' => '15',
        ];
    }

    private function envPreview(array $input): string
    {
        $lines = [];

        foreach ($this->buildEnv($input) as $key => $value) {
            $lines[] = $key . '=' . (str_contains((string) $value, ' ') ? '"' . $value . '"' : $value);
        }

        return implode("\n", $lines);
    }
}

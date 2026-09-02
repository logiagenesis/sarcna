<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Config;
use App\Core\Database;
use App\Core\Mailer;
use App\Services\AuthService;
use App\Services\MailService;
use App\Services\PayFastService;
use App\Services\SettingsService;

final class SettingsController extends AdminController
{
    public function index(): string
    {
        return $this->render('admin.settings', 'Site settings', [
            'groups' => SettingsService::groups(),
        ]);
    }

    public function update(): never
    {
        $submitted = $this->request->array('settings');
        $changed   = [];

        foreach (Database::select('SELECT key_name, type, value FROM settings') as $setting) {
            $key = $setting['key_name'];

            $value = $setting['type'] === 'boolean'
                ? (isset($submitted[$key]) ? '1' : '0')
                : (string) ($submitted[$key] ?? $setting['value']);

            if ($value !== (string) $setting['value']) {
                $changed[$key] = $value;
                SettingsService::set($key, $value);
            }
        }

        SettingsService::flush();

        $this->audit('updated site settings', 'settings', null, array_keys($changed));
        $this->flashSuccess(count($changed) === 0 ? 'Nothing changed.' : count($changed) . ' setting(s) saved.');
        $this->back(url('/admin/settings'));
    }

    public function emailTemplates(): string
    {
        return $this->render('admin.email-templates', 'Email templates', [
            'templates' => Database::select('SELECT * FROM email_templates ORDER BY key_name'),
        ]);
    }

    public function saveEmailTemplate(): never
    {
        $id      = $this->request->int('id');
        $subject = trim((string) $this->request->input('subject', ''));

        if ($id <= 0 || $subject === '') {
            $this->flashError('A subject line is required.');
            $this->back();
        }

        Database::update('email_templates', [
            'subject'   => $subject,
            'is_active' => $this->request->bool('is_active') ? 1 : 0,
        ], 'id = :id', ['id' => $id]);

        $this->audit('edited an email template', 'email_template', $id);
        $this->flashSuccess('Template saved.');
        $this->back(url('/admin/settings/email-templates'));
    }

    /** Everything a committee member needs before going live, on one page. */
    public function diagnostics(): string
    {
        $root = (string) Config::get('paths.root');

        $checks = [
            ['PHP version', version_compare(PHP_VERSION, '8.1.0', '>='), PHP_VERSION],
            ['PDO MySQL', extension_loaded('pdo_mysql'), 'Database driver'],
            ['Database connection', Database::isConnected(), (string) Config::get('database.name')],
            ['OpenSSL', extension_loaded('openssl'), 'Needed for secure SMTP'],
            ['GD or Imagick', extension_loaded('gd') || extension_loaded('imagick'), 'Image resizing on upload'],
            ['cURL', function_exists('curl_init'), 'Used to validate PayFast notifications'],
            ['/storage writable', is_writable($root . '/storage'), 'Logs, cache, email queue'],
            ['/public_html/uploads writable', is_writable($root . '/public_html/uploads'), 'Admin image uploads'],
            ['.env is outside the web root', !is_file($root . '/public_html/.env'), 'Credentials must not be public'],
            ['Installer is locked', is_file((string) Config::get('paths.lock')), 'app/Config/installed.lock'],
            ['HTTPS', \App\Core\Session::isHttps(), 'Required for secure cookies and PayFast'],
            ['PayFast configured', PayFastService::isConfigured(), PayFastService::isSandbox() ? 'Sandbox mode' : 'Live mode'],
            ['PayFast passphrase set', trim((string) Config::get('payfast.passphrase', '')) !== '', 'Without it a payment notification\'s signature proves nothing'],
            ['PayFast reachable', $this->canReachPayFast(), 'Outbound HTTPS to ' . PayFastService::host()],
            ['GA4 measurement ID', (string) SettingsService::get('ga_measurement_id', '') !== '', (string) SettingsService::get('ga_measurement_id', 'not set')],
            ['Search Console tag', (string) SettingsService::get('google_site_verification', '') !== '', 'Verification meta tag'],
            ['WhatsApp number', (string) SettingsService::get('whatsapp_number', '') !== '', (string) SettingsService::get('whatsapp_number', 'not set')],
            ['Mail driver', (string) Config::get('mail.driver') !== 'log', (string) Config::get('mail.driver')],
        ];

        return $this->render('admin.diagnostics', 'Diagnostics', [
            'checks'    => $checks,
            'php'       => [
                'version'            => PHP_VERSION,
                'memory_limit'       => ini_get('memory_limit'),
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'post_max_size'      => ini_get('post_max_size'),
                'max_execution_time' => ini_get('max_execution_time'),
                'timezone'           => date_default_timezone_get(),
            ],
            'payfast'   => [
                'mode'       => (string) Config::get('payfast.mode'),
                'merchant'   => (string) Config::get('payfast.merchant_id') === '' ? 'not set' : 'set',
                'passphrase' => (string) Config::get('payfast.passphrase') === '' ? 'not set' : 'set',
                'notify_url' => (string) Config::get('payfast.notify_url'),
            ],
            'counts'    => [
                'orders'    => (int) Database::scalar('SELECT COUNT(*) FROM orders'),
                'beds'      => (int) Database::scalar('SELECT COUNT(*) FROM beds'),
                'bookings'  => (int) Database::scalar('SELECT COUNT(*) FROM bookings WHERE status = "confirmed"'),
                'products'  => (int) Database::scalar('SELECT COUNT(*) FROM products WHERE is_active = 1'),
                'mockRows'  => (int) Database::scalar('SELECT COUNT(*) FROM products WHERE is_mock = 1')
                                 + (int) Database::scalar('SELECT COUNT(*) FROM room_types WHERE is_mock = 1'),
                'users'     => (int) Database::scalar('SELECT COUNT(*) FROM users'),
            ],
            'adminEmail' => MailService::adminAddress(),
        ]);
    }

    public function sendTestEmail(): never
    {
        $to = trim((string) $this->request->input('email', '')) ?: (string) AuthService::user()['email'];

        $sent = Mailer::make()
            ->to($to)
            ->subject('SARCNA 2027 test email')
            ->template('generic', [
                'name' => '',
                'body' => "This is a test email from the SARCNA 2027 Convention website.\n\n"
                        . 'Sent at ' . date('Y-m-d H:i:s') . ' using the ' . Config::get('mail.driver') . " driver.\n"
                        . "If you received this, transactional email is working.",
            ])
            ->send();

        $this->audit('sent a test email', 'settings');

        if ($sent) {
            $this->flashSuccess('Test email sent to ' . $to . '. Check the inbox and the spam folder.');
        } else {
            $this->flashError('The test email failed. Check the SMTP settings in .env and see /storage/logs/app-*.log.');
        }

        $this->back(url('/admin/settings/diagnostics'));
    }

    public function logs(): string
    {
        $directory = (string) Config::get('paths.logs');
        $files     = glob($directory . '/*.log') ?: [];

        usort($files, static fn (string $a, string $b): int => filemtime($b) <=> filemtime($a));

        $selected = (string) $this->request->input('file', '');
        $lines    = [];

        if ($selected !== '') {
            // Only ever read from the log folder itself.
            $path = $directory . '/' . basename($selected);

            if (is_file($path)) {
                $all   = file($path, FILE_IGNORE_NEW_LINES) ?: [];
                $lines = array_slice($all, -400);
            }
        }

        return $this->render('admin.logs', 'Logs', [
            'files'    => array_map('basename', $files),
            'selected' => $selected === '' ? '' : basename($selected),
            'lines'    => array_reverse($lines),
            'audit'    => \App\Services\AuditService::recent(60),
        ]);
    }

    private function canReachPayFast(): bool
    {
        $host = PayFastService::host();

        $socket = @fsockopen('ssl://' . $host, 443, $code, $message, 5);

        if ($socket === false) {
            return false;
        }

        fclose($socket);

        return true;
    }
}

<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Database;
use App\Core\Logger;

/**
 * PayFast integration.
 *
 * Four checks run on every payment notification before an order is marked paid:
 *   1. the signature matches (with the passphrase),
 *   2. the request came from a PayFast server,
 *   3. the amount matches the order to the cent,
 *   4. PayFast itself confirms the data when we post it back.
 *
 * A customer landing on the return URL proves nothing and never marks an order
 * paid.
 */
final class PayFastService
{
    private const LIVE_HOST    = 'www.payfast.co.za';
    private const SANDBOX_HOST = 'sandbox.payfast.co.za';

    /** PayFast's published notification source addresses. */
    private const VALID_HOSTS = [
        'www.payfast.co.za',
        'sandbox.payfast.co.za',
        'w1w.payfast.co.za',
        'w2w.payfast.co.za',
    ];

    public static function isSandbox(): bool
    {
        return strtolower((string) Config::get('payfast.mode', 'sandbox')) !== 'live';
    }

    public static function host(): string
    {
        return self::isSandbox() ? self::SANDBOX_HOST : self::LIVE_HOST;
    }

    public static function processUrl(): string
    {
        return 'https://' . self::host() . '/eng/process';
    }

    public static function isConfigured(): bool
    {
        return (string) Config::get('payfast.merchant_id', '') !== ''
            && (string) Config::get('payfast.merchant_key', '') !== '';
    }

    /**
     * Build the form fields posted to PayFast. Field order matters: the
     * signature is calculated over the fields in exactly this sequence.
     */
    public static function fieldsForOrder(array $order, array $customer = []): array
    {
        $config = Config::get('payfast');

        $fields = [
            'merchant_id'   => (string) $config['merchant_id'],
            'merchant_key'  => (string) $config['merchant_key'],
            'return_url'    => self::urlOr($config['return_url'], '/payment/success'),
            'cancel_url'    => self::urlOr($config['cancel_url'], '/payment/cancelled'),
            'notify_url'    => self::urlOr($config['notify_url'], '/payment/notify'),
            'name_first'    => self::clean((string) ($order['first_name'] ?? $customer['first_name'] ?? ''), 100),
            'name_last'     => self::clean((string) ($order['last_name'] ?? $customer['last_name'] ?? ''), 100),
            'email_address' => self::clean((string) $order['email'], 100),
            'm_payment_id'  => (string) $order['reference'],
            'amount'        => number_format(((int) $order['total_cents']) / 100, 2, '.', ''),
            'item_name'     => self::clean('SARCNA 2027 Convention — Order ' . $order['reference'], 100),
            'item_descript' => self::clean(self::describe($order), 255),
            'custom_str1'   => (string) $order['reference'],
            'custom_str2'   => (string) ($order['cart_token'] ?? ''),
            'custom_int1'   => (string) $order['id'],
        ];

        $cellphone = preg_replace('/\D+/', '', (string) ($order['phone'] ?? ''));

        if (is_string($cellphone) && strlen($cellphone) >= 10) {
            $fields['cell_number'] = substr($cellphone, -10);
        }

        $fields = array_filter($fields, static fn (string $value): bool => $value !== '');

        $fields['signature'] = self::signature($fields);

        return $fields;
    }

    /** md5 of the urlencoded field string, with the passphrase appended. */
    public static function signature(array $fields, ?string $passphrase = null): string
    {
        $passphrase ??= (string) Config::get('payfast.passphrase', '');

        $pairs = [];

        foreach ($fields as $key => $value) {
            if ($key === 'signature' || $value === '' || $value === null) {
                continue;
            }

            $pairs[] = $key . '=' . urlencode(trim((string) $value));
        }

        $payload = implode('&', $pairs);

        if (trim($passphrase) !== '') {
            $payload .= '&passphrase=' . urlencode(trim($passphrase));
        }

        return md5($payload);
    }

    /* --------------------------------------------------------------- ITN */

    /**
     * Handle a payment notification. Returns a result array; the controller
     * always answers PayFast with HTTP 200 so it does not retry forever.
     *
     * @param array<string,string> $data the raw $_POST from PayFast
     */
    public static function handleNotification(array $data, string $sourceIp): array
    {
        $reference = (string) ($data['m_payment_id'] ?? $data['custom_str1'] ?? '');
        $order     = $reference === '' ? null : OrderService::findByReference($reference);
        $orderId   = $order === null ? null : (int) $order['id'];

        self::logEvent($orderId, 'itn_received', 'Notification received from ' . $sourceIp, $data);

        // 1. Signature.
        $expected  = self::signature($data);
        $signature = (string) ($data['signature'] ?? '');
        $signatureValid = hash_equals($expected, $signature);

        if (!$signatureValid) {
            self::logEvent($orderId, 'itn_bad_signature', 'Signature mismatch — notification rejected.', [
                'expected' => $expected,
                'received' => $signature,
            ]);

            self::recordPayment($order, $data, 'failed', false, $sourceIp);

            return ['ok' => false, 'reason' => 'invalid_signature'];
        }

        // 2. Source address.
        if (!self::isValidSource($sourceIp)) {
            self::logEvent($orderId, 'itn_bad_source', 'Notification came from an unexpected address: ' . $sourceIp);

            self::recordPayment($order, $data, 'failed', true, $sourceIp);

            return ['ok' => false, 'reason' => 'invalid_source'];
        }

        if ($order === null) {
            self::logEvent(null, 'itn_unknown_order', 'No order matches reference ' . $reference, $data);

            return ['ok' => false, 'reason' => 'unknown_order'];
        }

        // 3. Amount.
        $grossAmount = (float) ($data['amount_gross'] ?? 0);
        $expectedAmount = ((int) $order['total_cents']) / 100;

        if (abs($grossAmount - $expectedAmount) > 0.01) {
            self::logEvent($orderId, 'itn_amount_mismatch', sprintf(
                'Amount mismatch: PayFast sent R%.2f, order total is R%.2f.',
                $grossAmount,
                $expectedAmount
            ), $data);

            self::recordPayment($order, $data, 'failed', true, $sourceIp);
            OrderService::markFailed($order, 'Payment amount did not match the order total.');

            return ['ok' => false, 'reason' => 'amount_mismatch'];
        }

        // 4. Ask PayFast to confirm the payload.
        if (!self::confirmWithPayFast($data)) {
            self::logEvent($orderId, 'itn_not_confirmed', 'PayFast did not validate the notification payload.');

            self::recordPayment($order, $data, 'failed', true, $sourceIp);

            return ['ok' => false, 'reason' => 'not_validated'];
        }

        $status  = strtoupper((string) ($data['payment_status'] ?? ''));
        $payment = self::recordPayment(
            $order,
            $data,
            $status === 'COMPLETE' ? 'complete' : ($status === 'CANCELLED' ? 'cancelled' : 'failed'),
            true,
            $sourceIp
        );

        if ($status === 'COMPLETE') {
            OrderService::markPaid($order, ['cart_token' => (string) ($data['custom_str2'] ?? $order['cart_token'] ?? ''), 'payment' => $payment]);

            return ['ok' => true, 'status' => 'complete'];
        }

        if ($status === 'CANCELLED') {
            OrderService::markCancelled($order, 'PayFast reported the payment as cancelled.');

            return ['ok' => true, 'status' => 'cancelled'];
        }

        OrderService::markFailed($order, 'PayFast reported payment status: ' . $status);

        return ['ok' => true, 'status' => strtolower($status)];
    }

    public static function isValidSource(string $ip): bool
    {
        // Sandbox testing sometimes runs from a fixed office address; the
        // signature check above is still enforced there.
        if (SettingsService::bool('payfast_skip_ip_check', false)) {
            return true;
        }

        $allowed = [];

        foreach (self::VALID_HOSTS as $host) {
            $records = @gethostbynamel($host);

            if (is_array($records)) {
                $allowed = array_merge($allowed, $records);
            }
        }

        if ($allowed === []) {
            // DNS is unavailable — do not fail an otherwise valid payment, but
            // make the gap visible in the payment log.
            Logger::payment('Could not resolve PayFast hosts for the source-IP check.', ['ip' => $ip]);

            return true;
        }

        return in_array($ip, array_unique($allowed), true);
    }

    private static function confirmWithPayFast(array $data): bool
    {
        $payload = http_build_query(array_diff_key($data, ['signature' => true]));
        $url     = 'https://' . self::host() . '/eng/query/validate';

        $response = self::post($url, $payload);

        if ($response === null) {
            Logger::payment('PayFast validation request failed; treating as unconfirmed.');

            return false;
        }

        return str_starts_with(strtoupper(trim($response)), 'VALID');
    }

    private static function post(string $url, string $payload): ?string
    {
        if (function_exists('curl_init')) {
            $curl = curl_init($url);
            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_TIMEOUT        => 20,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_USERAGENT      => 'SARCNA-2027/1.0',
            ]);

            $response = curl_exec($curl);
            $error    = curl_error($curl);
            curl_close($curl);

            if ($response === false) {
                Logger::payment('cURL error validating with PayFast: ' . $error);

                return null;
            }

            return (string) $response;
        }

        $context = stream_context_create([
            'http' => [
                'method'        => 'POST',
                'header'        => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content'       => $payload,
                'timeout'       => 20,
                'ignore_errors' => true,
            ],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);

        $response = @file_get_contents($url, false, $context);

        return $response === false ? null : (string) $response;
    }

    private static function recordPayment(?array $order, array $data, string $status, bool $signatureValid, string $sourceIp): ?array
    {
        if ($order === null) {
            return null;
        }

        $providerReference = (string) ($data['pf_payment_id'] ?? '');

        $existingId = $providerReference === '' ? null : Database::scalar(
            'SELECT id FROM payments WHERE provider_reference = ? AND order_id = ? LIMIT 1',
            [$providerReference, (int) $order['id']]
        );

        $payload = [
            'order_id'           => (int) $order['id'],
            'provider'           => 'payfast',
            'provider_reference' => $providerReference !== '' ? $providerReference : null,
            'amount_cents'       => (int) round(((float) ($data['amount_gross'] ?? 0)) * 100),
            'fee_cents'          => (int) round(abs((float) ($data['amount_fee'] ?? 0)) * 100),
            'status'             => $status,
            'signature_valid'    => $signatureValid ? 1 : 0,
            'source_ip'          => $sourceIp,
            'payload'            => json_encode($data, JSON_UNESCAPED_UNICODE),
        ];

        if ($existingId !== null) {
            Database::update('payments', $payload, 'id = :id', ['id' => (int) $existingId]);

            return Database::first('SELECT * FROM payments WHERE id = ?', [(int) $existingId]);
        }

        $id = Database::insert('payments', $payload);

        return Database::first('SELECT * FROM payments WHERE id = ?', [$id]);
    }

    public static function logEvent(?int $orderId, string $event, string $message, mixed $payload = null): void
    {
        Database::insert('payment_logs', [
            'order_id'  => $orderId,
            'event'     => $event,
            'message'   => mb_substr($message, 0, 500),
            'payload'   => $payload === null ? null : json_encode($payload, JSON_UNESCAPED_UNICODE),
            'source_ip' => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
        ]);

        Logger::payment($event . ': ' . $message);
    }

    /** Mark a pending payment as initiated when the customer is redirected. */
    public static function recordRedirect(array $order): void
    {
        Database::insert('payments', [
            'order_id'     => (int) $order['id'],
            'provider'     => 'payfast',
            'amount_cents' => (int) $order['total_cents'],
            'status'       => 'initiated',
            'source_ip'    => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
        ]);

        self::logEvent((int) $order['id'], 'redirect', 'Customer redirected to PayFast (' . (self::isSandbox() ? 'sandbox' : 'live') . ').');
    }

    private static function describe(array $order): string
    {
        $items = OrderService::items((int) $order['id']);
        $parts = array_map(static fn (array $item): string => $item['quantity'] . ' × ' . $item['description'], array_slice($items, 0, 4));

        if (count($items) > 4) {
            $parts[] = 'and ' . (count($items) - 4) . ' more';
        }

        return $parts === [] ? 'SARCNA 2027 Convention' : implode(', ', $parts);
    }

    private static function urlOr(mixed $configured, string $fallbackPath): string
    {
        $configured = (string) $configured;

        return $configured !== '' ? $configured : url($fallbackPath);
    }

    private static function clean(string $value, int $length): string
    {
        $value = preg_replace('/[\r\n\t]+/', ' ', $value) ?? $value;

        return mb_substr(trim($value), 0, $length);
    }
}

<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Dependency-free SMTP client (AUTH LOGIN / PLAIN, STARTTLS or implicit TLS)
 * with a mail() fallback and a file queue for local development.
 *
 * cPanel mailbox SMTP is the intended driver: host mail.<domain>, port 587, TLS.
 */
final class Mailer
{
    /** @var array<int, array{path:string,name:string,mime:string}> */
    private array $attachments = [];
    private array $to = [];
    private array $replyTo = [];
    private string $subject = '';
    private string $html = '';
    private string $text = '';

    public static function make(): self
    {
        return new self();
    }

    public function to(string $email, string $name = ''): self
    {
        $this->to[] = ['email' => $email, 'name' => $name];

        return $this;
    }

    public function replyTo(string $email, string $name = ''): self
    {
        $this->replyTo = ['email' => $email, 'name' => $name];

        return $this;
    }

    public function subject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function html(string $html): self
    {
        $this->html = $html;

        if ($this->text === '') {
            $this->text = trim(html_entity_decode(strip_tags(preg_replace('/<(br|\/p|\/div|\/tr)[^>]*>/i', "\n", $html) ?? $html), ENT_QUOTES, 'UTF-8'));
        }

        return $this;
    }

    public function text(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function attach(string $path, string $name = '', string $mime = 'application/octet-stream'): self
    {
        if (is_readable($path)) {
            $this->attachments[] = ['path' => $path, 'name' => $name ?: basename($path), 'mime' => $mime];
        }

        return $this;
    }

    /** Render an email view inside the branded email layout. */
    public function template(string $template, array $data = []): self
    {
        $body = View::partial('emails.' . $template, $data);

        return $this->html(View::partial('emails.layout', array_merge($data, ['emailBody' => $body])));
    }

    public function send(): bool
    {
        if ($this->to === []) {
            return false;
        }

        $driver = (string) Config::get('mail.driver', 'smtp');

        try {
            $sent = match ($driver) {
                'smtp'  => $this->sendSmtp(),
                'mail'  => $this->sendMailFunction(),
                default => $this->queueToDisk(),
            };
        } catch (\Throwable $e) {
            Logger::error('Mail send failed: ' . $e->getMessage(), ['subject' => $this->subject]);
            $this->queueToDisk();

            return false;
        }

        if (!$sent) {
            Logger::warning('Mail not delivered, queued to disk.', ['subject' => $this->subject]);
            $this->queueToDisk();
        }

        return $sent;
    }

    /* ------------------------------------------------------------ drivers */

    private function queueToDisk(): bool
    {
        $directory = Config::get('paths.email_queue');

        if (!is_dir($directory) && !@mkdir($directory, 0750, true) && !is_dir($directory)) {
            return false;
        }

        $file = sprintf('%s/%s-%s.eml', rtrim((string) $directory, '/'), date('Ymd-His'), bin2hex(random_bytes(4)));

        return file_put_contents($file, $this->buildHeaders() . "\r\n" . $this->buildBody()) !== false;
    }

    private function sendMailFunction(): bool
    {
        $recipients = implode(', ', array_map(fn (array $r): string => $this->formatAddress($r['email'], $r['name']), $this->to));

        return mail($recipients, $this->encodeHeader($this->subject), $this->buildBody(), $this->buildHeaders(false));
    }

    private function sendSmtp(): bool
    {
        $config     = Config::get('mail');
        $host       = (string) $config['host'];
        $port       = (int) $config['port'];
        $encryption = strtolower((string) $config['encryption']);

        if ($host === '') {
            return false;
        }

        $transport = $encryption === 'ssl' ? 'ssl://' . $host : $host;
        $context   = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'SNI_enabled' => true]]);

        $socket = @stream_socket_client(
            $transport . ':' . $port,
            $errorCode,
            $errorMessage,
            15,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if ($socket === false) {
            Logger::error('SMTP connect failed', ['host' => $host, 'port' => $port, 'error' => $errorMessage]);

            return false;
        }

        stream_set_timeout($socket, 20);

        try {
            $this->expect($socket, 220);

            $hostname = (string) (parse_url((string) Config::get('app.url'), PHP_URL_HOST) ?: 'localhost');
            $this->command($socket, 'EHLO ' . $hostname, 250);

            if ($encryption === 'tls') {
                $this->command($socket, 'STARTTLS', 220);

                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new \RuntimeException('STARTTLS negotiation failed.');
                }

                $this->command($socket, 'EHLO ' . $hostname, 250);
            }

            if (($config['username'] ?? '') !== '') {
                $this->command($socket, 'AUTH LOGIN', 334);
                $this->command($socket, base64_encode((string) $config['username']), 334);
                $this->command($socket, base64_encode((string) $config['password']), 235);
            }

            $this->command($socket, 'MAIL FROM:<' . $config['from_address'] . '>', 250);

            foreach ($this->to as $recipient) {
                $this->command($socket, 'RCPT TO:<' . $recipient['email'] . '>', 250);
            }

            $this->command($socket, 'DATA', 354);

            $message = $this->buildHeaders() . "\r\n" . $this->buildBody();
            // Dot-stuffing, per RFC 5321.
            $message = preg_replace('/^\./m', '..', $message) ?? $message;

            fwrite($socket, $message . "\r\n.\r\n");
            $this->expect($socket, 250);

            $this->command($socket, 'QUIT', 221);
        } catch (\Throwable $e) {
            @fclose($socket);
            Logger::error('SMTP error: ' . $e->getMessage(), ['subject' => $this->subject]);

            return false;
        }

        @fclose($socket);

        return true;
    }

    /* ------------------------------------------------------------ helpers */

    /** @param resource $socket */
    private function command($socket, string $command, int $expected): void
    {
        fwrite($socket, $command . "\r\n");
        $this->expect($socket, $expected);
    }

    /** @param resource $socket */
    private function expect($socket, int $expected): void
    {
        $response = '';

        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;

            // Multi-line replies keep a hyphen in the 4th column.
            if (strlen($line) < 4 || $line[3] !== '-') {
                break;
            }
        }

        $code = (int) substr(trim($response), 0, 3);

        if ($code !== $expected) {
            throw new \RuntimeException(sprintf('Expected SMTP %d, got: %s', $expected, trim($response)));
        }
    }

    private function boundary(): string
    {
        static $boundary = null;

        return $boundary ??= 'sarcna-' . bin2hex(random_bytes(12));
    }

    private function buildHeaders(bool $includeToAndSubject = true): string
    {
        $config  = Config::get('mail');
        $headers = [];

        $headers[] = 'From: ' . $this->formatAddress((string) $config['from_address'], (string) $config['from_name']);

        if ($includeToAndSubject) {
            $headers[] = 'To: ' . implode(', ', array_map(fn (array $r): string => $this->formatAddress($r['email'], $r['name']), $this->to));
            $headers[] = 'Subject: ' . $this->encodeHeader($this->subject);
            $headers[] = 'Date: ' . date('r');
            $headers[] = 'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . (parse_url((string) Config::get('app.url'), PHP_URL_HOST) ?: 'sarcna.org.za') . '>';
        }

        if ($this->replyTo !== []) {
            $headers[] = 'Reply-To: ' . $this->formatAddress($this->replyTo['email'], $this->replyTo['name']);
        }

        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'X-Mailer: SARCNA 2027 Convention';

        if ($this->attachments !== []) {
            $headers[] = 'Content-Type: multipart/mixed; boundary="mixed-' . $this->boundary() . '"';
        } else {
            $headers[] = 'Content-Type: multipart/alternative; boundary="alt-' . $this->boundary() . '"';
        }

        return implode("\r\n", $headers) . "\r\n";
    }

    private function buildBody(): string
    {
        $boundary    = $this->boundary();
        $alternative = "--alt-{$boundary}\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($this->text)) . "\r\n"
            . "--alt-{$boundary}\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($this->html)) . "\r\n"
            . "--alt-{$boundary}--\r\n";

        if ($this->attachments === []) {
            return $alternative;
        }

        $body = "--mixed-{$boundary}\r\n"
            . "Content-Type: multipart/alternative; boundary=\"alt-{$boundary}\"\r\n\r\n"
            . $alternative . "\r\n";

        foreach ($this->attachments as $attachment) {
            $body .= "--mixed-{$boundary}\r\n"
                . 'Content-Type: ' . $attachment['mime'] . '; name="' . $attachment['name'] . "\"\r\n"
                . "Content-Transfer-Encoding: base64\r\n"
                . 'Content-Disposition: attachment; filename="' . $attachment['name'] . "\"\r\n\r\n"
                . chunk_split(base64_encode((string) file_get_contents($attachment['path']))) . "\r\n";
        }

        return $body . "--mixed-{$boundary}--\r\n";
    }

    private function formatAddress(string $email, string $name): string
    {
        return $name === '' ? $email : sprintf('%s <%s>', $this->encodeHeader($name), $email);
    }

    private function encodeHeader(string $value): string
    {
        return preg_match('/[\x80-\xFF]/', $value) === 1
            ? '=?UTF-8?B?' . base64_encode($value) . '?='
            : $value;
    }
}

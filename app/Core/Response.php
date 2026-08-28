<?php
declare(strict_types=1);

namespace App\Core;

final class Response
{
    public static function redirect(string $url, int $status = 302): never
    {
        header('Location: ' . $url, true, $status);
        exit;
    }

    public static function back(string $fallback = '/'): never
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $host    = $_SERVER['HTTP_HOST'] ?? '';

        // Only follow a referer that points back at this host.
        if ($referer !== '' && $host !== '' && str_contains((string) parse_url($referer, PHP_URL_HOST), $host)) {
            self::redirect($referer);
        }

        self::redirect($fallback);
    }

    public static function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function text(string $body, int $status = 200, string $contentType = 'text/plain; charset=utf-8'): never
    {
        http_response_code($status);
        header('Content-Type: ' . $contentType);
        echo $body;
        exit;
    }

    public static function download(string $filename, string $content, string $contentType = 'text/csv; charset=utf-8'): never
    {
        header('Content-Type: ' . $contentType);
        header('Content-Disposition: attachment; filename="' . str_replace('"', '', $filename) . '"');
        header('Content-Length: ' . strlen($content));
        header('Cache-Control: no-store');
        echo $content;
        exit;
    }
}

<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/** Database-backed throttling for logins, password resets and public forms. */
final class RateLimiter
{
    public static function tooManyAttempts(string $key, int $maxAttempts, int $decaySeconds = 900): bool
    {
        self::purge();

        $bucket = hash('sha256', $key);
        $row    = Database::first('SELECT * FROM rate_limits WHERE bucket = ? LIMIT 1', [$bucket]);

        if ($row === null) {
            return false;
        }

        if (strtotime((string) $row['expires_at']) < time()) {
            Database::delete('rate_limits', 'bucket = ?', [$bucket]);

            return false;
        }

        return (int) $row['attempts'] >= $maxAttempts;
    }

    public static function hit(string $key, int $decaySeconds = 900): int
    {
        $bucket    = hash('sha256', $key);
        $expiresAt = date('Y-m-d H:i:s', time() + $decaySeconds);

        $row = Database::first('SELECT * FROM rate_limits WHERE bucket = ? LIMIT 1', [$bucket]);

        if ($row === null) {
            Database::insert('rate_limits', ['bucket' => $bucket, 'attempts' => 1, 'expires_at' => $expiresAt]);

            return 1;
        }

        $attempts = strtotime((string) $row['expires_at']) < time() ? 1 : (int) $row['attempts'] + 1;

        Database::update('rate_limits', ['attempts' => $attempts, 'expires_at' => $expiresAt], 'bucket = :bucket', ['bucket' => $bucket]);

        return $attempts;
    }

    public static function clear(string $key): void
    {
        Database::delete('rate_limits', 'bucket = ?', [hash('sha256', $key)]);
    }

    public static function secondsRemaining(string $key): int
    {
        $row = Database::first('SELECT expires_at FROM rate_limits WHERE bucket = ? LIMIT 1', [hash('sha256', $key)]);

        if ($row === null) {
            return 0;
        }

        return max(0, strtotime((string) $row['expires_at']) - time());
    }

    private static function purge(): void
    {
        // Cheap opportunistic cleanup — roughly one request in twenty.
        if (random_int(1, 20) === 1) {
            Database::delete('rate_limits', 'expires_at < DATE_SUB(NOW(), INTERVAL 1 DAY)');
        }
    }
}

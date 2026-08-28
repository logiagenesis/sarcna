<?php
declare(strict_types=1);

namespace App\Core;

/** Daily rotating file logger writing to /storage/logs. */
final class Logger
{
    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('WARNING', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    public static function payment(string $message, array $context = []): void
    {
        self::write('PAYMENT', $message, $context, 'payfast');
    }

    private static function write(string $level, string $message, array $context, string $channel = 'app'): void
    {
        $directory = Config::get('paths.logs', dirname(__DIR__, 2) . '/storage/logs');

        if (!is_dir($directory) && !@mkdir($directory, 0750, true) && !is_dir($directory)) {
            return;
        }

        $line = sprintf(
            "[%s] %s: %s%s%s",
            date('Y-m-d H:i:s'),
            $level,
            $message,
            $context === [] ? '' : ' ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            PHP_EOL
        );

        @file_put_contents(
            sprintf('%s/%s-%s.log', rtrim($directory, '/'), $channel, date('Y-m-d')),
            $line,
            FILE_APPEND | LOCK_EX
        );
    }
}

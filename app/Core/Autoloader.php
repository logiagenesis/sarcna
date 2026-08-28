<?php
declare(strict_types=1);

namespace App\Core;

/** PSR-4 style autoloader for the App\ namespace — no Composer required. */
final class Autoloader
{
    public static function register(string $baseDirectory): void
    {
        spl_autoload_register(static function (string $class) use ($baseDirectory): void {
            if (!str_starts_with($class, 'App\\')) {
                return;
            }

            $relative = str_replace('\\', '/', substr($class, 4));
            $path     = rtrim($baseDirectory, '/') . '/' . $relative . '.php';

            if (is_file($path)) {
                require_once $path;
            }
        });
    }
}

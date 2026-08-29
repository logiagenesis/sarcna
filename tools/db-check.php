<?php
declare(strict_types=1);

/**
 * Can this machine reach the configured database?
 *
 *   php tools/db-check.php
 *
 * Prints one line and exits 0 or 1. It exists so that scripts — notably
 * tools/run-audit.ps1 on Windows — can ask the question without embedding PHP
 * inside a shell string, where quoting differs between platforms and breaks
 * silently.
 *
 * It never prints the password.
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Config;
use App\Core\Database;

if (PHP_SAPI !== 'cli') {
    exit("Command line only.\n");
}

try {
    Database::scalar('SELECT 1');

    printf(
        "OK  connected to %s on %s:%s as %s\n",
        (string) Config::get('database.name'),
        (string) Config::get('database.host'),
        (string) Config::get('database.port'),
        (string) Config::get('database.user')
    );

    $tables = (int) Database::scalar(
        'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()'
    );

    printf("    %d table(s) present%s\n", $tables, $tables === 0 ? ' — not installed yet' : '');

    exit(0);
} catch (\Throwable $e) {
    printf("FAIL %s\n", $e->getMessage());
    exit(1);
}

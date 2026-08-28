<?php
declare(strict_types=1);

/**
 * Application bootstrap. Loaded by public_html/index.php and by the CLI tools
 * in /database. Requires nothing beyond a stock PHP 8.2+ install.
 */

if (version_compare(PHP_VERSION, '8.1.0', '<')) {
    http_response_code(500);
    exit('This application requires PHP 8.1 or newer. This server runs PHP ' . PHP_VERSION . '.');
}

define('SARCNA_START', microtime(true));

$root = dirname(__DIR__);

require_once $root . '/app/Core/Autoloader.php';

App\Core\Autoloader::register($root . '/app');

App\Core\Env::load($root . '/.env');
App\Core\Config::load(require $root . '/app/Config/config.php');

date_default_timezone_set((string) App\Core\Config::get('app.timezone', 'Africa/Johannesburg'));
setlocale(LC_TIME, 'en_ZA.UTF-8', 'en_ZA', 'en_US.UTF-8');
mb_internal_encoding('UTF-8');

require_once $root . '/app/Helpers/functions.php';

if (App\Core\Config::get('app.debug')) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
    ini_set('display_errors', '0');
}

ini_set('log_errors', '1');
ini_set('error_log', $root . '/storage/logs/php-' . date('Y-m-d') . '.log');

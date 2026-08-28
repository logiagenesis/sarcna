<?php
/**
 * Router for PHP's built-in server, for local development only:
 *
 *   php -S 127.0.0.1:8000 -t public_html tools/dev-router.php
 *
 * Production runs on Apache with public_html/.htaccess; this file is never used there.
 */
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . '/../public_html' . $path;

if ($path !== '/' && is_file($file)) {
    return false;
}

require __DIR__ . '/../public_html/index.php';

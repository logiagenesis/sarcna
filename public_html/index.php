<?php
declare(strict_types=1);

/**
 * SARCNA 2027 Convention — front controller.
 *
 * Every public request enters here. Upload this file's parent folder to a
 * cPanel account and point the domain at /public_html; nothing else is needed.
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';

(new App\Core\Kernel())->handle();

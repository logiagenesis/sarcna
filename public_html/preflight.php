<?php
/**
 * SARCNA 2027 — deployment preflight.
 *
 * Open this in a browser immediately after uploading, BEFORE running /install.
 * It answers, with evidence rather than assumption, the questions that a
 * failed deployment leaves you guessing at:
 *
 *   - is the folder layout right, or is this the flat layout that returns 500?
 *   - did the upload actually include the hidden .htaccess files?
 *   - is .env present, and is it reachable from the web (it must not be)?
 *   - do the private folders refuse HTTP requests?
 *   - does the database actually connect with the credentials in .env?
 *
 * It exists because SSH and the cPanel API are unavailable on this hosting, so
 * `php tools/audit.php` cannot be run on the server. This is the browser
 * equivalent of the first half of that audit.
 *
 * It deliberately depends on nothing: no bootstrap, no autoloader, no
 * database. It still works when the application itself is returning 500 —
 * which is exactly when it is needed.
 *
 * DELETE IT when you are done. There is a button at the bottom that does it.
 */

declare(strict_types=1);

/* ------------------------------------------------------------------ setup */

$appRoot   = dirname(__DIR__);
$webRoot   = __DIR__;
$results   = [];
$selfName  = basename(__FILE__);

/** @param 'pass'|'fail'|'warn' $state */
function result(string $group, string $label, string $state, string $evidence = '', string $fix = ''): void
{
    global $results;

    $results[$group][] = compact('label', 'state', 'evidence', 'fix');
}

function ok(string $g, string $l, bool $c, string $e = '', string $fix = ''): void
{
    result($g, $l, $c ? 'pass' : 'fail', $e, $c ? '' : $fix);
}

/* --------------------------------------------------- self-delete handling */

if (($_POST['action'] ?? '') === 'delete-me' && ($_POST['confirm'] ?? '') === 'yes') {
    $gone = @unlink(__FILE__);

    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><meta charset="utf-8"><title>Preflight</title>'
        . '<body style="font:16px/1.6 system-ui;max-width:40rem;margin:4rem auto;padding:0 1rem">'
        . ($gone
            ? '<h1>Deleted.</h1><p>preflight.php has removed itself. This page will 404 if you reload it — that is the correct result.</p>'
            : '<h1>Could not delete.</h1><p>Delete <code>public_html/' . htmlspecialchars($selfName) . '</code> by hand in cPanel File Manager.</p>')
        . '</body>';
    exit;
}

/* ------------------------------------------------------- 1. PHP and server */

$phpOk = PHP_VERSION_ID >= 80100;
ok('PHP and server', 'PHP is 8.1 or newer', $phpOk, 'running ' . PHP_VERSION,
   'Set the PHP version in cPanel → MultiPHP Manager to 8.1 or higher.');

foreach (['pdo_mysql', 'mbstring', 'json', 'gd', 'openssl', 'fileinfo', 'curl'] as $ext) {
    ok('PHP and server', "Extension {$ext} is loaded", extension_loaded($ext), '',
       "Enable {$ext} in cPanel → Select PHP Version → Extensions.");
}

$server = $_SERVER['SERVER_SOFTWARE'] ?? 'unknown';
result('PHP and server', 'Web server', 'pass', $server);

/* --------------------------------------------------------- 2. the layout */

/*
 * This is the check that matters most. public_html/index.php requires
 * ../app/bootstrap.php — so app/ must be the PARENT's sibling, not this
 * folder's sibling. Flattening the two produces a silent HTTP 500 with no
 * log entry, which is exactly what happened on the first deployment attempt.
 */
$bootstrap = $appRoot . '/app/bootstrap.php';
$flat      = is_file($webRoot . '/app/bootstrap.php');

ok('Folder layout', 'app/bootstrap.php is one level above the web root', is_file($bootstrap),
   'looked for: ' . $bootstrap,
   'The layout is wrong. app/ database/ storage/ tools/ docs/ and .env must sit in the PARENT of this folder, and only public_html/ may be the document root.');

if ($flat) {
    result('Folder layout', 'The flat layout that causes HTTP 500 is NOT present', 'fail',
        'found app/ inside the web root at ' . $webRoot . '/app',
        'Move app/ database/ storage/ tools/ docs/ and .env UP one level, so they are siblings of public_html/, not children of it.');
} else {
    result('Folder layout', 'The flat layout that causes HTTP 500 is NOT present', 'pass');
}

foreach (['app', 'database', 'storage', 'tools', 'docs'] as $dir) {
    ok('Folder layout', "{$dir}/ is present in the application root", is_dir($appRoot . '/' . $dir),
        $appRoot . '/' . $dir, 'Upload it. The deployment is incomplete without it.');
}

ok('Folder layout', 'index.php is inside the web root', is_file($webRoot . '/index.php'), $webRoot . '/index.php');

/* ------------------------------------- 3. hidden files survived the upload */

/*
 * Windows Explorer's "Send to → Compressed folder" silently drops dotfiles.
 * When .htaccess is missing, LiteSpeed looks for a real directory called
 * "install" and returns 404 instead of routing to the front controller.
 */
$htWeb  = $webRoot . '/.htaccess';
$htRoot = $appRoot . '/.htaccess';

ok('Hidden files', 'public_html/.htaccess survived the upload', is_file($htWeb), $htWeb,
   'Missing. Your upload dropped hidden files (Windows zip does this). Re-upload using cPanel Git Version Control, or 7-Zip with hidden files included.');

ok('Hidden files', 'Application-root .htaccess survived the upload', is_file($htRoot), $htRoot,
   'Missing. Without it, .env is web-reachable on this hosting. Re-upload including hidden files.');

foreach (['app', 'database', 'storage', 'tools', 'docs'] as $dir) {
    ok('Hidden files', "{$dir}/.htaccess survived the upload", is_file($appRoot . '/' . $dir . '/.htaccess'),
        '', 'Missing. Re-upload including hidden files.');
}

ok('Hidden files', '.env exists in the application root', is_file($appRoot . '/.env'), $appRoot . '/.env',
   'Copy .env.example to .env in the application root and fill in the database details. Do not invent key names — use the ones in .env.example.');

ok('Hidden files', '.env.example is present to copy from', is_file($appRoot . '/.env.example'), '',
   'Missing. Fetch it from github.com/logiagenesis/sarcna — do not guess the key names.');

/* ----------------------------------------------------------- 4. writable */

foreach (['storage', 'storage/logs', 'storage/cache', 'storage/email-queue', 'public_html/uploads'] as $dir) {
    $path = $appRoot . '/' . $dir;
    ok('Permissions', "{$dir}/ is writable", is_dir($path) && is_writable($path), $path,
       "Set permissions to 0755 on {$dir} in cPanel File Manager.");
}

/* ---------------------------------------------------------- 5. database */

$envPath = $appRoot . '/.env';

if (is_file($envPath)) {
    $env = [];

    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$k, $v] = explode('=', $line, 2);
        $env[trim($k)] = trim(trim($v), "\"'");
    }

    $missing = array_values(array_filter(
        ['DB_HOST', 'DB_NAME', 'DB_USER', 'APP_URL'],
        static fn (string $k): bool => ($env[$k] ?? '') === ''
    ));

    ok('Database', 'The required .env keys are present and filled in', $missing === [],
        $missing === [] ? 'DB_HOST, DB_NAME, DB_USER and APP_URL are set' : 'empty or missing: ' . implode(', ', $missing),
        'Fill these in from .env.example. Never invent key names.');

    if ($missing === []) {
        try {
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $env['DB_HOST'], $env['DB_PORT'] ?? '3306', $env['DB_NAME']);

            $pdo = new PDO($dsn, $env['DB_USER'], $env['DB_PASS'] ?? '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5,
            ]);

            result('Database', 'The database connects with the credentials in .env', 'pass',
                'connected to ' . $env['DB_NAME'] . ' as ' . $env['DB_USER']);

            $tables = (int) $pdo->query(
                'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()'
            )->fetchColumn();

            result('Database', 'Installation state', 'pass',
                $tables === 0
                    ? 'empty — ready for /install'
                    : "{$tables} tables present" . ($tables >= 44 ? ' (installed)' : ' (PARTIAL — expected 44)'));
        } catch (PDOException $e) {
            // Never echo the password; the message can contain the DSN.
            result('Database', 'The database connects with the credentials in .env', 'fail',
                'SQLSTATE ' . $e->getCode(),
                'Check the database name, user and password in cPanel → MySQL Databases, and that the user is added to the database with ALL PRIVILEGES.');
        }
    }
} else {
    result('Database', 'The database connects with the credentials in .env', 'fail',
        '.env not found, so nothing to test with', 'Create .env first.');
}

/* ------------------------------------------- 6. are the secrets exposed? */

/*
 * Asked over real HTTP against this very site, because "the .htaccess is
 * there" is not the same claim as "the request is refused".
 */
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? '';
$baseUrl = $host === '' ? '' : $scheme . '://' . $host;

if ($baseUrl !== '' && function_exists('curl_init')) {
    // The application root is one level above the web root, so from the web it
    // is the parent path of whatever folder this site is served from.
    $probes = [
        '/../.env'                 => 'The .env file',
        '/../app/bootstrap.php'    => 'Application source',
        '/../database/schema.sql'  => 'The database schema',
        '/../tools/audit.php'      => 'The tooling',
        '/../storage/logs/'        => 'The log folder',
    ];

    foreach ($probes as $path => $label) {
        $ch = curl_init($baseUrl . $path);
        curl_setopt_array($ch, [
            CURLOPT_NOBODY         => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_FOLLOWLOCATION => false,
        ]);

        curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        $blocked = $code !== 200;

        ok('Secrets are not served', "{$label} refuses HTTP requests", $blocked,
            'HTTP ' . ($code ?: 'no response'),
            'This is served over the web and must not be. Check the application-root .htaccess exists and that the document root is public_html/, not the application root.');
    }
} else {
    result('Secrets are not served', 'Live HTTP checks', 'warn',
        'curl is unavailable, so this could not be tested from here',
        'Test by hand: requesting /../.env from a browser must NOT return the file.');
}

/* ------------------------------------------------------- 7. the front door */

$lock = $appRoot . '/app/Config/installed.lock';

result('Next step', 'Installer', 'pass',
    is_file($lock)
        ? 'Already installed — /install now returns HTTP 410, which is correct.'
        : 'Not installed yet. Once every check above passes, open /install.');

/* ------------------------------------------------------------- rendering */

$counts = ['pass' => 0, 'fail' => 0, 'warn' => 0];

foreach ($results as $rows) {
    foreach ($rows as $r) {
        $counts[$r['state']]++;
    }
}

$verdict = $counts['fail'] === 0;

header('Content-Type: text/html; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');
?><!doctype html>
<html lang="en">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Deployment preflight — SARCNA 2027</title>
<style>
  :root {
    --ink: #17211d; --soft: #5d6a64; --line: #e2ddd2; --ground: #faf7f1;
    --card: #fff; --pass: #2f7d4f; --fail: #b4403a; --warn: #b8873a; --deep: #173d2f;
  }
  * { box-sizing: border-box; }
  body { margin: 0; background: var(--ground); color: var(--ink);
         font: 15px/1.6 system-ui, -apple-system, "Segoe UI", sans-serif; }
  .wrap { width: min(100% - 2rem, 60rem); margin-inline: auto; }
  header { background: var(--deep); color: #fff6e7; padding: 2rem 0; }
  header h1 { margin: 0 0 .4rem; font-size: 1.6rem; }
  header p { margin: 0; color: rgba(255,246,231,.8); }
  .verdict { margin: 1.5rem 0; padding: 1.1rem 1.3rem; border-radius: 8px;
             border-left: 5px solid; background: var(--card); }
  .verdict.good { border-color: var(--pass); }
  .verdict.bad  { border-color: var(--fail); }
  .verdict h2 { margin: 0 0 .3rem; font-size: 1.2rem; }
  .verdict p { margin: 0; color: var(--soft); }
  h3 { margin: 2rem 0 .6rem; font-size: 1rem; text-transform: uppercase;
       letter-spacing: .1em; color: var(--soft); }
  table { width: 100%; border-collapse: collapse; background: var(--card);
          border: 1px solid var(--line); border-radius: 8px; overflow: hidden; }
  td { padding: .6rem .8rem; border-bottom: 1px solid var(--line); vertical-align: top; }
  tr:last-child td { border-bottom: 0; }
  td.state { width: 4.5rem; font-weight: 700; font-size: .8rem; letter-spacing: .05em; }
  .pass { color: var(--pass); } .fail { color: var(--fail); } .warn { color: var(--warn); }
  .evi { display: block; color: var(--soft); font-size: .82rem;
         font-family: ui-monospace, Menlo, Consolas, monospace; word-break: break-all; margin-top: .2rem; }
  .fix { display: block; margin-top: .35rem; padding: .5rem .7rem; background: #fdf3f2;
         border-left: 3px solid var(--fail); font-size: .87rem; }
  footer { margin: 3rem 0 4rem; padding: 1.3rem; background: #fdf3f2;
           border: 1px solid #e8c9c6; border-radius: 8px; }
  footer h3 { margin-top: 0; color: var(--fail); }
  button { font: inherit; font-weight: 600; padding: .6rem 1.1rem; border: 0;
           border-radius: 6px; background: var(--fail); color: #fff; cursor: pointer; }
</style>

<header><div class="wrap">
  <h1>Deployment preflight</h1>
  <p>SARCNA 2027 Convention — checked <?= date('j M Y, H:i') ?></p>
</div></header>

<div class="wrap">
  <div class="verdict <?= $verdict ? 'good' : 'bad' ?>">
    <h2><?= $verdict ? 'Ready.' : $counts['fail'] . ' problem' . ($counts['fail'] === 1 ? '' : 's') . ' to fix first.' ?></h2>
    <p>
      <?= $counts['pass'] ?> passed<?= $counts['fail'] ? ', ' . $counts['fail'] . ' failed' : '' ?><?= $counts['warn'] ? ', ' . $counts['warn'] . ' could not be tested' : '' ?>.
      <?= $verdict
            ? 'Open <code>/install</code> next, then delete this file using the button below.'
            : 'Each failure below says exactly what to do. Fix them, then reload this page.' ?>
    </p>
  </div>

  <?php foreach ($results as $group => $rows): ?>
    <h3><?= htmlspecialchars($group) ?></h3>
    <table>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td class="state <?= $r['state'] ?>"><?= strtoupper($r['state']) ?></td>
          <td>
            <?= htmlspecialchars($r['label']) ?>
            <?php if ($r['evidence'] !== ''): ?><span class="evi"><?= htmlspecialchars($r['evidence']) ?></span><?php endif; ?>
            <?php if ($r['fix'] !== ''): ?><span class="fix"><strong>Fix:</strong> <?= htmlspecialchars($r['fix']) ?></span><?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endforeach; ?>

  <footer>
    <h3>Delete this file when you are finished</h3>
    <p>
      It reports server paths, so it must not stay on a live site. This button removes it.
      If it cannot, delete <code>public_html/<?= htmlspecialchars($selfName) ?></code> by hand in cPanel File Manager.
    </p>
    <form method="post">
      <input type="hidden" name="action" value="delete-me">
      <input type="hidden" name="confirm" value="yes">
      <button type="submit">Delete preflight.php now</button>
    </form>
  </footer>
</div>
</html>

<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This script is for the command line only.\n");
}


/**
 * Build the cPanel upload package.
 *
 *   php tools/package.php
 *
 * Produces dist/sarcna-2027-cpanel-<date>.zip containing everything that
 * belongs on the server and nothing that does not: no .git, no .env, no
 * uploaded files, no logs.
 *
 * Upload the ZIP to the account's home directory in cPanel File Manager and
 * extract it there, so that public_html becomes the document root.
 */

$root = dirname(__DIR__);
$dist = $root . '/dist';

if (!class_exists('ZipArchive')) {
    fwrite(STDERR, "The zip extension is not available. Compress the folder manually instead,\n"
        . "excluding .git, .env, dist, storage contents and public_html/uploads contents.\n");
    exit(1);
}

if (!is_dir($dist) && !mkdir($dist, 0755, true) && !is_dir($dist)) {
    fwrite(STDERR, "Could not create the dist folder.\n");
    exit(1);
}

$name = sprintf('sarcna-2027-cpanel-%s.zip', date('Y-m-d-Hi'));
$path = $dist . '/' . $name;

/** Paths that must never ship. */
$excludedDirectories = ['.git', '.github', 'dist', 'node_modules', '.idea', '.vscode'];
$excludedFiles       = ['.env', '.DS_Store', 'Thumbs.db', 'installed.lock'];

/** Folders that ship empty, with only their .gitkeep. */
$emptyDirectories = [
    'storage/logs', 'storage/cache', 'storage/backups', 'storage/email-queue',
    'public_html/uploads',
];

$zip = new ZipArchive();

if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    fwrite(STDERR, "Could not create {$path}.\n");
    exit(1);
}

$iterator = new RecursiveIteratorIterator(
    new RecursiveCallbackFilterIterator(
        new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
        static function (SplFileInfo $file) use ($root, $excludedDirectories): bool {
            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
            $top      = explode('/', $relative)[0];

            return !in_array($top, $excludedDirectories, true);
        }
    ),
    RecursiveIteratorIterator::SELF_FIRST
);

$count = 0;
$bytes = 0;

foreach ($iterator as $file) {
    /** @var SplFileInfo $file */
    $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));

    if (in_array(basename($relative), $excludedFiles, true)) {
        continue;
    }

    // Ship the runtime folders empty.
    foreach ($emptyDirectories as $directory) {
        if (str_starts_with($relative, $directory . '/') && basename($relative) !== '.gitkeep' && basename($relative) !== '.htaccess') {
            continue 2;
        }
    }

    if ($file->isDir()) {
        $zip->addEmptyDir($relative);
        continue;
    }

    $zip->addFile($file->getPathname(), $relative);
    $count++;
    $bytes += $file->getSize();
}

// A short readme at the top of the archive, for whoever opens it in cPanel.
$zip->addFromString('UPLOAD-ME-FIRST.txt', <<<TEXT
SARCNA 2027 Convention — cPanel upload package
Built {$name}

1. In cPanel, create a MySQL database and a user, and give the user ALL
   PRIVILEGES on that database. Write down the full names and the password.

2. In cPanel File Manager, go to the account's HOME directory — the folder that
   CONTAINS public_html, not public_html itself.

3. Upload this ZIP there and extract it. You should end up with:

       /home/youraccount/
           app/
           database/
           docs/
           storage/
           tools/
           public_html/     <- the domain's document root

4. Set folder permissions to 755 and file permissions to 644.
   Make sure storage/ and public_html/uploads/ are writable (755).

5. Visit https://yourdomain/install and follow the form.

Full instructions: docs/cpanel-deployment-guide.md
TEXT);

$zip->close();

printf(
    "Package built\n  %s\n  %d files, %.1f MB uncompressed, %.1f MB zipped\n\nUpload it to the account home directory and extract it there.\n",
    $path,
    $count,
    $bytes / 1048576,
    filesize($path) / 1048576
);

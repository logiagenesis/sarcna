<?php
declare(strict_types=1);

/**
 * Venue photo importer.
 *
 *   php tools/import-venue-images.php            # download + process everything
 *   php tools/import-venue-images.php --list     # show the manifest and exit
 *   php tools/import-venue-images.php --dry-run  # check reachability, write nothing
 *
 * Downloads Boschendal's own photographs of the Retreat cottages, the
 * conference venues and the estate from boschendal.com, resizes them to the
 * site's sizes, writes JPEG + WebP pairs into public_html/assets/img/venue/real/
 * and registers the gallery shots in the gallery_images table as real venue
 * photography (is_mock = 0), each with a source note naming where it came from.
 *
 * Run this from a machine with ordinary internet access — the development
 * sandbox this site was built in cannot reach image hosts, which is exactly
 * why the importer exists as a separate, re-runnable step.
 *
 * PERMISSION: these are Boschendal's photographs, listed here from the venue's
 * own public website. Confirm with the venue that the convention may use them
 * before launch — a venue hosting the event normally welcomes this, but the
 * committee should have it in writing. The source of every image is kept in
 * docs/venue-source-log.md.
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;

if (PHP_SAPI !== 'cli') {
    exit("This script is for the command line only.\n");
}

/**
 * The curated manifest. Full-resolution originals wherever the site offers
 * them ("-scaled" is WordPress's 2560px rendition). Deliberately excludes the
 * estate's wine-tasting and bar photography — this is a recovery convention.
 */
$manifest = [
    /* ------------------------------------------ the Retreat cottages (rooms) */
    ['url' => 'https://boschendal.com/wp-content/uploads/2022/09/Boschendal_Aug312022_0156copy-scaled.jpg',
     'slug' => 'retreat-cottage-exterior', 'caption' => 'A Retreat cottage under the oaks', 'gallery' => true],
    ['url' => 'https://boschendal.com/wp-content/uploads/2022/09/Boschendal_Aug312022_0588copy-scaled.jpg',
     'slug' => 'retreat-cottage-bedroom-1', 'caption' => 'A Retreat cottage bedroom', 'room_type' => 'retreat-cottage-twin-room'],
    ['url' => 'https://boschendal.com/wp-content/uploads/2022/09/Boschendal_Aug312022_1199copy-scaled.jpg',
     'slug' => 'retreat-cottage-bedroom-2', 'caption' => 'Retreat cottage bedroom, second aspect', 'room_type' => 'retreat-cottage-twin-room'],
    ['url' => 'https://boschendal.com/wp-content/uploads/2022/09/Boschendal_Aug312022_1283copy-1-scaled.jpg',
     'slug' => 'retreat-cottage-living', 'caption' => 'The cottage living area', 'room_type' => 'retreat-cottage-twin-room'],
    ['url' => 'https://boschendal.com/wp-content/uploads/2022/09/Boschendal_Aug312022_4422copy-1-scaled.jpg',
     'slug' => 'retreat-cottage-interior-1', 'caption' => 'Inside a Retreat cottage', 'room_type' => 'retreat-cottage-accessible-room'],
    ['url' => 'https://boschendal.com/wp-content/uploads/2022/09/Boschendal_Aug312022_4424copy-2-scaled.jpg',
     'slug' => 'retreat-cottage-interior-2', 'caption' => 'Cottage interior detail', 'gallery' => true],
    ['url' => 'https://boschendal.com/wp-content/uploads/2022/09/Boschendal_Aug312022_4441copy-2.jpg',
     'slug' => 'retreat-cottage-bathroom', 'caption' => 'An en-suite cottage bathroom', 'room_type' => 'retreat-cottage-accessible-room'],
    ['url' => 'https://boschendal.com/wp-content/uploads/2022/09/Boschendal_Aug312022_4454copy-scaled.jpg',
     'slug' => 'retreat-cottage-detail', 'caption' => 'Cottage detail', 'gallery' => true],
    ['url' => 'https://boschendal.com/wp-content/uploads/2022/09/Boschendal_Sep022022_2141copy-1-scaled.jpg',
     'slug' => 'retreat-grounds-1', 'caption' => 'The Retreat gardens', 'gallery' => true],
    ['url' => 'https://boschendal.com/wp-content/uploads/2022/09/Boschendal_Sep022022_2149copy-scaled.jpg',
     'slug' => 'retreat-grounds-2', 'caption' => 'Fynbos paths between the cottages', 'gallery' => true],
    ['url' => 'https://boschendal.com/wp-content/uploads/2022/09/Page-slider-images-1088×784-retreat-cottages-3.jpg',
     'slug' => 'retreat-cottages-row', 'caption' => 'The Retreat cottages', 'room_type' => 'retreat-cottage-twin-room'],
    ['url' => 'https://boschendal.com/wp-content/uploads/2022/09/Page-slider-images-1088×784-retreat-cottages-4.jpg',
     'slug' => 'retreat-cottages-pool', 'caption' => 'The natural pool at the Retreat', 'gallery' => true],

    /* ------------------------------------------------- conference venues */
    ['url' => 'https://boschendal.com/wp-content/uploads/2022/11/venue1.jpg',
     'slug' => 'conference-venue-1', 'caption' => 'One of the estate\'s conference venues', 'gallery' => true],
    ['url' => 'https://boschendal.com/wp-content/uploads/2022/11/venue2.jpg',
     'slug' => 'conference-venue-2', 'caption' => 'Conference venue, set for an event', 'gallery' => true],
    ['url' => 'https://boschendal.com/wp-content/uploads/2022/11/venue3.jpg',
     'slug' => 'conference-venue-3', 'caption' => 'Function space on the werf', 'gallery' => true],
    ['url' => 'https://boschendal.com/wp-content/uploads/2024/10/Conferences-Functions.jpg',
     'slug' => 'conference-fireside', 'caption' => 'A function room ready for guests', 'gallery' => true],

    /* --------------------------------------------------------- the estate */
    ['url' => 'https://boschendal.com/wp-content/uploads/2022/10/Farm-Tour-Overview-1-scaled.jpg',
     'slug' => 'estate-farm-tour', 'caption' => 'The farm against the Drakenstein mountains', 'gallery' => true],
    ['url' => 'https://boschendal.com/wp-content/uploads/2022/11/BoschendalClaireGunn_012622_3273copy-scaled.jpg',
     'slug' => 'estate-werf', 'caption' => 'The historic werf', 'gallery' => true],
    ['url' => 'https://boschendal.com/wp-content/uploads/2022/12/Community-Overview-scaled.jpg',
     'slug' => 'estate-community', 'caption' => 'Life on the estate', 'gallery' => true],
    ['url' => 'https://boschendal.com/wp-content/uploads/2022/10/Tree-house-_-kids-1.jpg',
     'slug' => 'estate-tree-house', 'caption' => 'The Tree House', 'gallery' => true],
    ['url' => 'https://boschendal.com/wp-content/uploads/2024/10/Trails.jpg',
     'slug' => 'estate-trails', 'caption' => 'Walking trails through the farm', 'gallery' => true],
    ['url' => 'https://boschendal.com/wp-content/uploads/2024/10/Garden-Tours.jpg',
     'slug' => 'estate-gardens', 'caption' => 'The food gardens', 'gallery' => true],
    ['url' => 'https://boschendal.com/wp-content/uploads/2024/10/Picnics.jpg',
     'slug' => 'estate-picnics', 'caption' => 'Picnics on the lawns', 'gallery' => true],
    ['url' => 'https://boschendal.com/wp-content/uploads/2024/10/Horse-Riding.jpg',
     'slug' => 'estate-horse-riding', 'caption' => 'Horse rides into the foothills', 'gallery' => true],
    ['url' => 'https://boschendal.com/wp-content/uploads/2023/06/farm.jpg',
     'slug' => 'estate-farm', 'caption' => 'The farm, looking toward Simonsberg', 'gallery' => true],
];

$publicDir = dirname(__DIR__) . '/public_html';
$targetDir = $publicDir . '/assets/img/venue/real';

/* ---------------------------------------------------------------- options */

if (in_array('--list', $argv, true)) {
    printf("%d images in the manifest:\n\n", count($manifest));

    foreach ($manifest as $item) {
        printf("  %-28s %s\n", $item['slug'], $item['url']);
    }

    exit(0);
}

$dryRun = in_array('--dry-run', $argv, true);

if (!is_dir($targetDir) && !$dryRun && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
    exit("Could not create {$targetDir}\n");
}

/** Encode only the path portion — some filenames contain a Unicode ×. */
function encodedUrl(string $url): string
{
    $parts = parse_url($url);
    $path  = implode('/', array_map('rawurlencode', explode('/', ltrim((string) $parts['path'], '/'))));

    return $parts['scheme'] . '://' . $parts['host'] . '/' . $path;
}

function download(string $url): ?string
{
    $ch = curl_init(encodedUrl($url));

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_USERAGENT      => 'SARCNA-2027-site-importer/1.0 (venue media; contact: convention committee)',
    ]);

    $body   = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $error  = curl_error($ch);
    curl_close($ch);

    if (!is_string($body) || $status !== 200 || strlen($body) < 20000) {
        printf("    failed (%s%s, %d bytes)\n", $status ?: 'no response', $error !== '' ? ', ' . $error : '', is_string($body) ? strlen($body) : 0);

        return null;
    }

    return $body;
}

/** Resize to fit within the width, write JPEG + WebP, return [w, h]. */
function process(string $raw, string $basePath, int $maxWidth = 1800): ?array
{
    $source = @imagecreatefromstring($raw);

    if ($source === false) {
        echo "    not a decodable image\n";

        return null;
    }

    $width  = imagesx($source);
    $height = imagesy($source);

    if ($width > $maxWidth) {
        $newWidth  = $maxWidth;
        $newHeight = (int) round($height * ($maxWidth / $width));
        $resized   = imagecreatetruecolor($newWidth, $newHeight);

        imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($source);

        $source = $resized;
        $width  = $newWidth;
        $height = $newHeight;
    }

    imageinterlace($source, true);
    imagejpeg($source, $basePath . '.jpg', 82);
    imagewebp($source, $basePath . '.webp', 80);
    imagedestroy($source);

    return [$width, $height];
}

/* ------------------------------------------------------------------- run */

printf("\nImporting %d venue photographs%s…\n\n", count($manifest), $dryRun ? ' (dry run)' : '');

$done   = 0;
$failed = 0;
$sort   = (int) (Database::isConnected()
    ? Database::scalar('SELECT COALESCE(MAX(sort_order), 0) FROM gallery_images')
    : 0);

foreach ($manifest as $item) {
    printf("  %s\n", $item['slug']);

    if ($dryRun) {
        $headers = @get_headers(encodedUrl($item['url']));
        printf("    %s\n", $headers === false ? 'UNREACHABLE' : $headers[0]);
        $headers === false ? $failed++ : $done++;

        continue;
    }

    $raw = download($item['url']);

    if ($raw === null) {
        $failed++;

        continue;
    }

    $base = $targetDir . '/' . $item['slug'];
    $size = process($raw, $base);

    if ($size === null) {
        $failed++;

        continue;
    }

    printf("    saved %dx%d (%s + webp)\n", $size[0], $size[1], basename($base) . '.jpg');

    $relative = 'assets/img/venue/real/' . $item['slug'] . '.jpg';

    if (Database::isConnected()) {
        // Attach to a room type where the manifest says so, otherwise gallery.
        if (isset($item['room_type'])) {
            $roomTypeId = Database::scalar('SELECT id FROM room_types WHERE slug = ?', [$item['room_type']]);

            if ($roomTypeId !== null) {
                $exists = Database::scalar(
                    'SELECT id FROM room_type_images WHERE room_type_id = ? AND file_path = ?',
                    [(int) $roomTypeId, $relative]
                );

                if ($exists === null) {
                    Database::insert('room_type_images', [
                        'room_type_id' => (int) $roomTypeId,
                        'file_path'    => $relative,
                        'alt_text'     => $item['caption'] . ', Boschendal',
                        'source_note'  => 'Photograph: Boschendal — ' . $item['url'],
                        'sort_order'   => 100,
                    ]);
                }
            }
        }

        if ($item['gallery'] ?? false) {
            $exists = Database::scalar('SELECT id FROM gallery_images WHERE file_path = ?', [$relative]);

            if ($exists === null) {
                Database::insert('gallery_images', [
                    'title'      => $item['caption'],
                    'alt_text'   => $item['caption'] . ' — Boschendal, Cape Winelands',
                    'file_path'  => $relative,
                    'category'   => 'venue',
                    'credit'     => 'Photograph: Boschendal',
                    'is_active'  => 1,
                    'is_mock'    => 0,
                    'sort_order' => ++$sort,
                ]);
            }
        }
    }

    $done++;
}

printf("\nDone: %d imported, %d failed.\n", $done, $failed);

if ($failed > 0) {
    echo "Failures are safe to re-run — the importer skips nothing and overwrites its own files only.\n";
}

if (!$dryRun && $done > 0) {
    echo "Review the results in the admin gallery, and confirm usage with the venue before launch.\n";
}

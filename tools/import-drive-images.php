<?php
declare(strict_types=1);

/**
 * Import the venue photographs the committee supplied.
 *
 *   php tools/import-drive-images.php <directory> [--dry-run]
 *
 * The committee delivered a folder of Boschendal photography. This puts the
 * approved ones into their slots through the very same code the Photographs
 * screen uses (App\Services\PhotoService), so a picture imported here is
 * processed identically to one a volunteer uploads in the browser: refused if
 * it is too small, centre-cropped, given a WebP twin, and stripped of EXIF.
 *
 * ---------------------------------------------------------------------------
 * WHY THE MANIFEST IS SHORTER THAN THE FOLDER
 * ---------------------------------------------------------------------------
 * Not every supplied photograph belongs on this site, and the reasons are not
 * technical. Each excluded file is listed in EXCLUDED below with its reason,
 * so nobody has to guess later why a picture was passed over — and so the
 * committee can overrule any of it.
 *
 * The two judgement calls that matter:
 *
 *   1. ALCOHOL. Boschendal is a wine estate and much of its photography shows
 *      wine being poured, tasted and sold. This is the website of a Narcotics
 *      Anonymous convention. Wine imagery has no place on it.
 *
 *   2. IDENTIFIABLE CHILDREN. Several photographs are close portraits of
 *      children. Publishing them on a recovery fellowship's site, where
 *      anonymity is a founding principle, is not something to do casually.
 *
 * Both were caught by looking at the photographs rather than reading their
 * file names.
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;
use App\Services\PhotoService;

if (PHP_SAPI !== 'cli') {
    exit("Command line only.\n");
}

$dir    = $argv[1] ?? '';
$dryRun = in_array('--dry-run', $argv, true);

if ($dir === '' || !is_dir($dir)) {
    exit("Usage: php tools/import-drive-images.php <directory> [--dry-run]\n");
}

$credit = 'Boschendal Estate, supplied by the committee (permission to be confirmed in writing)';

/**
 * file => [slot key resolver, alt text]
 *
 * Slot keys are resolved by name, not by id, so this still works after a
 * reinstall renumbers the tables.
 */
$manifest = [
    // The home page hero: the historic Cape Dutch manor at golden hour.
    '034_BoschendalClaireGunn_012622_3273copy-scaled.jpg' => [
        ['banner', 'home_hero'],
        'boschendal-manor-house',
        'The historic Boschendal manor house in late afternoon light, framed by oak trees',
    ],

    // Room types.
    '003_Page-gallery-image-mountain-villa-10.jpg' => [
        ['room', 'retreat-cottage-twin-room'],
        'retreat-cottages-mountain',
        'A row of whitewashed retreat cottages with the Simonsberg mountains behind them',
    ],
    '006_Page-gallery-image-clarence-cottage-10.jpg' => [
        ['room', 'retreat-cottage-accessible-room'],
        'accessible-cottage-ramp',
        'An accessible cottage veranda reached by a gently ramped path with handrails',
    ],
    '008_Page-gallery-image-orchard-cottages-11.jpg' => [
        ['room', 'partner-guest-house-franschhoek'],
        'orchard-cottage-veranda',
        'A cottage veranda opening onto lavender beds and lawn, with the mountains beyond',
    ],

    // Extra photographs inside the room galleries.
    '009_Boschendal-The-Cow-Shed-07_03_23-008.jpg' => [
        ['room-gallery', 'retreat-cottage-twin-room'],
        'cottage-veranda-mountains',
        'A cottage veranda set with armchairs and a dining table, mountains rising behind',
    ],
    // The venue gallery.
    '030_Farm-Tour-Overview-1-scaled.jpg' => [
        ['gallery-new', ''],
        'walled-kitchen-garden',
        'The walled kitchen garden at sunrise, with raised beds and a stone water furrow',
    ],
    '010_Vineyard-Farmhouse-Slider-2.jpg' => [
        ['gallery-new', ''],
        'pool-and-simonsberg',
        'A swimming pool and parasols on the lawn beneath the Simonsberg',
    ],
    '011_Camp-Canoe-Featured.jpg' => [
        ['gallery-new', ''],
        'tented-suites-fynbos',
        'Canvas tented suites tucked into mountain fynbos above the estate',
    ],
];

/**
 * Supplied but deliberately not used, with the reason. Kept in the code so the
 * decision is reviewable rather than invisible.
 */
const EXCLUDED = [
    '013_eatWine-tasting-page-Werf-copy-scaled.jpg' => 'Alcohol — a wine tasting. This is an NA convention site.',
    '017_arum-1536x957.jpg'                          => 'Alcohol — wine glasses laid on every restaurant table.',
    '019_Wine-Tasting.jpg'                           => 'Alcohol — wine tasting.',
    '040_Buy-Our-Wine.jpg'                           => 'Alcohol — wine retail.',
    '016_Boschendal_Aug312022_0365-Edit-Menu.jpg'    => 'Restaurant/menu photography, not verified free of alcohol.',
    '020_Nightmarket_0162copy.jpg'                   => 'Night market, not verified free of alcohol.',
    '037_Nightmarket_0633copy-1.jpg'                 => 'Night market, not verified free of alcohol.',
    '012_4.4.jpg'                                    => 'Identifiable children.',
    '021_Tree-house-_-kids-1.jpg'                    => 'Identifiable children.',
    '035_Community-Overview-scaled.jpg'              => 'Identifiable children, in close portrait.',
    '029_venue1.jpg'                                 => 'Portrait crop, 1000x1250 — below the bar for the landscape slots.',
    // These two are lovely, and were in the manifest until the quality gate
    // refused them on a real run. They are letterbox crops (633px and 585px
    // tall) supplied for a full-width slider; there is no 3:2 slot they fit
    // without throwing away most of the frame. Left out rather than lowering
    // the bar for them.
    '004_Stay-Cottage-1685-1-1.jpeg'                 => 'Panoramic crop, 1920x633 — too short for any 3:2 slot.',
    '007_Stay-Werf-Garden-Suites-4-1.jpeg'           => 'Panoramic crop, 1772x585 — too short for any 3:2 slot.',
];

/** Resolve a manifest target to a live slot key. */
function resolveSlot(array $target): ?string
{
    [$kind, $ref] = $target;

    return match ($kind) {
        'banner' => ($id = Database::scalar('SELECT id FROM banners WHERE position = ? ORDER BY sort_order LIMIT 1', [$ref]))
            ? 'banner:' . $id : null,
        'room', 'room-gallery' => ($id = Database::scalar('SELECT id FROM room_types WHERE slug = ?', [$ref]))
            ? $kind . ':' . $id : null,
        'gallery-new' => 'gallery-new:1',
        default       => null,
    };
}

printf("\nImporting venue photographs from %s\n%s\n\n", $dir, str_repeat('=', 70));

$done = 0;
$skipped = 0;
$failed = 0;

foreach ($manifest as $file => [$target, $stableName, $alt]) {
    $path = rtrim($dir, '/') . '/' . $file;

    if (!is_file($path)) {
        printf("  \033[33mMISS\033[0m %-52s not in the folder\n", $file);
        $skipped++;
        continue;
    }

    $slotKey = resolveSlot($target);

    if ($slotKey === null) {
        printf("  \033[31mFAIL\033[0m %-52s no slot for %s/%s\n", $file, $target[0], $target[1]);
        $failed++;
        continue;
    }

    // Ask the live slot list for this slot's required size, so the importer
    // can never drift from what the browser upload enforces.
    $size = null;

    foreach (PhotoService::slots() as $slots) {
        foreach ($slots as $slot) {
            if ($slot['key'] === $slotKey) {
                $size = [$slot['width'], $slot['height']];
            }
        }
    }

    if ($size === null) {
        printf("  \033[31mFAIL\033[0m %-52s slot %s not offered\n", $file, $slotKey);
        $failed++;
        continue;
    }

    $info = getimagesize($path);

    if ($dryRun) {
        printf("  \033[36mDRY \033[0m %-52s %dx%d → %s (needs %dx%d)\n",
            $file, $info[0], $info[1], $slotKey, $size[0], $size[1]);
        continue;
    }

    $result = PhotoService::accept(
        ['error' => UPLOAD_ERR_OK, 'size' => filesize($path), 'tmp_name' => $path],
        $size[0],
        $size[1]
    );

    if ($result['ok'] !== true) {
        printf("  \033[31mFAIL\033[0m %-52s %s\n", $file, $result['message']);
        $failed++;
        continue;
    }

    // Give the file a stable, meaningful name. These photographs are project
    // assets committed to the repository, not one-off uploads, so a random
    // name would make every re-import look like a different picture.
    $stored = (string) $result['path'];
    $photos = dirname(__DIR__) . '/public_html/uploads';

    foreach (['jpg', 'webp'] as $ext) {
        $from = $photos . preg_replace('/\.jpg$/', '.' . $ext, $stored);
        $to   = $photos . '/photos/' . $stableName . '.' . $ext;

        if (is_file($from)) {
            @rename($from, $to);
        }
    }

    $stored = '/photos/' . $stableName . '.jpg';

    $assigned = PhotoService::assign($slotKey, $stored, $alt, $credit);

    if ($assigned['ok'] !== true) {
        printf("  \033[31mFAIL\033[0m %-52s %s\n", $file, $assigned['message']);
        $failed++;
        continue;
    }

    printf("  \033[32m OK \033[0m %-52s %dx%d → %s\n", $file, $info[0], $info[1], $slotKey);
    $done++;
}

/**
 * Put the real photographs in front of the illustrations.
 *
 * Every import also files a copy in the gallery for provenance. Once real
 * photographs exist there is no reason to keep showing drawings of the venue
 * beside them, so the imported ones are activated and sorted first and the
 * shipped illustrations are switched off. The illustration rows are only
 * deactivated, never deleted, so this is reversible from Admin → Gallery.
 */
if (!$dryRun && $done > 0) {
    $order = 1;

    foreach (Database::select(
        "SELECT id FROM gallery_images WHERE file_path LIKE '/photos/%' ORDER BY id"
    ) as $row) {
        Database::run(
            'UPDATE gallery_images SET is_active = 1, category = "venue", sort_order = ? WHERE id = ?',
            [$order++, (int) $row['id']]
        );
    }

    $retired = Database::run(
        "UPDATE gallery_images SET is_active = 0
          WHERE category IN ('venue','conference') AND file_path NOT LIKE '/photos/%'"
    );

    printf("\n  Promoted %d photographs to the front of the gallery and retired the illustrations.\n", $order - 1);
}

printf("\n%s\n%d imported, %d skipped, %d failed.\n", str_repeat('=', 70), $done, $skipped, $failed);

printf("\nDeliberately not imported (%d):\n", count(EXCLUDED));

foreach (EXCLUDED as $file => $reason) {
    printf("  · %-48s %s\n", $file, $reason);
}

$progress = PhotoService::progress();
printf("\nReal photographs in place: %d of %d slots.\n\n", $progress['real'], $progress['total']);

exit($failed === 0 ? 0 : 1);

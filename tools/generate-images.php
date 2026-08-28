<?php
declare(strict_types=1);

/**
 * Placeholder imagery generator (PHP GD).
 *
 *   php tools/generate-images.php
 *
 * Every image the mockup ships with is drawn here and written into
 * /public_html/assets/img as an optimised JPEG plus a WebP twin. Nothing is
 * hot-linked, nothing is downloaded at runtime, and every file is logged in
 * docs/image-source-log.md.
 *
 * These are ORIGINAL STYLISED ILLUSTRATIONS of a Cape Winelands landscape,
 * not photographs of Boschendal. Replace them with licensed venue photography
 * before the site goes live — see docs/image-source-log.md.
 */

$root   = dirname(__DIR__);
$out    = $root . '/public_html/assets/img';
$brand  = $root . '/public_html/assets/brand';
$fonts  = $root . '/public_html/assets/fonts';

$serif      = $fonts . '/Lora-Bold.ttf';
$serifLight = $fonts . '/Lora-Regular.ttf';
$sans       = $fonts . '/WorkSans-Regular.ttf';
$sansBold   = $fonts . '/WorkSans-Bold.ttf';

const PALETTE = [
    'vineyard'   => [0x17, 0x3D, 0x2F],
    'forest'     => [0x0E, 0x24, 0x1C],
    'plum'       => [0x6E, 0x2B, 0x55],
    'gold'       => [0xD9, 0xA4, 0x41],
    'clay'       => [0xB9, 0x6A, 0x45],
    'sage'       => [0x8D, 0xA9, 0x8F],
    'cream'      => [0xFF, 0xF6, 0xE7],
    'mist'       => [0xF3, 0xE8, 0xD3],
    'charcoal'   => [0x11, 0x18, 0x15],
    'white'      => [0xFF, 0xFF, 0xFF],
];

/* ------------------------------------------------------------ primitives */

function colour($image, string $name, float $alpha = 0.0)
{
    [$r, $g, $b] = PALETTE[$name];

    return $alpha > 0
        ? imagecolorallocatealpha($image, $r, $g, $b, (int) round($alpha * 127))
        : imagecolorallocate($image, $r, $g, $b);
}

function rgb($image, array $rgb, float $alpha = 0.0)
{
    return $alpha > 0
        ? imagecolorallocatealpha($image, $rgb[0], $rgb[1], $rgb[2], (int) round($alpha * 127))
        : imagecolorallocate($image, $rgb[0], $rgb[1], $rgb[2]);
}

function mix(array $a, array $b, float $t): array
{
    return [
        (int) round($a[0] + ($b[0] - $a[0]) * $t),
        (int) round($a[1] + ($b[1] - $a[1]) * $t),
        (int) round($a[2] + ($b[2] - $a[2]) * $t),
    ];
}

/** Vertical gradient fill. */
function gradient($image, int $x, int $y, int $w, int $h, array $from, array $to): void
{
    for ($i = 0; $i < $h; $i++) {
        $colour = rgb($image, mix($from, $to, $h <= 1 ? 0 : $i / ($h - 1)));
        imagefilledrectangle($image, $x, $y + $i, $x + $w, $y + $i, $colour);
    }
}

/** A soft ridge line built from layered sine waves — deterministic per seed. */
function ridge(int $width, int $baseline, float $amplitude, int $seed, float $roughness = 1.0): array
{
    mt_srand($seed);

    $phase1 = mt_rand(0, 628) / 100;
    $phase2 = mt_rand(0, 628) / 100;
    $phase3 = mt_rand(0, 628) / 100;
    $points = [];

    for ($x = 0; $x <= $width; $x++) {
        $t = $x / max(1, $width);
        $y = $baseline
            - $amplitude * (0.62 * sin(($t * 3.1) + $phase1)
                + 0.26 * sin(($t * 7.3 * $roughness) + $phase2)
                + 0.12 * sin(($t * 13.7 * $roughness) + $phase3));
        $points[$x] = (int) round($y);
    }

    return $points;
}

function fillRidge($image, array $points, int $bottom, $colour): void
{
    foreach ($points as $x => $y) {
        imageline($image, $x, $y, $x, $bottom, $colour);
    }
}

/** Vineyard rows receding to a vanishing point. */
function vineyard($image, int $width, int $height, int $horizon, array $colour, int $rows = 16): void
{
    $vanishX = (int) ($width * 0.5);

    for ($i = 0; $i <= $rows; $i++) {
        $t      = $i / $rows;
        $startX = (int) (-$width * 0.6 + ($width * 2.2) * $t);
        $shade  = mix($colour, PALETTE['forest'], 0.15 + 0.35 * abs(0.5 - $t) * 2);
        $line   = rgb($image, $shade, 0.35);

        imagesetthickness($image, max(1, (int) ($width / 420)));
        imageline($image, $startX, $height, $vanishX, $horizon, $line);
    }

    imagesetthickness($image, 1);
}

/** Cottage silhouettes along the ridge. */
function cottages($image, int $width, int $baseY, int $count, $colour, int $seed): void
{
    mt_srand($seed + 77);

    for ($i = 0; $i < $count; $i++) {
        $w = (int) ($width * (mt_rand(45, 75) / 1000));
        $h = (int) ($w * 0.62);
        $x = (int) ($width * (mt_rand(60, 900) / 1000));
        $y = $baseY + mt_rand(0, (int) ($h * 0.35));

        imagefilledrectangle($image, $x, $y - $h, $x + $w, $y, $colour);
        imagefilledpolygon($image, [
            $x - (int) ($w * 0.12), $y - $h,
            $x + (int) ($w / 2), $y - (int) ($h * 1.45),
            $x + $w + (int) ($w * 0.12), $y - $h,
        ], $colour);
    }
}

/** Edge darkening: the outermost ring is the darkest and falls off inward. */
/** A row of cypress-like trees along the horizon. */
function treeline($image, int $width, int $baseY, int $maxHeight, $colour, int $seed): void
{
    mt_srand($seed + 401);

    $spacing = max(24, (int) ($width / mt_rand(9, 16)));

    for ($x = -$spacing; $x <= $width + $spacing; $x += $spacing) {
        $h  = (int) ($maxHeight * (mt_rand(55, 100) / 100));
        $w  = max(3, (int) ($h * 0.24));
        $px = $x + mt_rand(-(int) ($spacing / 3), (int) ($spacing / 3));

        imagefilledpolygon($image, [
            $px, $baseY,
            $px + (int) ($w / 2), $baseY - $h,
            $px + $w, $baseY,
        ], $colour);
        imagefilledrectangle($image, $px + (int) ($w * 0.42), $baseY - 2, $px + (int) ($w * 0.58), $baseY + (int) ($h * 0.08), $colour);
    }
}

function vignette($image, int $width, int $height, float $strength = 0.3): void
{
    $depth = (int) (min($width, $height) / 5);

    for ($i = 0; $i < $depth; $i++) {
        $opacity = $strength * (1 - $i / $depth) ** 2.4;

        if ($opacity <= 0.004) {
            break;
        }

        $colour = imagecolorallocatealpha($image, 17, 24, 21, (int) round(127 * (1 - $opacity)));
        imagerectangle($image, $i, $i, $width - 1 - $i, $height - 1 - $i, $colour);
    }
}

function grain($image, int $width, int $height, int $seed, int $density = 900): void
{
    mt_srand($seed + 13);
    $light = imagecolorallocatealpha($image, 255, 246, 231, 110);
    $dark  = imagecolorallocatealpha($image, 17, 24, 21, 110);

    for ($i = 0; $i < $density; $i++) {
        imagesetpixel($image, mt_rand(0, $width - 1), mt_rand(0, $height - 1), $i % 2 === 0 ? $light : $dark);
    }
}

function placeholderBadge($image, int $width, int $height, string $font, string $text = 'ILLUSTRATION · PLACEHOLDER'): void
{
    if (!is_file($font)) {
        return;
    }

    $size = max(9, (int) ($width / 110));
    $box  = imagettfbbox($size, 0, $font, $text);
    $textWidth  = abs($box[4] - $box[0]);
    $textHeight = abs($box[5] - $box[1]);

    $padX = (int) ($size * 1.1);
    $padY = (int) ($size * 0.75);
    $x    = $width - $textWidth - ($padX * 2) - (int) ($width * 0.025);
    $y    = $height - (int) ($height * 0.035) - $textHeight - ($padY * 2);

    $plate = imagecolorallocatealpha($image, 17, 24, 21, 68);
    imagefilledrectangle($image, $x, $y, $x + $textWidth + ($padX * 2), $y + $textHeight + ($padY * 2), $plate);

    $ink = imagecolorallocatealpha($image, 255, 246, 231, 25);
    imagettftext($image, $size, 0, $x + $padX, $y + $padY + $textHeight, $ink, $font, $text);
}

/* ------------------------------------------------------------- the scene */

/**
 * @param string $mood dawn | day | dusk | mist
 */
function landscape(int $width, int $height, int $seed, string $mood = 'dawn', bool $withVineyard = true, bool $withCottages = false)
{
    $image = imagecreatetruecolor($width, $height);
    imagealphablending($image, true);
    imagesavealpha($image, false);

    $skies = [
        'dawn' => [[0xF6, 0xC9, 0x7A], [0xFF, 0xF6, 0xE7]],
        'day'  => [[0x9E, 0xC4, 0xD8], [0xFF, 0xF6, 0xE7]],
        'dusk' => [[0x6E, 0x2B, 0x55], [0xE9, 0x9E, 0x63]],
        'mist' => [[0xD9, 0xDF, 0xD4], [0xFF, 0xF6, 0xE7]],
    ];

    [$skyTop, $skyBottom] = $skies[$mood] ?? $skies['dawn'];

    $horizon = (int) ($height * 0.62);
    gradient($image, 0, 0, $width, $horizon + 2, $skyTop, $skyBottom);

    // Sun
    mt_srand($seed);
    $sunX = (int) ($width * (mt_rand(300, 700) / 1000));
    $sunY = (int) ($horizon - $height * (mt_rand(90, 220) / 1000));
    $sunR = (int) ($height * ($mood === 'dusk' ? 0.13 : 0.1));

    $glowSteps = 34;

    for ($glow = $glowSteps; $glow >= 1; $glow--) {
        $radius = (int) ($sunR * (1 + ($glow / $glowSteps) * 3.4));
        $colour = imagecolorallocatealpha($image, 0xF2, 0xC2, 0x63, 124);
        imagefilledellipse($image, $sunX, $sunY, $radius * 2, $radius * 2, $colour);
    }

    imagefilledellipse($image, $sunX, $sunY, $sunR * 2, $sunR * 2, rgb($image, PALETTE['gold'], 0.05));

    // Three mountain layers, back to front
    $layers = [
        ['baseline' => $horizon - (int) ($height * 0.10), 'amp' => $height * 0.13, 'tint' => 0.68, 'seed' => $seed],
        ['baseline' => $horizon - (int) ($height * 0.04), 'amp' => $height * 0.10, 'tint' => 0.40, 'seed' => $seed + 31],
        ['baseline' => $horizon + (int) ($height * 0.02), 'amp' => $height * 0.06, 'tint' => 0.12, 'seed' => $seed + 67],
    ];

    foreach ($layers as $index => $layer) {
        $tone   = mix(PALETTE['vineyard'], $mood === 'mist' ? PALETTE['mist'] : PALETTE['cream'], $layer['tint']);
        $colour = rgb($image, $tone);
        $points = ridge($width, (int) $layer['baseline'], $layer['amp'], $layer['seed'], 1 + $index * 0.4);
        fillRidge($image, $points, $height, $colour);

    }

    // Horizon haze, so the ridges read as distance rather than flat shapes
    $hazeBand = (int) ($height * 0.08);
    for ($i = 0; $i < $hazeBand; $i++) {
        $opacity = 0.30 * (1 - $i / $hazeBand);
        $colour  = imagecolorallocatealpha($image, $skyBottom[0], $skyBottom[1], $skyBottom[2], (int) round(127 * (1 - $opacity)));
        imageline($image, 0, $horizon - $hazeBand + $i, $width, $horizon - $hazeBand + $i, $colour);
    }

    // Foreground field
    gradient($image, 0, $horizon + (int) ($height * 0.02), $width, $height - $horizon, PALETTE['sage'], PALETTE['forest']);

    if ($withVineyard) {
        vineyard($image, $width, $height, $horizon + (int) ($height * 0.02), PALETTE['sage'], 18);
    }

    if ($withCottages) {
        cottages($image, $width, $horizon + (int) ($height * 0.03), 4, rgb($image, PALETTE['forest']), $seed);
    }

    treeline($image, $width, $horizon + (int) ($height * 0.015), (int) ($height * 0.11), rgb($image, mix(PALETTE['forest'], PALETTE['vineyard'], 0.4)), $seed);

    grain($image, $width, $height, $seed);
    vignette($image, $width, $height, 0.3);

    return $image;
}

/* --------------------------------------------------------------- output */

function save($image, string $path, int $quality = 82): void
{
    $directory = dirname($path);

    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }

    imagejpeg($image, $path . '.jpg', $quality);
    imagewebp($image, $path . '.webp', $quality);

    echo str_pad(basename($path), 42), ' ', str_pad((string) round(filesize($path . '.jpg') / 1024), 5, ' ', STR_PAD_LEFT), " KB jpg  ",
         str_pad((string) round(filesize($path . '.webp') / 1024), 5, ' ', STR_PAD_LEFT), " KB webp\n";
}

/* ------------------------------------------------------------ the catalogue */

$catalogue = [
    // path                              w     h    seed  mood     vineyard cottages badge
    ['backgrounds/hero-winelands',      1920, 1080, 1017, 'dawn',  true,  true,  false],
    ['backgrounds/hero-mobile',          900, 1300, 1017, 'dawn',  true,  true,  false],
    ['backgrounds/cta-sunset',          1920,  760, 2044, 'dusk',  true,  false, false],
    ['backgrounds/section-mist',        1600,  600, 3081, 'mist',  false, false, false],
    ['backgrounds/placeholder',          800,  600, 4120, 'day',   true,  false, true],

    // The Retreat sits in a secluded corner of the 1 800 ha farm, between the
    // Simonsberg and the Groot Drakenstein.
    ['venue/boschendal-overview',       1600, 1000, 5150, 'day',   true,  true,  true],
    ['venue/retreat-cottages',          1200,  800, 5211, 'dawn',  false, true,  true],
    ['venue/cottage-interior',          1200,  800, 5266, 'dusk',  false, true,  true],
    ['venue/auditorium',                1200,  800, 5322, 'day',   false, true,  true],
    ['venue/screening-room',            1200,  800, 5377, 'mist',  false, true,  true],
    ['venue/dining-lounge',             1200,  800, 5544, 'dusk',  false, true,  true],
    ['venue/boma-firepit',              1200,  800, 5766, 'dusk',  false, true,  true],
    ['venue/natural-pool',              1200,  800, 5820, 'day',   false, false, true],
    ['venue/fynbos-gardens',            1200,  800, 5433, 'mist',  true,  false, true],
    ['venue/walking-trails',            1200,  800, 5655, 'dawn',  false, false, true],
    ['venue/horse-trails',              1200,  800, 5690, 'day',   true,  false, true],
    ['venue/arrival-drive',             1200,  800, 5877, 'day',   true,  false, true],

    ['rooms/retreat-twin-room',         1200,  800, 7100, 'dawn',  false, true,  true],
    ['rooms/retreat-accessible-room',   1200,  800, 7433, 'mist',  false, true,  true],
    ['rooms/partner-guest-house',       1200,  800, 7544, 'day',   true,  true,  true],

    ['transport/airport-shuttle',       1200,  800, 8100, 'day',   false, false, true],
    ['transport/winelands-road',        1200,  800, 8211, 'dawn',  true,  false, true],
];

echo "Generating landscape imagery\n----------------------------\n";

foreach ($catalogue as [$path, $w, $h, $seed, $mood, $withVineyard, $withCottages, $badge]) {
    $image = landscape($w, $h, $seed, $mood, $withVineyard, $withCottages);

    if ($badge) {
        placeholderBadge($image, $w, $h, $sansBold);
    }

    save($image, $out . '/' . $path);
    imagedestroy($image);
}

/* ------------------------------------------------------- merchandise mockups */

/** A flat-lay product mockup on a tinted plinth. */
function merchMockup(int $size, string $kind, array $garment, int $seed, string $font, string $label)
{
    $image = imagecreatetruecolor($size, $size);
    gradient($image, 0, 0, $size, $size, PALETTE['mist'], mix(PALETTE['cream'], PALETTE['sage'], 0.25));

    $cx = (int) ($size / 2);
    $cy = (int) ($size / 2);
    $u  = $size / 100;

    // Soft shadow
    $shadow = imagecolorallocatealpha($image, 17, 24, 21, 108);
    imagefilledellipse($image, $cx, (int) ($cy + 34 * $u), (int) (62 * $u), (int) (12 * $u), $shadow);

    $main  = rgb($image, $garment);
    $shade = rgb($image, mix($garment, PALETTE['charcoal'], 0.22));

    switch ($kind) {
        case 'tee':
        case 'hoodie':
            $bodyTop    = (int) ($cy - 26 * $u);
            $bodyBottom = (int) ($cy + 30 * $u);
            imagefilledpolygon($image, [
                (int) ($cx - 18 * $u), $bodyTop,
                (int) ($cx - 30 * $u), (int) ($bodyTop + 6 * $u),
                (int) ($cx - 38 * $u), (int) ($bodyTop + 20 * $u),
                (int) ($cx - 27 * $u), (int) ($bodyTop + 27 * $u),
                (int) ($cx - 24 * $u), $bodyBottom,
                (int) ($cx + 24 * $u), $bodyBottom,
                (int) ($cx + 27 * $u), (int) ($bodyTop + 27 * $u),
                (int) ($cx + 38 * $u), (int) ($bodyTop + 20 * $u),
                (int) ($cx + 30 * $u), (int) ($bodyTop + 6 * $u),
                (int) ($cx + 18 * $u), $bodyTop,
            ], $main);
            imagefilledellipse($image, $cx, $bodyTop, (int) (26 * $u), (int) (11 * $u), $shade);

            if ($kind === 'hoodie') {
                imagefilledellipse($image, $cx, (int) ($bodyTop + 2 * $u), (int) (34 * $u), (int) (16 * $u), $shade);
                imagefilledrectangle($image, (int) ($cx - 16 * $u), (int) ($cy + 6 * $u), (int) ($cx + 16 * $u), (int) ($cy + 20 * $u), $shade);
            }
            break;

        case 'cap':
            imagefilledarc($image, $cx, (int) ($cy + 4 * $u), (int) (56 * $u), (int) (48 * $u), 180, 360, $main, IMG_ARC_PIE);
            imagefilledellipse($image, $cx, (int) ($cy + 8 * $u), (int) (74 * $u), (int) (20 * $u), $shade);
            break;

        case 'tote':
            imagefilledrectangle($image, (int) ($cx - 26 * $u), (int) ($cy - 18 * $u), (int) ($cx + 26 * $u), (int) ($cy + 28 * $u), $main);
            imagesetthickness($image, (int) max(2, 3 * $u));
            imagearc($image, (int) ($cx - 12 * $u), (int) ($cy - 18 * $u), (int) (18 * $u), (int) (26 * $u), 180, 360, $shade);
            imagearc($image, (int) ($cx + 12 * $u), (int) ($cy - 18 * $u), (int) (18 * $u), (int) (26 * $u), 180, 360, $shade);
            imagesetthickness($image, 1);
            break;

        case 'mug':
            imagefilledrectangle($image, (int) ($cx - 20 * $u), (int) ($cy - 18 * $u), (int) ($cx + 14 * $u), (int) ($cy + 22 * $u), $main);
            imagefilledellipse($image, (int) ($cx - 3 * $u), (int) ($cy - 18 * $u), (int) (34 * $u), (int) (12 * $u), $shade);
            imagesetthickness($image, (int) max(3, 4 * $u));
            imagearc($image, (int) ($cx + 16 * $u), $cy, (int) (22 * $u), (int) (26 * $u), 270, 90, $main);
            imagesetthickness($image, 1);
            break;

        case 'sticker':
            imagefilledellipse($image, $cx, $cy, (int) (54 * $u), (int) (54 * $u), $main);
            imagefilledellipse($image, $cx, $cy, (int) (44 * $u), (int) (44 * $u), $shade);
            break;

        case 'lanyard':
            imagefilledrectangle($image, (int) ($cx - 7 * $u), (int) ($cy - 34 * $u), (int) ($cx + 7 * $u), (int) ($cy + 10 * $u), $main);
            imagefilledrectangle($image, (int) ($cx - 17 * $u), (int) ($cy + 10 * $u), (int) ($cx + 17 * $u), (int) ($cy + 34 * $u), $shade);
            break;
    }

    // The badge mark printed on the item
    $markR = (int) (11 * $u);
    imagefilledellipse($image, $cx, (int) ($cy + 2 * $u), $markR * 2, $markR * 2, rgb($image, PALETTE['gold'], 0.15));
    imagefilledellipse($image, $cx, (int) ($cy + 2 * $u), (int) ($markR * 1.6), (int) ($markR * 1.6), rgb($image, PALETTE['cream'], 0.1));

    if (is_file($font)) {
        $fontSize = max(8, (int) ($size / 42));
        $box      = imagettfbbox($fontSize, 0, $font, $label);
        $textW    = abs($box[4] - $box[0]);
        imagettftext($image, $fontSize, 0, (int) ($cx - $textW / 2), (int) ($size - 9 * $u), rgb($image, PALETTE['vineyard'], 0.15), $font, $label);
    }

    grain($image, $size, $size, $seed, 300);

    return $image;
}

echo "\nGenerating merchandise mockups\n------------------------------\n";

$merch = [
    ['merch/t-shirt',  'tee',     PALETTE['vineyard'], 9100, 'SARCNA 2027 T-SHIRT'],
    ['merch/hoodie',   'hoodie',  PALETTE['forest'],   9200, 'SARCNA 2027 HOODIE'],
    ['merch/cap',      'cap',     PALETTE['clay'],     9300, 'SARCNA 2027 CAP'],
    ['merch/tote-bag', 'tote',    PALETTE['sage'],     9400, 'SARCNA 2027 TOTE'],
    ['merch/mug',      'mug',     PALETTE['cream'],    9500, 'SARCNA 2027 MUG'],
    ['merch/stickers', 'sticker', PALETTE['plum'],     9600, 'SARCNA 2027 STICKERS'],
    ['merch/lanyard',  'lanyard', PALETTE['gold'],     9700, 'SARCNA 2027 LANYARD'],
];

foreach ($merch as [$path, $kind, $garment, $seed, $label]) {
    $image = merchMockup(900, $kind, $garment, $seed, $sansBold, $label);
    save($image, $out . '/' . $path, 84);
    imagedestroy($image);
}

/* --------------------------------------------------------- brand artefacts */

echo "\nGenerating brand artefacts\n--------------------------\n";

/** Draw the badge mark at an arbitrary size. */
function drawBadge($image, int $cx, int $cy, int $r): void
{
    $u = $r / 60;

    imagefilledellipse($image, $cx, $cy, $r * 2, $r * 2, rgb($image, PALETTE['vineyard']));
    imagesetthickness($image, max(1, (int) (1.6 * $u)));
    imageellipse($image, $cx, $cy, (int) ($r * 1.82), (int) ($r * 1.82), rgb($image, PALETTE['gold']));
    imagesetthickness($image, 1);

    $inner = (int) ($r * 0.78);
    $clipTop = $cy - $inner;

    // Sky
    for ($y = -$inner; $y <= $inner; $y++) {
        $halfWidth = (int) sqrt(max(0, $inner * $inner - $y * $y));
        $t         = ($y + $inner) / (2 * $inner);
        $colour    = rgb($image, mix([0xFF, 0xF6, 0xE7], [0xE9, 0xC9, 0x8F], $t));
        imageline($image, $cx - $halfWidth, $cy + $y, $cx + $halfWidth, $cy + $y, $colour);
    }

    // Sun
    imagefilledellipse($image, $cx, (int) ($cy - $inner * 0.05), (int) ($inner * 0.52), (int) ($inner * 0.52), rgb($image, PALETTE['gold']));

    // Mountains, clipped to the disc
    $ridgeY = (int) ($cy + $inner * 0.18);
    for ($x = -$inner; $x <= $inner; $x++) {
        $halfHeight = (int) sqrt(max(0, $inner * $inner - $x * $x));
        $bottom     = $cy + $halfHeight;
        $t          = ($x + $inner) / (2 * $inner);
        $peak       = $ridgeY - (int) ($inner * 0.42 * (0.6 * sin($t * 6.0) + 0.4 * sin($t * 11.0 + 1.1)));

        if ($peak < $cy - $halfHeight) {
            $peak = $cy - $halfHeight;
        }

        imageline($image, $cx + $x, $peak, $cx + $x, $bottom, rgb($image, [0x2A, 0x4A, 0x3C]));
    }

    // The path
    $pathTop = (int) ($cy + $inner * 0.22);
    for ($y = $pathTop; $y <= $cy + $inner; $y++) {
        $progress   = ($y - $pathTop) / max(1, ($cy + $inner) - $pathTop);
        $halfWidth  = (int) ($inner * 0.06 + $inner * 0.30 * $progress);
        $halfHeight = (int) sqrt(max(0, $inner * $inner - ($y - $cy) * ($y - $cy)));
        $halfWidth  = min($halfWidth, $halfHeight);
        imageline($image, $cx - $halfWidth, $y, $cx + $halfWidth, $y, rgb($image, PALETTE['cream']));
    }

    imagesetthickness($image, max(2, (int) (2.4 * $u)));
    imageellipse($image, $cx, $cy, $inner * 2, $inner * 2, rgb($image, PALETTE['forest']));
    imagesetthickness($image, 1);
}

// Apple touch icon 180×180
$icon = imagecreatetruecolor(180, 180);
imagefilledrectangle($icon, 0, 0, 180, 180, rgb($icon, PALETTE['vineyard']));
drawBadge($icon, 90, 90, 78);
imagepng($icon, $brand . '/apple-touch-icon.png', 9);
echo "apple-touch-icon.png                  ", round(filesize($brand . '/apple-touch-icon.png') / 1024), " KB\n";
imagedestroy($icon);

// favicon.ico — a 48×48 PNG wrapped in an ICO container
$fav = imagecreatetruecolor(48, 48);
imagefilledrectangle($fav, 0, 0, 48, 48, rgb($fav, PALETTE['vineyard']));
drawBadge($fav, 24, 24, 22);
ob_start();
imagepng($fav, null, 9);
$pngData = (string) ob_get_clean();
imagedestroy($fav);

$ico = pack('vvv', 0, 1, 1)                                  // reserved, type=icon, count
     . pack('CCCCvvVV', 48, 48, 0, 0, 1, 32, strlen($pngData), 22);
file_put_contents($brand . '/favicon.ico', $ico . $pngData);
echo "favicon.ico                           ", round(filesize($brand . '/favicon.ico') / 1024, 1), " KB\n";

// Social share card 1200×630
$card = landscape(1200, 630, 1017, 'dawn', true, true);
$scrim = imagecolorallocatealpha($card, 14, 36, 28, 52);
imagefilledrectangle($card, 0, 0, 1200, 630, $scrim);
drawBadge($card, 152, 315, 88);

if (is_file($serif)) {
    imagettftext($card, 54, 0, 280, 268, rgb($card, PALETTE['cream']), $serif, 'SARCNA 2027 Convention');
}
if (is_file($serifLight)) {
    imagettftext($card, 30, 0, 282, 330, rgb($card, PALETTE['gold']), $serifLight, 'Rooted in Recovery. Rising Together.');
}
if (is_file($sans)) {
    imagettftext($card, 20, 0, 284, 392, rgb($card, PALETTE['mist']), $sans, '27 – 29 August 2027  ·  Boschendal, Cape Winelands');
}

imagejpeg($card, $brand . '/social-share-card.jpg', 86);
echo "social-share-card.jpg                 ", round(filesize($brand . '/social-share-card.jpg') / 1024), " KB\n";
imagedestroy($card);

echo "\nDone. All imagery is stored locally under /public_html/assets.\n";

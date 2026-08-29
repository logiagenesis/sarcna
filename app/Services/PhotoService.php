<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Database;

/**
 * The photo manager.
 *
 * The venue photographs are the one part of this site that cannot be written
 * in code — somebody has to supply the files. On the target host there is no
 * SSH and no cPanel API, so a command-line importer is of no use to the
 * committee: whoever ends up doing this will be a volunteer with a browser and
 * a folder of pictures.
 *
 * So this service backs a screen that lists every place the site shows a
 * picture, says which are still illustrations, and takes an upload for each
 * one. It enforces the quality bar rather than trusting the uploader: an image
 * below the minimum size for its slot is refused with the reason, because a
 * stretched 600px photo on a 1920px hero looks worse than the illustration it
 * replaced.
 *
 * Everything it writes goes to public_html/uploads/photos, never into the
 * repository's own assets, so an upgrade never overwrites the committee's
 * photographs and a mistake is always reversible.
 */
final class PhotoService
{
    /** Anything smaller than this in either dimension is refused outright. */
    private const ABSOLUTE_MIN_WIDTH  = 800;
    private const ABSOLUTE_MIN_HEIGHT = 500;

    private const MAX_BYTES = 12 * 1024 * 1024;

    /**
     * Every image slot on the public site, grouped for the screen.
     *
     * Read from the database rather than hard-coded, so a room type or product
     * added next year appears here on its own.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function slots(): array
    {
        $groups = [];

        foreach (Database::select('SELECT id, position, title, image FROM banners ORDER BY position, sort_order') as $row) {
            $groups['Home page & banners'][] = self::slot(
                'banner:' . $row['id'],
                (string) $row['title'],
                'Banner · ' . $row['position'],
                $row['image'] === null ? null : (string) $row['image'],
                1920,
                1080
            );
        }

        foreach (Database::select('SELECT id, slug, title, hero_image FROM pages WHERE is_legal = 0 ORDER BY title') as $row) {
            $groups['Page headers'][] = self::slot(
                'page:' . $row['id'],
                (string) $row['title'],
                '/' . $row['slug'],
                $row['hero_image'] === null ? null : (string) $row['hero_image'],
                1600,
                900
            );
        }

        foreach (Database::select('SELECT id, name, slug, hero_image FROM room_types ORDER BY sort_order, name') as $row) {
            $groups['Accommodation'][] = self::slot(
                'room:' . $row['id'],
                (string) $row['name'],
                'Main photograph',
                $row['hero_image'] === null ? null : (string) $row['hero_image'],
                1600,
                1000
            );

            $extra = Database::select(
                'SELECT id, file_path FROM room_type_images WHERE room_type_id = ? ORDER BY sort_order, id',
                [(int) $row['id']]
            );

            $groups['Accommodation'][] = self::slot(
                'room-gallery:' . $row['id'],
                (string) $row['name'] . ' — more photographs',
                count($extra) . ' in the room gallery. Guests want to see inside before they book.',
                $extra[0]['file_path'] ?? null,
                1600,
                1000,
                true
            );
        }

        // The venue gallery is what a delegate actually scrolls through before
        // deciding to come, so every picture in it gets its own slot rather
        // than being buried behind a generic "add an image" form.
        foreach (Database::select(
            "SELECT id, title, alt_text, category, file_path FROM gallery_images
              WHERE is_active = 1 AND category IN ('venue','conference')
              ORDER BY category, sort_order, id"
        ) as $row) {
            $groups['Venue gallery'][] = self::slot(
                'gallery:' . $row['id'],
                (string) ($row['title'] !== '' ? $row['title'] : $row['alt_text']),
                'Shown on the venue page · ' . $row['category'],
                (string) $row['file_path'],
                1600,
                1000
            );
        }

        // …and one always-open slot so the committee can keep adding to it.
        $groups['Venue gallery'][] = self::slot(
            'gallery-new:1',
            'Add another venue photograph',
            'Goes straight onto the venue page.',
            null,
            1600,
            1000,
            true
        );

        foreach (Database::select("SELECT id, name, image FROM products WHERE is_active = 1 AND type NOT IN ('donation') ORDER BY type, name") as $row) {
            $groups['Shop'][] = self::slot(
                'product:' . $row['id'],
                (string) $row['name'],
                'Product photograph',
                $row['image'] === null ? null : (string) $row['image'],
                1200,
                1200
            );
        }

        return $groups;
    }

    /** @return array<string, mixed> */
    private static function slot(
        string $key,
        string $label,
        string $note,
        ?string $current,
        int $width,
        int $height,
        bool $multiple = false
    ): array {
        return [
            'key'         => $key,
            'label'       => $label,
            'note'        => $note,
            'current'     => $current,
            'width'       => $width,
            'height'      => $height,
            'multiple'    => $multiple,
            'placeholder' => self::isPlaceholder($current),
        ];
    }

    /**
     * Is this slot still showing one of the shipped illustrations?
     *
     * The illustrations all live under /assets/img, and every real photograph
     * this service writes lives under uploads/photos. That is the whole test —
     * no guessing from file names.
     */
    public static function isPlaceholder(?string $path): bool
    {
        return $path === null || trim($path) === '' || !str_starts_with(ltrim($path, '/'), 'photos/');
    }

    /** How many slots are still illustrations, and how many there are in total. */
    /** @return array{real:int, total:int} */
    public static function progress(): array
    {
        $real  = 0;
        $total = 0;

        foreach (self::slots() as $slots) {
            foreach ($slots as $slot) {
                $total++;

                if (!$slot['placeholder']) {
                    $real++;
                }
            }
        }

        return ['real' => $real, 'total' => $total];
    }

    /**
     * Validate and store one uploaded photograph.
     *
     * @param array<string, mixed> $file a single $_FILES entry
     * @return array{ok: bool, message: string, path?: string}
     */
    public static function accept(array $file, int $targetWidth, int $targetHeight): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['ok' => false, 'message' => 'No file was chosen.'];
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'message' => 'The upload did not finish. The file may be larger than the server accepts.'];
        }

        if ((int) $file['size'] > self::MAX_BYTES) {
            return ['ok' => false, 'message' => sprintf('That file is %s. The limit is 12 MB.', self::bytes((int) $file['size']))];
        }

        $info = @getimagesize($file['tmp_name']);

        if ($info === false) {
            return ['ok' => false, 'message' => 'That file is not an image this server can read.'];
        }

        [$width, $height, $type] = $info;

        if (!in_array($type, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            return ['ok' => false, 'message' => 'Photographs must be JPEG, PNG or WebP.'];
        }

        // The quality bar. A picture smaller than the slot renders it soft, and
        // a soft venue photograph is worse than the illustration it replaced.
        $minWidth  = max(self::ABSOLUTE_MIN_WIDTH, (int) round($targetWidth * 0.9));
        $minHeight = max(self::ABSOLUTE_MIN_HEIGHT, (int) round($targetHeight * 0.9));

        if ($width < $minWidth || $height < $minHeight) {
            return [
                'ok'      => false,
                'message' => sprintf(
                    'That photograph is %d×%d. This slot needs at least %d×%d, otherwise it will look soft on a modern screen. Please use the original file rather than one saved from a web page or a chat app.',
                    $width,
                    $height,
                    $minWidth,
                    $minHeight
                ),
            ];
        }

        $source = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($file['tmp_name']),
            IMAGETYPE_PNG  => @imagecreatefrompng($file['tmp_name']),
            IMAGETYPE_WEBP => @imagecreatefromwebp($file['tmp_name']),
            default        => false,
        };

        if ($source === false) {
            return ['ok' => false, 'message' => 'That image is damaged and could not be opened.'];
        }

        $directory = rtrim((string) Config::get('paths.uploads'), '/') . '/photos';

        if (!is_dir($directory) && !@mkdir($directory, 0755, true) && !is_dir($directory)) {
            imagedestroy($source);

            return ['ok' => false, 'message' => 'The uploads folder is not writable. In cPanel set public_html/uploads to permissions 755.'];
        }

        $resized = self::coverResize($source, $targetWidth, $targetHeight);
        imagedestroy($source);

        $name = date('Ymd-His') . '-' . bin2hex(random_bytes(4));
        $jpeg = $directory . '/' . $name . '.jpg';

        // Re-encoding through GD is also what strips the EXIF block, so a
        // committee member's phone photograph does not publish its GPS
        // coordinates along with the picture.
        if (!@imagejpeg($resized, $jpeg, 86)) {
            imagedestroy($resized);

            return ['ok' => false, 'message' => 'The photograph could not be written to disk. Check the folder permissions and the disk quota.'];
        }

        @chmod($jpeg, 0644);

        if (function_exists('imagewebp')) {
            @imagewebp($resized, $directory . '/' . $name . '.webp', 82);
            @chmod($directory . '/' . $name . '.webp', 0644);
        }

        imagedestroy($resized);

        return ['ok' => true, 'message' => 'Photograph saved.', 'path' => 'photos/' . $name . '.jpg'];
    }

    /**
     * Scale and centre-crop to exactly the slot's shape.
     *
     * Cover rather than letterbox: a band of grey down the side of a hero is
     * the thing that makes a site look homemade.
     *
     * @param \GdImage $source
     */
    private static function coverResize(\GdImage $source, int $targetWidth, int $targetHeight): \GdImage
    {
        $sourceWidth  = imagesx($source);
        $sourceHeight = imagesy($source);

        // Never upscale: if the picture is smaller than the slot, keep its own
        // size and its aspect ratio rather than inventing pixels.
        if ($sourceWidth < $targetWidth || $sourceHeight < $targetHeight) {
            $scale        = min($sourceWidth / $targetWidth, $sourceHeight / $targetHeight);
            $targetWidth  = max(1, (int) round($targetWidth * $scale));
            $targetHeight = max(1, (int) round($targetHeight * $scale));
        }

        $sourceRatio = $sourceWidth / $sourceHeight;
        $targetRatio = $targetWidth / $targetHeight;

        if ($sourceRatio > $targetRatio) {
            // Wider than the slot: crop the sides.
            $cropHeight = $sourceHeight;
            $cropWidth  = (int) round($sourceHeight * $targetRatio);
        } else {
            // Taller than the slot: crop top and bottom.
            $cropWidth  = $sourceWidth;
            $cropHeight = (int) round($sourceWidth / $targetRatio);
        }

        $cropX = (int) round(($sourceWidth - $cropWidth) / 2);
        $cropY = (int) round(($sourceHeight - $cropHeight) / 2);

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        imagecopyresampled($canvas, $source, 0, 0, $cropX, $cropY, $targetWidth, $targetHeight, $cropWidth, $cropHeight);

        return $canvas;
    }

    private static function bytes(int $size): string
    {
        return $size >= 1048576
            ? round($size / 1048576, 1) . ' MB'
            : round($size / 1024) . ' KB';
    }

    /**
     * Write the accepted path back to whatever the slot points at.
     *
     * @return array{ok: bool, message: string}
     */
    public static function assign(string $slotKey, string $path, string $altText, string $credit): array
    {
        [$kind, $id] = array_pad(explode(':', $slotKey, 2), 2, '');
        $id          = (int) $id;

        if ($id <= 0) {
            return ['ok' => false, 'message' => 'That image slot does not exist.'];
        }

        switch ($kind) {
            case 'banner':
                Database::run('UPDATE banners SET image = ?, image_alt = ? WHERE id = ?', [$path, $altText, $id]);
                break;

            case 'page':
                Database::run('UPDATE pages SET hero_image = ? WHERE id = ?', [$path, $id]);
                break;

            case 'room':
                Database::run('UPDATE room_types SET hero_image = ? WHERE id = ?', [$path, $id]);
                break;

            case 'room-gallery':
                Database::insert('room_type_images', [
                    'room_type_id' => $id,
                    'file_path'    => $path,
                    'alt_text'     => $altText,
                    'source_note'  => $credit,
                    'sort_order'   => (int) Database::scalar(
                        'SELECT COALESCE(MAX(sort_order), 0) + 1 FROM room_type_images WHERE room_type_id = ?',
                        [$id]
                    ),
                ]);
                break;

            case 'product':
                Database::run('UPDATE products SET image = ? WHERE id = ?', [$path, $id]);
                break;

            case 'gallery':
                // Replacing a picture already on the venue page, in place, so
                // it keeps its position in the running order.
                Database::run(
                    'UPDATE gallery_images SET file_path = ?, alt_text = ?, source_note = ? WHERE id = ?',
                    [$path, $altText, $credit, $id]
                );

                return ['ok' => true, 'message' => 'Photograph replaced and now showing on the venue page.'];

            case 'gallery-new':
                Database::insert('gallery_images', [
                    'title'       => $altText,
                    'alt_text'    => $altText,
                    'file_path'   => $path,
                    'category'    => 'venue',
                    'source_note' => $credit,
                    'sort_order'  => (int) Database::scalar('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM gallery_images'),
                    'is_active'   => 1,
                ]);

                return ['ok' => true, 'message' => 'Photograph added to the venue page.'];

            default:
                return ['ok' => false, 'message' => 'That image slot does not exist.'];
        }

        // Every photograph also joins the gallery, so the committee has one
        // place that lists what they hold and where each picture came from.
        // Inactive, because this copy is a record of provenance — the picture
        // is already showing in the slot it was uploaded for.
        Database::insert('gallery_images', [
            'title'       => $altText,
            'alt_text'    => $altText,
            'file_path'   => $path,
            'category'    => match ($kind) {
                'room', 'room-gallery' => 'rooms',
                'product'              => 'merch',
                default                => 'venue',
            },
            'source_note' => $credit,
            'sort_order'  => 0,
            'is_active'   => 0,
        ]);

        return ['ok' => true, 'message' => 'Photograph saved and now showing on the site.'];
    }
}

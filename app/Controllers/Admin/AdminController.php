<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Config;
use App\Core\Controller;
use App\Core\Database;
use App\Services\AuditService;

abstract class AdminController extends Controller
{
    protected const PER_PAGE = 30;

    protected function render(string $template, string $title, array $data = []): string
    {
        return $this->view($template, array_merge($data, ['pageTitle' => $title]));
    }

    protected function page(string $template, array $seo, array $data = []): string
    {
        return $this->render($template, (string) ($seo['title'] ?? 'Admin'), $data);
    }

    /** @return array{rows: array, page: int, pages: int, total: int} */
    protected function paginate(string $countSql, string $rowsSql, array $params = [], int $perPage = self::PER_PAGE): array
    {
        $total = (int) Database::scalar($countSql, $params);
        $pages = max(1, (int) ceil($total / $perPage));
        $page  = max(1, min($pages, $this->request->int('page', 1)));

        $rows = Database::select(
            $rowsSql . sprintf(' LIMIT %d OFFSET %d', $perPage, ($page - 1) * $perPage),
            $params
        );

        return ['rows' => $rows, 'page' => $page, 'pages' => $pages, 'total' => $total];
    }

    protected function audit(string $action, ?string $entity = null, ?int $entityId = null, mixed $changes = null): void
    {
        AuditService::record($action, $entity, $entityId, $changes);
    }

    /**
     * Store an uploaded image under /public_html/uploads and return its path.
     * Type is checked from the file's own bytes, not from the sent MIME type.
     */
    protected function storeImage(string $field, string $folder = 'general'): ?string
    {
        $file = $this->request->file($field);

        if ($file === null) {
            return null;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->flashError('That image failed to upload. It may be larger than the server allows.');

            return null;
        }

        if ($file['size'] > 6 * 1024 * 1024) {
            $this->flashError('Images must be 6 MB or smaller.');

            return null;
        }

        $info = @getimagesize($file['tmp_name']);

        if ($info === false) {
            $this->flashError('That file is not an image we can read.');

            return null;
        }

        $extension = match ($info[2]) {
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG  => 'png',
            IMAGETYPE_WEBP => 'webp',
            IMAGETYPE_GIF  => 'gif',
            default        => null,
        };

        if ($extension === null) {
            $this->flashError('Images must be JPEG, PNG, WebP or GIF.');

            return null;
        }

        $directory = rtrim((string) Config::get('paths.uploads'), '/') . '/' . preg_replace('/[^a-z0-9\-]/', '', $folder);

        if (!is_dir($directory) && !@mkdir($directory, 0755, true) && !is_dir($directory)) {
            $this->flashError('The uploads folder is not writable. Set /public_html/uploads to 755 in cPanel.');

            return null;
        }

        $name = date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
        $path = $directory . '/' . $name;

        if (!move_uploaded_file($file['tmp_name'], $path)) {
            $this->flashError('The image could not be saved.');

            return null;
        }

        @chmod($path, 0644);

        $this->makeWebp($path, $info[2]);

        // Leading slash, so picture() routes this through uploaded() and finds
        // it under /uploads/. Without it the path falls through to asset() and
        // every admin-uploaded image 404s.
        return '/' . preg_replace('/[^a-z0-9\-]/', '', $folder) . '/' . $name;
    }

    /** Write a WebP twin so picture() can serve the smaller file. */
    private function makeWebp(string $path, int $type): void
    {
        if (!function_exists('imagewebp') || $type === IMAGETYPE_WEBP) {
            return;
        }

        $image = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG  => @imagecreatefrompng($path),
            IMAGETYPE_GIF  => @imagecreatefromgif($path),
            default        => false,
        };

        if ($image === false) {
            return;
        }

        imagepalettetotruecolor($image);
        @imagewebp($image, preg_replace('/\.(jpg|png|gif)$/i', '.webp', $path), 82);
        imagedestroy($image);
    }
}

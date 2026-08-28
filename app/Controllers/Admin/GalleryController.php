<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;

final class GalleryController extends AdminController
{
    public function index(): string
    {
        return $this->render('admin.gallery', 'Gallery', [
            'images'     => Database::select('SELECT * FROM gallery_images ORDER BY category, sort_order, id'),
            'categories' => ['venue', 'conference', 'rooms', 'transport', 'merch', 'general'],
        ]);
    }

    public function store(): never
    {
        $path = $this->storeImage('image_file', 'gallery') ?? (string) $this->request->input('file_path', '');

        if (trim($path) === '') {
            $this->flashError('Choose an image to upload, or give the path of an image already on the server.');
            $this->back();
        }

        $altText = trim((string) $this->request->input('alt_text', ''));

        if ($altText === '') {
            $this->flashError('Alt text is required — it is what screen readers and search engines read.');
            $this->back();
        }

        $id = Database::insert('gallery_images', [
            'title'       => (string) $this->request->input('title', ''),
            'alt_text'    => $altText,
            'file_path'   => $path,
            'category'    => (string) ($this->request->input('category') ?: 'venue'),
            'source_note' => (string) $this->request->input('source_note', ''),
            'sort_order'  => $this->request->int('sort_order', 0),
            'is_active'   => 1,
        ]);

        $this->audit('added a gallery image', 'gallery_image', $id);
        $this->flashSuccess('Image added to the gallery.');
        $this->back(url('/admin/gallery'));
    }

    public function destroy(string $id): never
    {
        Database::delete('gallery_images', 'id = ?', [(int) $id]);

        $this->audit('removed a gallery image', 'gallery_image', (int) $id);
        $this->flashSuccess('Image removed from the gallery. The file itself is still on the server.');
        $this->back(url('/admin/gallery'));
    }
}

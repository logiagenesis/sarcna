<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Services\PhotoService;

final class PhotoController extends AdminController
{
    public function index(): string
    {
        return $this->render('admin.photos', 'Photographs', [
            'groups'   => PhotoService::slots(),
            'progress' => PhotoService::progress(),
        ]);
    }

    public function upload(): never
    {
        $slotKey = trim((string) $this->request->input('slot', ''));
        $altText = trim((string) $this->request->input('alt_text', ''));
        $credit  = trim((string) $this->request->input('credit', ''));

        if ($altText === '') {
            $this->flashError('Describe the photograph in the alt text box. Screen readers and Google both read it, and the page fails accessibility without it.');
            $this->back(url('/admin/photos'));
        }

        $slot = $this->findSlot($slotKey);

        if ($slot === null) {
            $this->flashError('That image slot no longer exists. Reload the page and try again.');
            $this->back(url('/admin/photos'));
        }

        $file = $this->request->file('photo');

        if ($file === null) {
            $this->flashError('Choose a photograph to upload.');
            $this->back(url('/admin/photos'));
        }

        $result = PhotoService::accept($file, (int) $slot['width'], (int) $slot['height']);

        if ($result['ok'] !== true) {
            $this->flashError($result['message']);
            $this->back(url('/admin/photos'));
        }

        $assigned = PhotoService::assign($slotKey, (string) $result['path'], $altText, $credit);

        if ($assigned['ok'] !== true) {
            $this->flashError($assigned['message']);
            $this->back(url('/admin/photos'));
        }

        $this->audit('uploaded a photograph for ' . $slot['label'], 'photo', null, ['slot' => $slotKey, 'path' => $result['path']]);
        $this->flashSuccess($slot['label'] . ' — ' . $assigned['message']);
        $this->back(url('/admin/photos'));
    }

    /**
     * Put a slot back to the shipped illustration.
     *
     * The uploaded file is left on disk deliberately: an accidental revert
     * should never destroy the only copy of a photograph the committee has.
     */
    public function reset(): never
    {
        $slotKey     = trim((string) $this->request->input('slot', ''));
        [$kind, $id] = array_pad(explode(':', $slotKey, 2), 2, '');
        $id          = (int) $id;

        if ($id <= 0) {
            $this->flashError('That image slot does not exist.');
            $this->back(url('/admin/photos'));
        }

        match ($kind) {
            'banner'       => Database::run('UPDATE banners SET image = NULL WHERE id = ?', [$id]),
            'page'         => Database::run('UPDATE pages SET hero_image = NULL WHERE id = ?', [$id]),
            'room'         => Database::run('UPDATE room_types SET hero_image = NULL WHERE id = ?', [$id]),
            'room-gallery' => Database::run('DELETE FROM room_type_images WHERE room_type_id = ?', [$id]),
            'product'      => Database::run('UPDATE products SET image = NULL WHERE id = ?', [$id]),
            'gallery'      => Database::run('UPDATE gallery_images SET is_active = 0 WHERE id = ?', [$id]),
            default        => null,
        };

        $this->audit('reset a photograph slot', 'photo', $id, ['slot' => $slotKey]);

        $this->flashSuccess($kind === 'gallery'
            ? 'That picture has been taken off the venue page. The file is still on the server.'
            : 'That slot is back to the shipped illustration. The uploaded file is still on the server.');

        $this->back(url('/admin/photos'));
    }

    /** @return array<string, mixed>|null */
    private function findSlot(string $key): ?array
    {
        foreach (PhotoService::slots() as $slots) {
            foreach ($slots as $slot) {
                if ($slot['key'] === $key) {
                    return $slot;
                }
            }
        }

        return null;
    }
}

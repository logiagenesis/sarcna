<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Validator;
use App\Services\AccommodationService;

final class RoomController extends AdminController
{
    public function index(): string
    {
        $roomTypes = AccommodationService::roomTypes(false);

        foreach ($roomTypes as &$roomType) {
            $roomType['availability'] = AccommodationService::availability((int) $roomType['id']);
        }
        unset($roomType);

        return $this->render('admin.rooms', 'Room types & beds', [
            'roomTypes'   => $roomTypes,
            'nightLabels' => AccommodationService::nightLabels(),
            'occupancy'   => AccommodationService::occupancySummary(),
        ]);
    }

    public function create(): string
    {
        return $this->render('admin.room-edit', 'New room type', [
            'roomType' => null,
            'units'    => [],
            'rates'    => [],
            'images'   => [],
            'nights'   => AccommodationService::nights(),
        ]);
    }

    public function edit(string $id): string
    {
        $roomType = AccommodationService::findRoomType((int) $id);

        if ($roomType === null) {
            $this->abort(404);
        }

        return $this->render('admin.room-edit', $roomType['name'], [
            'roomType' => $roomType,
            'units'    => Database::select(
                'SELECT ru.*, (SELECT COUNT(*) FROM beds b WHERE b.room_unit_id = ru.id) AS bed_count
                   FROM room_units ru WHERE ru.room_type_id = ? ORDER BY ru.sort_order, ru.id',
                [(int) $roomType['id']]
            ),
            'beds'     => Database::select(
                'SELECT b.*, ru.name AS unit_name,
                        (SELECT COUNT(*) FROM bookings bk WHERE bk.bed_id = b.id AND bk.status IN ("confirmed","checked_in")) AS booked_nights
                   FROM beds b JOIN room_units ru ON ru.id = b.room_unit_id
                  WHERE ru.room_type_id = ? ORDER BY ru.sort_order, b.sort_order',
                [(int) $roomType['id']]
            ),
            'rates'    => AccommodationService::ratesFor($roomType),
            'images'   => AccommodationService::images((int) $roomType['id']),
            'nights'   => AccommodationService::nights(),
        ]);
    }

    public function store(): never
    {
        $data = $this->validated();
        $data['slug'] = $this->uniqueSlug($data['slug'] ?: $data['name']);

        $image = $this->storeImage('hero_image_file', 'rooms');
        if ($image !== null) {
            $data['hero_image'] = $image;
        }

        $id = Database::insert('room_types', $data);

        $this->audit('created a room type', 'room_type', $id, ['name' => $data['name']]);
        $this->flashSuccess('Room type created. Now generate its units and beds.');
        $this->redirect(url('/admin/rooms/' . $id));
    }

    public function update(string $id): never
    {
        $roomType = AccommodationService::findRoomType((int) $id);

        if ($roomType === null) {
            $this->abort(404);
        }

        $data = $this->validated();
        $data['slug'] = $this->uniqueSlug($data['slug'] ?: $data['name'], (int) $roomType['id']);

        $image = $this->storeImage('hero_image_file', 'rooms');
        if ($image !== null) {
            $data['hero_image'] = $image;
        }

        Database::update('room_types', $data, 'id = :id', ['id' => (int) $roomType['id']]);

        $this->audit('updated a room type', 'room_type', (int) $roomType['id']);
        $this->flashSuccess('Room type saved.');
        $this->back(url('/admin/rooms/' . $roomType['id']));
    }

    /**
     * Create units and their beds. Existing units are left alone, so this can
     * be run again to add capacity without disturbing live bookings.
     */
    public function generateUnits(string $id): never
    {
        $roomType = AccommodationService::findRoomType((int) $id);

        if ($roomType === null) {
            $this->abort(404);
        }

        $count    = max(1, min(200, $this->request->int('unit_count', 1)));
        $bedCount = max(1, min(20, $this->request->int('beds_per_unit', (int) $roomType['beds_per_unit'])));
        $existing = (int) Database::scalar('SELECT COUNT(*) FROM room_units WHERE room_type_id = ?', [(int) $roomType['id']]);

        $createdUnits = 0;
        $createdBeds  = 0;

        for ($index = 1; $index <= $count; $index++) {
            $number = $existing + $index;

            $unitId = Database::insert('room_units', [
                'room_type_id' => (int) $roomType['id'],
                'name'         => sprintf('%s %02d', $roomType['name'], $number),
                'code'         => strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $roomType['slug']) ?: 'UNIT', 0, 3)) . sprintf('%02d', $number),
                'sort_order'   => $number,
            ]);

            $createdUnits++;

            for ($bed = 0; $bed < $bedCount; $bed++) {
                Database::insert('beds', [
                    'room_unit_id' => $unitId,
                    'label'        => 'Bed ' . chr(65 + $bed),
                    'sort_order'   => $bed,
                ]);

                $createdBeds++;
            }
        }

        $this->audit('generated accommodation units', 'room_type', (int) $roomType['id'], ['units' => $createdUnits, 'beds' => $createdBeds]);
        $this->flashSuccess(sprintf('Added %d unit(s) and %d bed(s).', $createdUnits, $createdBeds));
        $this->back(url('/admin/rooms/' . $roomType['id']));
    }

    public function saveRates(string $id): never
    {
        $roomType = AccommodationService::findRoomType((int) $id);

        if ($roomType === null) {
            $this->abort(404);
        }

        foreach (AccommodationService::nights() as $night) {
            $bedRate  = rands($this->request->input('bed_rate_' . $night, 0));
            $unitRate = rands($this->request->input('unit_rate_' . $night, 0));
            $available = $this->request->bool('available_' . $night) ? 1 : 0;
            $label     = (string) $this->request->input('label_' . $night, '');

            if ($bedRate <= 0) {
                continue;
            }

            $existing = Database::first('SELECT id FROM bed_rates WHERE room_type_id = ? AND night = ?', [(int) $roomType['id'], $night]);

            $payload = [
                'bed_rate_cents'          => $bedRate,
                'private_unit_rate_cents' => $unitRate > 0 ? $unitRate : null,
                'is_available'            => $available,
                'label'                   => $label !== '' ? $label : null,
            ];

            if ($existing === null) {
                Database::insert('bed_rates', array_merge($payload, [
                    'room_type_id' => (int) $roomType['id'],
                    'night'        => $night,
                ]));
            } else {
                Database::update('bed_rates', $payload, 'id = :id', ['id' => (int) $existing['id']]);
            }
        }

        $this->audit('updated bed rates', 'room_type', (int) $roomType['id']);
        $this->flashSuccess('Nightly rates saved.');
        $this->back(url('/admin/rooms/' . $roomType['id']));
    }

    public function toggleUnit(string $unitId): never
    {
        $unit = Database::first('SELECT * FROM room_units WHERE id = ?', [(int) $unitId]);

        if ($unit === null) {
            $this->abort(404);
        }

        $booked = (int) Database::scalar(
            'SELECT COUNT(*) FROM bookings WHERE room_unit_id = ? AND status IN ("confirmed","checked_in")',
            [(int) $unit['id']]
        );

        if ($booked > 0 && (int) $unit['is_active'] === 1) {
            $this->flashError('That unit has confirmed bookings. Move or cancel them before taking it out of service.');
            $this->back();
        }

        Database::update('room_units', ['is_active' => (int) $unit['is_active'] === 1 ? 0 : 1], 'id = :id', ['id' => (int) $unit['id']]);

        $this->audit('toggled a room unit', 'room_unit', (int) $unit['id']);
        $this->flashSuccess($unit['name'] . ' is now ' . ((int) $unit['is_active'] === 1 ? 'out of service' : 'in service') . '.');
        $this->back();
    }

    public function toggleBed(string $bedId): never
    {
        $bed = Database::first('SELECT * FROM beds WHERE id = ?', [(int) $bedId]);

        if ($bed === null) {
            $this->abort(404);
        }

        $booked = (int) Database::scalar(
            'SELECT COUNT(*) FROM bookings WHERE bed_id = ? AND status IN ("confirmed","checked_in")',
            [(int) $bed['id']]
        );

        if ($booked > 0 && (int) $bed['is_active'] === 1) {
            $this->flashError('That bed has confirmed bookings and cannot be taken out of service.');
            $this->back();
        }

        Database::update('beds', ['is_active' => (int) $bed['is_active'] === 1 ? 0 : 1], 'id = :id', ['id' => (int) $bed['id']]);

        $this->audit('toggled a bed', 'bed', (int) $bed['id']);
        $this->back();
    }

    private function validated(): array
    {
        $validator = Validator::make($this->request->all(), [
            'name'           => 'required|max:140',
            'slug'           => 'max:160',
            'summary'        => 'max:255',
            'beds_per_unit'  => 'required|integer|gte:1|lte:20',
            'bed_rate'       => 'required|numeric|gte:0',
        ], ['bed_rate' => 'Bed rate']);

        if ($validator->fails()) {
            $this->withErrors($validator->errors());
        }

        $unitRate = rands($this->request->input('private_unit_rate', 0));

        return [
            'name'                    => (string) $this->request->input('name'),
            'slug'                    => slugify((string) $this->request->input('slug', '')),
            'summary'                 => (string) $this->request->input('summary', ''),
            'description'             => (string) $this->request->input('description', ''),
            'beds_per_unit'           => $this->request->int('beds_per_unit', 2),
            'bed_rate_cents'          => rands($this->request->input('bed_rate', 0)),
            'private_unit_rate_cents' => $unitRate > 0 ? $unitRate : null,
            'allows_private_buyout'   => $this->request->bool('allows_private_buyout') ? 1 : 0,
            'is_accessible'           => $this->request->bool('is_accessible') ? 1 : 0,
            'is_offsite'              => $this->request->bool('is_offsite') ? 1 : 0,
            'amenities'               => (string) $this->request->input('amenities', ''),
            'hero_image'              => (string) $this->request->input('hero_image', ''),
            'meta_title'              => (string) $this->request->input('meta_title', ''),
            'meta_description'        => (string) $this->request->input('meta_description', ''),
            'sort_order'              => $this->request->int('sort_order', 0),
            'is_active'               => $this->request->bool('is_active') ? 1 : 0,
            'is_mock'                 => $this->request->bool('is_mock') ? 1 : 0,
        ];
    }

    private function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $slug = slugify($value);
        $base = $slug;
        $i    = 2;

        while (true) {
            $sql    = 'SELECT COUNT(*) FROM room_types WHERE slug = ?';
            $params = [$slug];

            if ($ignoreId !== null) {
                $sql     .= ' AND id <> ?';
                $params[] = $ignoreId;
            }

            if ((int) Database::scalar($sql, $params) === 0) {
                return $slug;
            }

            $slug = $base . '-' . $i++;
        }
    }
}

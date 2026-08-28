<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Validator;
use App\Services\AccommodationService;
use App\Services\CartService;
use App\Services\SeoService;
use App\Services\SettingsService;

final class AccommodationController extends Controller
{
    public function index(): string
    {
        $roomTypes = AccommodationService::roomTypes();
        $token     = CartService::token();

        foreach ($roomTypes as &$roomType) {
            $roomType['availability'] = AccommodationService::availability((int) $roomType['id'], $token);
            $roomType['rates']        = AccommodationService::ratesFor($roomType);
        }
        unset($roomType);

        SeoService::breadcrumbs(['Accommodation' => '/accommodation']);

        return $this->page('pages.accommodation-index', [
            'title'       => 'Accommodation & Bed Booking',
            'description' => 'Book accommodation for the SARCNA 2027 Convention one bed at a time, from R700 per bed per night. Shared cottages, private units and accessible rooms at Boschendal in the Cape Winelands.',
            'image'       => '/assets/img/rooms/retreat-twin-room.jpg',
        ], [
            'roomTypes'   => $roomTypes,
            'nightLabels' => AccommodationService::nightLabels(),
            'occupancy'   => AccommodationService::occupancySummary(),
            'isOpen'      => SettingsService::bool('accommodation_enabled', true),
        ]);
    }

    public function show(string $slug): string
    {
        $roomType = AccommodationService::findRoomType($slug);

        if ($roomType === null || (int) $roomType['is_active'] !== 1) {
            $this->abort(404);
        }

        $token  = CartService::token();
        $nights = AccommodationService::nights();
        $rates  = AccommodationService::ratesFor($roomType);

        $availability = [];
        $freeUnits    = [];

        foreach ($nights as $night) {
            $availability[$night] = count(AccommodationService::freeBedIds((int) $roomType['id'], $night, $token));
            $freeUnits[$night]    = count(AccommodationService::freeUnits((int) $roomType['id'], [$night], $token));
        }

        SeoService::breadcrumbs(['Accommodation' => '/accommodation', $roomType['name'] => '/accommodation/' . $roomType['slug']]);

        return $this->page('pages.accommodation-show', [
            'title'       => $roomType['meta_title'] ?: $roomType['name'],
            'description' => $roomType['meta_description'] ?: excerpt($roomType['summary'], 155),
            'image'       => $roomType['hero_image'],
        ], [
            'roomType'     => $roomType,
            'images'       => AccommodationService::images((int) $roomType['id']),
            'nights'       => $nights,
            'rates'        => $rates,
            'availability' => $availability,
            'freeUnits'    => $freeUnits,
            'amenities'    => array_filter(array_map('trim', explode('|', (string) $roomType['amenities']))),
            'holdMinutes'  => AccommodationService::holdMinutes(),
            'isOpen'       => SettingsService::bool('accommodation_enabled', true),
            'others'       => Database::select('SELECT * FROM room_types WHERE is_active = 1 AND id <> ? ORDER BY sort_order LIMIT 3', [(int) $roomType['id']]),
        ]);
    }

    /**
     * Put beds in the cart.
     *
     * Bed mode  — take N beds for each selected night, preferring beds in the
     *             same unit so people travelling together stay together.
     * Unit mode — take every bed in one unit that is free for all the nights.
     */
    public function book(string $slug): never
    {
        if (!SettingsService::bool('accommodation_enabled', true)) {
            $this->flashError('Accommodation booking is closed at the moment.');
            $this->back(url('/accommodation'));
        }

        $roomType = AccommodationService::findRoomType($slug);

        if ($roomType === null || (int) $roomType['is_active'] !== 1) {
            $this->abort(404);
        }

        $selectedNights = array_values(array_intersect(
            array_map('strval', $this->request->array('nights')),
            AccommodationService::nights()
        ));

        $validator = Validator::make(array_merge($this->request->all(), ['nights' => $selectedNights]), [
            'mode'   => 'required|in:bed,unit',
            'nights' => 'required',
            'beds'   => 'integer|gte:1|lte:4',
        ], ['nights' => 'Nights']);

        if ($validator->fails() || $selectedNights === []) {
            $this->flashError('Choose at least one night before adding accommodation to your cart.');
            $this->back(url('/accommodation/' . $roomType['slug']));
        }

        $mode      = (string) $this->request->input('mode', 'bed');
        $bedCount  = max(1, min(4, $this->request->int('beds', 1)));
        $token     = CartService::token();
        $guestMeta = [
            'guest_name'          => (string) $this->request->input('guest_name', ''),
            'roommate_request'    => (string) $this->request->input('roommate_request', ''),
            'accessibility_needs' => (string) $this->request->input('accessibility_needs', ''),
            'notes'               => (string) $this->request->input('notes', ''),
        ];

        if ($mode === 'unit' && (int) $roomType['allows_private_buyout'] !== 1) {
            $this->flashError('Private unit booking is not available for this room type.');
            $this->back(url('/accommodation/' . $roomType['slug']));
        }

        try {
            $added = $mode === 'unit'
                ? $this->bookPrivateUnit($roomType, $selectedNights, $token, $guestMeta)
                : $this->bookBeds($roomType, $selectedNights, $bedCount, $token, $guestMeta);
        } catch (\RuntimeException $e) {
            $this->flashError($e->getMessage());
            $this->back(url('/accommodation/' . $roomType['slug']));
        }

        if ($added === 0) {
            $this->flashError('Those nights are no longer available. Please choose different nights or another room type.');
            $this->back(url('/accommodation/' . $roomType['slug']));
        }

        $this->flashSuccess(sprintf(
            '%d night%s added to your cart and held for %d minutes.',
            $added,
            $added === 1 ? '' : 's',
            AccommodationService::holdMinutes()
        ));

        $this->redirect(url('/cart'));
    }

    /** @return int number of nights added */
    private function bookBeds(array $roomType, array $nights, int $bedCount, string $token, array $guestMeta): int
    {
        $added = 0;

        foreach ($nights as $night) {
            $rate = AccommodationService::rateFor($roomType, $night);

            if (!$rate['available']) {
                continue;
            }

            $bedIds = $this->pickBeds((int) $roomType['id'], $night, $bedCount, $token);

            if ($bedIds === []) {
                throw new \RuntimeException(sprintf(
                    'Only %d bed(s) are left in the %s on %s. Please reduce the number of beds or pick another night.',
                    count(AccommodationService::freeBedIds((int) $roomType['id'], $night, $token)),
                    $roomType['name'],
                    za_date($night, 'D j M')
                ));
            }

            $unitPrice = $rate['bed'] * count($bedIds);

            foreach ($bedIds as $bedId) {
                AccommodationService::holdBed($token, $bedId, $night, $rate['bed'], false, auth_id());
            }

            $unit = Database::first(
                'SELECT ru.name FROM beds b JOIN room_units ru ON ru.id = b.room_unit_id WHERE b.id = ?',
                [$bedIds[0]]
            );

            CartService::add([
                'item_type'        => 'accommodation',
                'bed_id'           => $bedIds[0],
                'room_type_id'     => (int) $roomType['id'],
                'night'            => $night,
                'description'      => sprintf(
                    '%s — %d bed%s, %s',
                    $roomType['name'],
                    count($bedIds),
                    count($bedIds) === 1 ? '' : 's',
                    za_date($night, 'D j M Y')
                ),
                'unit_price_cents' => $unitPrice,
                'quantity'         => 1,
                'meta'             => array_merge($guestMeta, [
                    'bed_ids'         => $bedIds,
                    'bed_count'       => count($bedIds),
                    'unit_name'       => $unit['name'] ?? null,
                    'room_type'       => $roomType['name'],
                    'is_private_unit' => 0,
                    'night_label'     => za_date($night, 'D j M Y'),
                ]),
            ]);

            $added++;
        }

        return $added;
    }

    private function bookPrivateUnit(array $roomType, array $nights, string $token, array $guestMeta): int
    {
        $units = AccommodationService::freeUnits((int) $roomType['id'], $nights, $token);

        if ($units === []) {
            throw new \RuntimeException(sprintf(
                'No whole %s is free for all of the nights you chose. Try fewer nights, or book individual beds.',
                $roomType['name']
            ));
        }

        $unit  = $units[0];
        $added = 0;

        foreach ($nights as $night) {
            $rate = AccommodationService::rateFor($roomType, $night);

            if (!$rate['available']) {
                continue;
            }

            $price = $rate['unit'] ?? ($rate['bed'] * count($unit['bed_ids']));

            foreach ($unit['bed_ids'] as $bedId) {
                AccommodationService::holdBed($token, (int) $bedId, $night, (int) round($price / max(1, count($unit['bed_ids']))), true, auth_id());
            }

            CartService::add([
                'item_type'        => 'accommodation',
                'bed_id'           => (int) $unit['bed_ids'][0],
                'room_type_id'     => (int) $roomType['id'],
                'night'            => $night,
                'description'      => sprintf('%s — whole unit (%s), %s', $roomType['name'], $unit['name'], za_date($night, 'D j M Y')),
                'unit_price_cents' => (int) $price,
                'quantity'         => 1,
                'meta'             => array_merge($guestMeta, [
                    'bed_ids'         => array_map('intval', $unit['bed_ids']),
                    'bed_count'       => count($unit['bed_ids']),
                    'unit_name'       => $unit['name'],
                    'room_type'       => $roomType['name'],
                    'is_private_unit' => 1,
                    'night_label'     => za_date($night, 'D j M Y'),
                ]),
            ]);

            $added++;
        }

        return $added;
    }

    /**
     * Choose beds for one night, preferring beds that share a unit.
     *
     * @return int[] empty when there are not enough free beds
     */
    private function pickBeds(int $roomTypeId, string $night, int $wanted, string $token): array
    {
        $free = AccommodationService::freeBedIds($roomTypeId, $night, $token);

        if (count($free) < $wanted) {
            return [];
        }

        if ($wanted === 1) {
            return [$free[0]];
        }

        $placeholders = implode(',', array_fill(0, count($free), '?'));
        $rows = Database::select(
            "SELECT id, room_unit_id FROM beds WHERE id IN ({$placeholders}) ORDER BY room_unit_id, sort_order, id",
            $free
        );

        $byUnit = [];
        foreach ($rows as $row) {
            $byUnit[(int) $row['room_unit_id']][] = (int) $row['id'];
        }

        foreach ($byUnit as $bedIds) {
            if (count($bedIds) >= $wanted) {
                return array_slice($bedIds, 0, $wanted);
            }
        }

        // Nothing large enough in one unit — spread across units.
        return array_slice($free, 0, $wanted);
    }
}

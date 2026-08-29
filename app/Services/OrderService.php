<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;

/**
 * Orders move: pending_payment -> paid | failed | cancelled.
 *
 * Only a verified PayFast ITN may call markPaid(). Landing on the return URL
 * never marks an order paid — that is the single most important rule here.
 */
final class OrderService
{
    public static function createFromCart(array $customer, array $totals, array $itemDetails = []): array
    {
        $cart = CartService::cart();

        return Database::transaction(static function () use ($cart, $customer, $totals, $itemDetails): array {
            $orderId = Database::insert('orders', [
                'reference'        => self::uniqueReference(),
                'cart_token'       => $cart['token'] ?? null,
                'user_id'          => AuthService::id(),
                'email'            => strtolower(trim((string) $customer['email'])),
                'first_name'       => $customer['first_name'] ?? null,
                'last_name'        => $customer['last_name'] ?? null,
                'phone'            => $customer['phone'] ?? null,
                'status'           => 'pending_payment',
                'subtotal_cents'   => (int) $totals['subtotal_cents'],
                'discount_cents'   => (int) $totals['discount_cents'],
                'total_cents'      => (int) $totals['total_cents'],
                'coupon_id'        => $totals['coupon']['id'] ?? null,
                'coupon_code'      => $totals['coupon']['code'] ?? null,
                'customer_note'    => $customer['note'] ?? null,
                'terms_accepted_at' => date('Y-m-d H:i:s'),
                'checkin_code'     => reference_code('CHK'),
                'ip'               => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
            ]);

            foreach ($totals['items'] as $item) {
                $meta = is_array($item['meta']) ? $item['meta'] : CartService::decodeMeta($item['meta']);

                // Attendee / passenger / guest details captured on the checkout page.
                if (isset($itemDetails[$item['id']]) && is_array($itemDetails[$item['id']])) {
                    $meta = array_merge($meta, $itemDetails[$item['id']]);
                }

                Database::insert('order_items', [
                    'order_id'          => $orderId,
                    'item_type'         => $item['item_type'],
                    'product_id'        => $item['product_id'],
                    'variant_id'        => $item['variant_id'],
                    'bed_id'            => $item['bed_id'],
                    'room_type_id'      => $item['room_type_id'],
                    'night'             => $item['night'],
                    'transport_slot_id' => $item['transport_slot_id'],
                    'description'       => $item['description'],
                    'unit_price_cents'  => (int) $item['unit_price_cents'],
                    'quantity'          => (int) $item['quantity'],
                    'total_cents'       => (int) $item['unit_price_cents'] * (int) $item['quantity'],
                    'meta'              => json_encode($meta, JSON_UNESCAPED_UNICODE),
                ]);
            }

            $order = self::find($orderId);

            self::log($orderId, 'order_created', 'Order created and awaiting payment.', [
                'total_cents' => $totals['total_cents'],
                'cart_token'  => $cart['token'] ?? null,
            ]);

            return $order;
        });
    }

    /**
     * Hold transport seats for a pending order. Beds are already held by the
     * cart; seats are counted here so a shuttle cannot be oversold mid-checkout.
     */
    public static function reserveTransportSeats(array $order): array
    {
        $problems = [];

        foreach (self::items($order['id']) as $item) {
            if ($item['item_type'] !== 'transport' || $item['transport_slot_id'] === null) {
                continue;
            }

            if (!TransportService::reserveSeats((int) $item['transport_slot_id'], (int) $item['quantity'])) {
                $problems[] = $item['description'];
            }
        }

        return $problems;
    }

    public static function releaseTransportSeats(array $order): void
    {
        foreach (self::items($order['id']) as $item) {
            if ($item['item_type'] === 'transport' && $item['transport_slot_id'] !== null) {
                TransportService::releaseSeats((int) $item['transport_slot_id'], (int) $item['quantity']);
            }
        }
    }

    /* --------------------------------------------------------- fulfilment */

    /**
     * Fulfil a paid order: confirm beds, write passenger records, reduce stock,
     * mark donations paid, and email everyone. Safe to call twice — the ITN can
     * legitimately arrive more than once.
     */
    public static function markPaid(array $order, ?array $payment = null): array
    {
        if ($order['status'] === 'paid') {
            self::log((int) $order['id'], 'itn_duplicate', 'Payment notification received for an already-paid order. Ignored.');

            return self::find((int) $order['id']);
        }

        $cartToken = $payment['cart_token'] ?? ($order['cart_token'] ?? null);

        Database::transaction(static function () use ($order): void {
            Database::update('orders', [
                'status'  => 'paid',
                'paid_at' => date('Y-m-d H:i:s'),
            ], 'id = :id', ['id' => (int) $order['id']]);
        });

        $order = self::find((int) $order['id']);

        // 1. Accommodation — holds become bookings.
        self::confirmAccommodation($order, $cartToken);

        // 2. Transport — passenger records (seats were already counted).
        self::confirmTransport($order);

        // 3. Merchandise / registration — stock comes down.
        self::reduceStock($order);

        // 4. Donations.
        self::confirmDonations($order);

        // 5. Coupon usage.
        if ($order['coupon_id'] !== null) {
            Database::run('UPDATE coupons SET used_count = used_count + 1 WHERE id = ?', [(int) $order['coupon_id']]);
        }

        self::log((int) $order['id'], 'order_paid', 'Order marked as paid and fulfilled.');

        MailService::orderPaid($order);
        MailService::adminNewOrder($order);

        return self::find((int) $order['id']);
    }

    private static function confirmAccommodation(array $order, ?string $cartToken): void
    {
        $accommodationItems = array_values(array_filter(
            self::items((int) $order['id']),
            static fn (array $item): bool => $item['item_type'] === 'accommodation'
        ));

        if ($accommodationItems === []) {
            return;
        }

        $guestDetails = ['default' => []];

        foreach ($accommodationItems as $item) {
            $meta = CartService::decodeMeta($item['meta']);
            $key  = $item['room_type_id'] . ':' . $item['night'];

            $guestDetails[$key] = [
                'guest_name'          => $meta['guest_name'] ?? null,
                'guest_email'         => $meta['guest_email'] ?? null,
                'guest_phone'         => $meta['guest_phone'] ?? null,
                'roommate_request'    => $meta['roommate_request'] ?? null,
                'accessibility_needs' => $meta['accessibility_needs'] ?? null,
                'notes'               => $meta['notes'] ?? null,
            ];
        }

        if ($cartToken !== null && $cartToken !== '') {
            $created = AccommodationService::confirmHolds($cartToken, $order, $guestDetails);

            if (count($created) > 0) {
                return;
            }
        }

        // The hold expired (a very slow payment) — try to allocate the same
        // beds directly, and flag the order if any bed is genuinely gone.
        self::allocateBedsWithoutHolds($order, $accommodationItems, $guestDetails);
    }

    private static function allocateBedsWithoutHolds(array $order, array $items, array $guestDetails): void
    {
        $unallocated = [];

        foreach ($items as $item) {
            $meta   = CartService::decodeMeta($item['meta']);
            $bedIds = $meta['bed_ids'] ?? array_filter([$item['bed_id']]);
            $night  = (string) $item['night'];
            $key    = $item['room_type_id'] . ':' . $night;
            $count  = max(1, count($bedIds));
            $share  = (int) floor((int) $item['total_cents'] / $count);

            foreach (array_values($bedIds) as $index => $bedId) {
                $bed = Database::first(
                    'SELECT b.id, b.room_unit_id, ru.room_type_id FROM beds b
                       JOIN room_units ru ON ru.id = b.room_unit_id WHERE b.id = ?',
                    [(int) $bedId]
                );

                if ($bed === null) {
                    $unallocated[] = $item['description'];
                    continue;
                }

                $price = $index === 0 ? (int) $item['total_cents'] - ($share * ($count - 1)) : $share;
                $details = $guestDetails[$key] ?? [];

                try {
                    Database::insert('bookings', [
                        'reference'           => reference_code('BED'),
                        'order_id'            => (int) $order['id'],
                        'order_item_id'       => (int) $item['id'],
                        'user_id'             => $order['user_id'] === null ? null : (int) $order['user_id'],
                        'bed_id'              => (int) $bed['id'],
                        'room_unit_id'        => (int) $bed['room_unit_id'],
                        'room_type_id'        => (int) $bed['room_type_id'],
                        'night'               => $night,
                        'is_private_unit'     => (int) ($meta['is_private_unit'] ?? 0),
                        'guest_name'          => $details['guest_name'] ?? trim(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? '')),
                        'guest_email'         => $details['guest_email'] ?? $order['email'],
                        'guest_phone'         => $details['guest_phone'] ?? $order['phone'],
                        'roommate_request'    => $details['roommate_request'] ?? null,
                        'accessibility_needs' => $details['accessibility_needs'] ?? null,
                        'notes'               => $details['notes'] ?? null,
                        'price_cents'         => $price,
                        'status'              => 'confirmed',
                    ]);
                } catch (\PDOException $e) {
                    $unallocated[] = $item['description'];
                    Logger::error('Bed could not be allocated after payment', [
                        'order' => $order['reference'],
                        'bed'   => $bedId,
                        'night' => $night,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        if ($unallocated !== []) {
            Database::update('orders', [
                'admin_note' => 'NEEDS ATTENTION: paid but these bed-nights could not be allocated — ' . implode('; ', array_unique($unallocated)),
            ], 'id = :id', ['id' => (int) $order['id']]);

            self::log((int) $order['id'], 'accommodation_conflict', 'Paid order has unallocated bed-nights.', $unallocated);
            MailService::adminAccommodationConflict($order, $unallocated);
        }
    }

    private static function confirmTransport(array $order): void
    {
        foreach (self::items((int) $order['id']) as $item) {
            if ($item['item_type'] !== 'transport' || $item['transport_slot_id'] === null) {
                continue;
            }

            $existing = (int) Database::scalar(
                'SELECT COUNT(*) FROM transport_bookings WHERE order_item_id = ?',
                [(int) $item['id']]
            );

            if ($existing > 0) {
                continue;
            }

            $slot = TransportService::findSlot((int) $item['transport_slot_id']);

            if ($slot === null) {
                continue;
            }

            $meta       = CartService::decodeMeta($item['meta']);
            $passengers = $meta['passengers'] ?? [];

            if ($passengers === []) {
                $passengers = [[
                    'passenger_name'      => $meta['passenger_name'] ?? trim(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? '')),
                    'phone'               => $meta['phone'] ?? $order['phone'] ?? '',
                    'email'               => $meta['email'] ?? $order['email'],
                    'flight_number'       => $meta['flight_number'] ?? null,
                    'luggage_count'       => $meta['luggage_count'] ?? 1,
                    'accessibility_needs' => $meta['accessibility_needs'] ?? null,
                    'notes'               => $meta['notes'] ?? null,
                ]];
            }

            $unitPrice = (int) $item['unit_price_cents'];

            for ($seat = 0; $seat < (int) $item['quantity']; $seat++) {
                $passenger = $passengers[$seat] ?? $passengers[0];

                Database::insert('transport_bookings', [
                    'reference'           => reference_code('TRN'),
                    'order_id'            => (int) $order['id'],
                    'order_item_id'       => (int) $item['id'],
                    'user_id'             => $order['user_id'] === null ? null : (int) $order['user_id'],
                    'slot_id'             => (int) $slot['id'],
                    'route_id'            => (int) $slot['route_id'],
                    'passenger_name'      => (string) ($passenger['passenger_name'] ?? $order['email']),
                    'phone'               => (string) ($passenger['phone'] ?? ''),
                    'email'               => (string) ($passenger['email'] ?? $order['email']),
                    'flight_number'       => $passenger['flight_number'] ?? null,
                    'luggage_count'       => (int) ($passenger['luggage_count'] ?? 1),
                    'accessibility_needs' => $passenger['accessibility_needs'] ?? null,
                    'notes'               => $passenger['notes'] ?? null,
                    'price_cents'         => $unitPrice,
                    'status'              => 'confirmed',
                ]);
            }
        }
    }

    private static function reduceStock(array $order): void
    {
        foreach (self::items((int) $order['id']) as $item) {
            if (!in_array($item['item_type'], ['merchandise', 'registration'], true) || $item['product_id'] === null) {
                continue;
            }

            $ok = ProductService::decrementStock(
                (int) $item['product_id'],
                $item['variant_id'] === null ? null : (int) $item['variant_id'],
                (int) $item['quantity'],
                (int) $order['id']
            );

            if (!$ok) {
                Logger::warning('Stock went short on a paid order', [
                    'order' => $order['reference'],
                    'item'  => $item['description'],
                ]);
            }
        }

        $low = ProductService::lowStock(5);

        if ($low !== []) {
            MailService::adminLowStock($low);
        }
    }

    /**
     * One donation record per donation line item.
     *
     * The check here has to be per line item, not per order. An order can
     * legitimately carry several donations — a Seventh Tradition contribution
     * and a sponsored newcomer registration, say — and asking whether the
     * ORDER already had a donation meant the first insert answered yes for all
     * the rest. The money was taken and counted as income, but every donation
     * after the first was missing from the ledger, the donations screen, the
     * CSV and the public "raised so far" total.
     *
     * donations.order_item_id carries a unique index, so this stays idempotent
     * when PayFast sends the same notification twice.
     */
    private static function confirmDonations(array $order): void
    {
        Database::update('donations', ['status' => 'paid'], 'order_id = :order', ['order' => (int) $order['id']]);

        foreach (self::items((int) $order['id']) as $item) {
            if ($item['item_type'] !== 'donation') {
                continue;
            }

            $exists = (int) Database::scalar(
                'SELECT COUNT(*) FROM donations WHERE order_item_id = ?',
                [(int) $item['id']]
            );

            if ($exists > 0) {
                continue;
            }

            $meta = CartService::decodeMeta($item['meta']);

            Database::insert('donations', [
                'reference'     => reference_code('DON'),
                'order_id'      => (int) $order['id'],
                'order_item_id' => (int) $item['id'],
                'user_id'       => $order['user_id'] === null ? null : (int) $order['user_id'],
                'donation_type' => $meta['donation_type'] ?? $item['description'],
                'name'          => ($meta['is_anonymous'] ?? false) ? null : trim(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? '')),
                'email'         => ($meta['is_anonymous'] ?? false) ? null : $order['email'],
                'amount_cents'  => (int) $item['total_cents'],
                'is_anonymous'  => (int) ($meta['is_anonymous'] ?? 0),
                'message'       => $meta['message'] ?? null,
                'status'        => 'paid',
            ]);
        }
    }

    /* --------------------------------------------------------- transitions */

    public static function markFailed(array $order, string $reason = ''): void
    {
        if (in_array($order['status'], ['paid', 'refunded'], true)) {
            return;
        }

        Database::update('orders', ['status' => 'failed'], 'id = :id', ['id' => (int) $order['id']]);
        self::releaseTransportSeats($order);
        self::log((int) $order['id'], 'order_failed', $reason !== '' ? $reason : 'Payment failed.');

        MailService::paymentFailed(self::find((int) $order['id']));
        MailService::adminFailedPayment(self::find((int) $order['id']), $reason);
    }

    public static function markCancelled(array $order, string $reason = 'Cancelled by the customer at PayFast.'): void
    {
        if (in_array($order['status'], ['paid', 'refunded'], true)) {
            return;
        }

        Database::update('orders', [
            'status'       => 'cancelled',
            'cancelled_at' => date('Y-m-d H:i:s'),
        ], 'id = :id', ['id' => (int) $order['id']]);

        self::releaseTransportSeats($order);
        self::log((int) $order['id'], 'order_cancelled', $reason);
    }

    public static function markRefunded(array $order, string $reason = ''): void
    {
        Database::update('orders', ['status' => 'refunded'], 'id = :id', ['id' => (int) $order['id']]);

        AccommodationService::cancelBookingsForOrder((int) $order['id'], 'refunded');
        Database::update('transport_bookings', ['status' => 'refunded'], 'order_id = :order', ['order' => (int) $order['id']]);
        Database::update('donations', ['status' => 'refunded'], 'order_id = :order', ['order' => (int) $order['id']]);

        self::releaseTransportSeats($order);
        self::log((int) $order['id'], 'order_refunded', $reason !== '' ? $reason : 'Refunded by an administrator.');
    }

    /* ------------------------------------------------------------ queries */

    public static function find(int $id): ?array
    {
        return Database::first('SELECT * FROM orders WHERE id = ? LIMIT 1', [$id]);
    }

    public static function findByReference(string $reference): ?array
    {
        return Database::first('SELECT * FROM orders WHERE reference = ? LIMIT 1', [$reference]);
    }

    public static function items(int $orderId): array
    {
        return Database::select(
            'SELECT * FROM order_items WHERE order_id = ?
          ORDER BY FIELD(item_type, "registration","accommodation","transport","merchandise","donation"), id',
            [$orderId]
        );
    }

    public static function forUser(int $userId): array
    {
        return Database::select(
            'SELECT o.*, (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS item_count
               FROM orders o WHERE o.user_id = ? ORDER BY o.created_at DESC',
            [$userId]
        );
    }

    public static function uniqueReference(): string
    {
        do {
            $reference = reference_code('SAR');
            $exists    = (int) Database::scalar('SELECT COUNT(*) FROM orders WHERE reference = ?', [$reference]);
        } while ($exists > 0);

        return $reference;
    }

    public static function log(int $orderId, string $event, string $message, mixed $payload = null): void
    {
        Database::insert('payment_logs', [
            'order_id'  => $orderId,
            'event'     => $event,
            'message'   => mb_substr($message, 0, 500),
            'payload'   => $payload === null ? null : json_encode($payload, JSON_UNESCAPED_UNICODE),
            'source_ip' => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
        ]);
    }

    /** Release stale pending orders so their seats go back on sale. */
    public static function expireStalePendingOrders(int $olderThanMinutes = 120): int
    {
        $stale = Database::select(
            'SELECT * FROM orders WHERE status = "pending_payment" AND created_at < DATE_SUB(NOW(), INTERVAL ? MINUTE)',
            [$olderThanMinutes]
        );

        foreach ($stale as $order) {
            self::markCancelled($order, 'Automatically cancelled: payment was never completed.');
        }

        return count($stale);
    }
}

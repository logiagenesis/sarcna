<?php
declare(strict_types=1);

/**
 * Demo order generator.
 *
 *   php tools/seed-demo-orders.php            # place 40 demo orders
 *   php tools/seed-demo-orders.php 120        # place 120
 *   php tools/seed-demo-orders.php --purge    # remove every demo order again
 *
 * Places realistic orders through the site's own services — the same cart, the
 * same bed holds, the same fulfilment path a real delegate goes through — so the
 * finance and booking screens have something honest to show before registration
 * opens. Every order it creates is flagged `is_mock = 1` and can be removed in
 * one command, so demo money can never be mistaken for real money.
 *
 * DO NOT run this on a live site once real bookings exist.
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;
use App\Services\AccommodationService;
use App\Services\CartService;
use App\Services\OrderService;

if (PHP_SAPI !== 'cli') {
    exit("This script is for the command line only.\n");
}

$_SESSION = [];
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

/* ------------------------------------------------------------------ purge */

if (in_array('--purge', $argv, true)) {
    $ids = Database::select('SELECT id, cart_token FROM orders WHERE is_mock = 1');

    if ($ids === []) {
        exit("No demo orders to remove.\n");
    }

    $orderIds = array_map(static fn (array $r): int => (int) $r['id'], $ids);
    $list     = implode(',', $orderIds);

    // Put stock back before the order items disappear.
    foreach (Database::select("SELECT product_id, variant_id, quantity FROM order_items
                                WHERE order_id IN ({$list}) AND item_type IN ('merchandise','registration')
                                  AND product_id IS NOT NULL") as $item) {
        if ($item['variant_id'] !== null) {
            Database::run('UPDATE product_variants SET stock = stock + ? WHERE id = ?', [(int) $item['quantity'], (int) $item['variant_id']]);
        }

        Database::run('UPDATE products SET stock = stock + ? WHERE id = ? AND track_stock = 1', [(int) $item['quantity'], (int) $item['product_id']]);
    }

    // Give the shuttle seats back.
    foreach (Database::select("SELECT transport_slot_id, quantity FROM order_items
                                WHERE order_id IN ({$list}) AND item_type = 'transport'
                                  AND transport_slot_id IS NOT NULL") as $item) {
        Database::run('UPDATE transport_slots SET seats_taken = GREATEST(0, seats_taken - ?) WHERE id = ?', [(int) $item['quantity'], (int) $item['transport_slot_id']]);
    }

    foreach (['refunds', 'payments', 'payment_logs', 'bookings', 'transport_bookings', 'donations',
              'inventory_movements', 'order_items'] as $table) {
        if (Database::scalar("SELECT COUNT(*) FROM information_schema.columns
                               WHERE table_schema = DATABASE() AND table_name = ? AND column_name = 'order_id'", [$table])) {
            Database::run("DELETE FROM {$table} WHERE order_id IN ({$list})");
        }
    }

    Database::run("DELETE FROM orders WHERE id IN ({$list})");

    foreach ($ids as $row) {
        if ($row['cart_token'] !== null) {
            Database::run('DELETE FROM booking_holds WHERE cart_token = ?', [$row['cart_token']]);
            Database::run('DELETE FROM cart_items WHERE cart_id IN (SELECT id FROM carts WHERE token = ?)', [$row['cart_token']]);
            Database::run('DELETE FROM carts WHERE token = ?', [$row['cart_token']]);
        }
    }

    Database::run("DELETE FROM users WHERE is_mock = 1 AND is_admin = 0");

    printf("Removed %d demo orders and everything attached to them.\n", count($orderIds));
    exit(0);
}

/* ---------------------------------------------------------------- catalogue */

$target = 40;

foreach ($argv as $arg) {
    if (ctype_digit((string) $arg)) {
        $target = max(1, min(500, (int) $arg));
    }
}

$registrations = Database::select("SELECT * FROM products WHERE type IN ('registration','day_pass') AND is_active = 1");
$merchandise   = Database::select("SELECT * FROM products WHERE type NOT IN ('registration','day_pass','donation','transport') AND is_active = 1");
$donationItems = Database::select("SELECT * FROM products WHERE type = 'donation' AND is_active = 1");
$roomTypes     = Database::select('SELECT * FROM room_types WHERE is_active = 1 ORDER BY sort_order');
$slots         = Database::select('SELECT s.*, r.name AS route_name, r.price_cents FROM transport_slots s JOIN transport_routes r ON r.id = s.route_id WHERE s.is_active = 1');
$nights        = AccommodationService::nights();

if ($registrations === []) {
    exit("No registration products found. Seed the demo data first.\n");
}

$firstNames = ['Thabo', 'Nadia', 'Sipho', 'Elmarie', 'Johan', 'Zanele', 'Riaan', 'Lerato', 'Faried', 'Anneke',
               'Mandla', 'Chantelle', 'Bongani', 'Marius', 'Precious', 'Deon', 'Nomsa', 'Willem', 'Kagiso', 'Ilse',
               'Pieter', 'Refilwe', 'Yusuf', 'Hendrik', 'Palesa', 'Andre', 'Tumi', 'Charmaine', 'Sibusiso', 'Marlene'];
$lastNames  = ['Mokoena', 'van Wyk', 'Dlamini', 'Botha', 'Ndlovu', 'Petersen', 'Nkosi', 'du Plessis', 'Adams', 'Khumalo',
               'Jacobs', 'Mabaso', 'Fourie', 'Sithole', 'Meyer', 'Molefe', 'Abrahams', 'Zulu', 'Steyn', 'Mthembu'];
$groups     = ['Sea Point Serenity', 'Bellville Freedom', 'Khayelitsha Hope', 'Stellenbosch Sunrise', 'Paarl New Way',
               'Somerset West Unity', 'Mitchells Plain Courage', 'George Living Clean', 'Worcester Just For Today'];

/** A weighted pick: index 0 is the most likely. */
function weighted(array $options): mixed
{
    $bag = [];

    foreach (array_values($options) as $i => $option) {
        for ($n = max(1, count($options) - $i); $n > 0; $n--) {
            $bag[] = $option;
        }
    }

    return $bag[array_rand($bag)];
}

$created  = 0;
$skipped  = 0;
$statuses = [];
$revenue  = 0;

printf("\nSeeding %d demo orders…\n\n", $target);

for ($i = 0; $i < $target; $i++) {
    $token = hash('sha256', 'demo-' . $i . '-' . microtime(true) . random_bytes(8));
    $_SESSION = ['cart_token' => $token];

    // The cart service keys off the session; give it a clean one per order.
    $cartId = Database::insert('carts', ['token' => $token]);

    $first = $firstNames[array_rand($firstNames)];
    $last  = $lastNames[array_rand($lastNames)];
    $email = strtolower(preg_replace('/[^a-z]/i', '', $first . '.' . $last)) . $i . '@example.invalid';

    $lines    = [];
    $subtotal = 0;

    $add = static function (array $line) use (&$lines, &$subtotal, $cartId): void {
        $line['cart_id']  = $cartId;
        $line['quantity'] = $line['quantity'] ?? 1;
        $line['meta']     = isset($line['meta']) ? json_encode($line['meta'], JSON_UNESCAPED_UNICODE) : null;

        $subtotal += (int) $line['unit_price_cents'] * (int) $line['quantity'];

        Database::insert('cart_items', $line + [
            'product_id' => null, 'variant_id' => null, 'bed_id' => null,
            'room_type_id' => null, 'night' => null, 'transport_slot_id' => null,
        ]);

        $lines[] = $line;
    };

    /* Registration — everybody buys one. */
    $registration = weighted($registrations);
    $add([
        'item_type'        => 'registration',
        'product_id'       => (int) $registration['id'],
        'description'      => $registration['name'],
        'unit_price_cents' => (int) $registration['price_cents'],
        'meta'             => [
            'product_type'   => $registration['type'],
            'attendee_name'  => $first . ' ' . $last,
            'attendee_email' => $email,
            'home_group'     => $groups[array_rand($groups)],
        ],
    ]);

    /* Accommodation — about two in three stay over. */
    $bedsHeld = 0;

    if ($roomTypes !== [] && $nights !== [] && random_int(1, 3) > 1) {
        $roomType   = weighted($roomTypes);
        $stay       = random_int(1, min(3, count($nights)));
        $startIndex = random_int(0, max(0, count($nights) - $stay));

        for ($n = 0; $n < $stay; $n++) {
            $night = $nights[$startIndex + $n] ?? null;

            if ($night === null) {
                continue;
            }

            $free = AccommodationService::freeBedIds((int) $roomType['id'], $night);

            if ($free === []) {
                continue;
            }

            $bedId = $free[array_rand($free)];
            $rate  = (int) AccommodationService::rateFor($roomType, $night)['bed'];

            try {
                AccommodationService::holdBed($token, (int) $bedId, $night, $rate);
            } catch (\RuntimeException) {
                continue;
            }

            $add([
                'item_type'        => 'accommodation',
                'bed_id'           => (int) $bedId,
                'room_type_id'     => (int) $roomType['id'],
                'night'            => $night,
                'description'      => sprintf('%s — 1 bed, %s', $roomType['name'], za_date($night, 'D j M Y')),
                'unit_price_cents' => $rate,
                'meta'             => [
                    'bed_ids'    => [(int) $bedId],
                    'bed_count'  => 1,
                    'room_type'  => $roomType['name'],
                    'guest_name' => $first . ' ' . $last,
                    'guest_email' => $email,
                ],
            ]);

            $bedsHeld++;
        }
    }

    /* Transport — about one in three takes the shuttle. */
    if ($slots !== [] && random_int(1, 3) === 1) {
        $slot  = $slots[array_rand($slots)];
        $seats = random_int(1, 2);

        $add([
            'item_type'         => 'transport',
            'transport_slot_id' => (int) $slot['id'],
            'description'       => sprintf('%s — %s', $slot['route_name'], za_date((string) $slot['departs_at'], 'D j M, H:i')),
            'unit_price_cents'  => (int) $slot['price_cents'],
            'quantity'          => $seats,
            'meta'              => [
                'route_name'     => $slot['route_name'],
                'departs_at'     => $slot['departs_at'],
                'passenger_name' => $first . ' ' . $last,
                'email'          => $email,
                'phone'          => '08' . random_int(20000000, 39999999),
                'luggage_count'  => 1,
            ],
        ]);
    }

    /* Merchandise — about half buy something. */
    if ($merchandise !== [] && random_int(1, 2) === 1) {
        $product = $merchandise[array_rand($merchandise)];
        $variant = Database::first('SELECT * FROM product_variants WHERE product_id = ? AND stock > 0 ORDER BY RAND() LIMIT 1', [(int) $product['id']]);

        $descriptor = $variant === null ? '' : trim(implode(' / ', array_filter([$variant['size'] ?? '', $variant['colour'] ?? ''])));

        $add([
            'item_type'        => 'merchandise',
            'product_id'       => (int) $product['id'],
            'variant_id'       => $variant === null ? null : (int) $variant['id'],
            'description'      => $product['name'] . ($descriptor === '' ? '' : ' (' . $descriptor . ')'),
            'unit_price_cents' => (int) ($variant['price_cents'] ?? $product['price_cents']),
            'quantity'         => random_int(1, 2),
            'meta'             => ['product_type' => $product['type'], 'sku' => $variant['sku'] ?? $product['sku']],
        ]);
    }

    /* Donation — about one in five adds one. */
    if ($donationItems !== [] && random_int(1, 5) === 1) {
        $donation = $donationItems[array_rand($donationItems)];
        $amount   = [5000, 10000, 15000, 25000, 50000][array_rand([0, 1, 2, 3, 4])];

        $add([
            'item_type'        => 'donation',
            'product_id'       => (int) $donation['id'],
            'description'      => $donation['name'],
            'unit_price_cents' => $amount,
            'meta'             => [
                'product_type'  => 'donation',
                'donation_type' => $donation['name'],
                'is_anonymous'  => random_int(1, 4) === 1 ? 1 : 0,
            ],
        ]);
    }

    /* -------------------------------------------------------- place it */

    // Backdate: registrations arrive over the months before the convention.
    // A few orders are placed "just now" so some of them can still be sitting in
    // the pipeline — the site expires anything pending for more than two hours,
    // so a backdated pending order would vanish before anyone saw it.
    $roll     = random_int(1, 100);
    $isRecent = $roll > 96;
    $daysAgo  = random_int(0, 150);
    $placedAt = $isRecent
        ? date('Y-m-d H:i:s', time() - random_int(120, 3000))
        : date('Y-m-d H:i:s', strtotime("-{$daysAgo} days -" . random_int(0, 20) . ' hours'));

    $reference = OrderService::uniqueReference();

    $orderId = Database::insert('orders', [
        'reference'         => $reference,
        'cart_token'        => $token,
        'email'             => $email,
        'first_name'        => $first,
        'last_name'         => $last,
        'phone'             => '08' . random_int(20000000, 39999999),
        'status'            => 'pending_payment',
        'subtotal_cents'    => $subtotal,
        'discount_cents'    => 0,
        'total_cents'       => $subtotal,
        'terms_accepted_at' => $placedAt,
        'checkin_code'      => reference_code('CHK'),
        'ip'                => '127.0.0.1',
        'is_mock'           => 1,
        'created_at'        => $placedAt,
    ]);

    foreach ($lines as $line) {
        Database::insert('order_items', [
            'order_id'          => $orderId,
            'item_type'         => $line['item_type'],
            'product_id'        => $line['product_id'] ?? null,
            'variant_id'        => $line['variant_id'] ?? null,
            'bed_id'            => $line['bed_id'] ?? null,
            'room_type_id'      => $line['room_type_id'] ?? null,
            'night'             => $line['night'] ?? null,
            'transport_slot_id' => $line['transport_slot_id'] ?? null,
            'description'       => $line['description'],
            'unit_price_cents'  => (int) $line['unit_price_cents'],
            'quantity'          => (int) $line['quantity'],
            'total_cents'       => (int) $line['unit_price_cents'] * (int) $line['quantity'],
            'meta'              => $line['meta'],
        ]);
    }

    $order = OrderService::find($orderId);

    // Most orders get paid. A few are abandoned or fail, which is what a real
    // report looks like and what the pipeline tiles are there to show.
    if ($roll <= 88) {
        OrderService::reserveTransportSeats($order);
        OrderService::markPaid($order, ['cart_token' => $token]);

        $paidAt = date('Y-m-d H:i:s', strtotime($placedAt) + random_int(60, 900));

        Database::run('UPDATE orders SET paid_at = ?, updated_at = ? WHERE id = ?', [$paidAt, $paidAt, $orderId]);

        // The payment record PayFast would have left behind.
        $gross = $subtotal;
        $fee   = (int) round($gross * 0.035) + 200;

        Database::insert('payments', [
            'order_id'           => $orderId,
            'provider'           => 'payfast',
            'provider_reference' => (string) random_int(1000000, 9999999),
            'amount_cents'       => $gross,
            'fee_cents'          => random_int(1, 10) === 1 ? 0 : $fee,
            'status'             => 'complete',
            'signature_valid'    => 1,
            'source_ip'          => '197.97.145.' . random_int(2, 250),
            'created_at'         => $paidAt,
        ]);

        Database::run('UPDATE bookings SET created_at = ? WHERE order_id = ?', [$paidAt, $orderId]);

        $revenue += $gross;
        $statuses['paid'] = ($statuses['paid'] ?? 0) + 1;
    } elseif ($roll <= 96) {
        AccommodationService::releaseCartHolds($token);
        Database::run('UPDATE orders SET status = "cancelled", cancelled_at = ? WHERE id = ?', [$placedAt, $orderId]);
        $statuses['cancelled'] = ($statuses['cancelled'] ?? 0) + 1;
    } else {
        AccommodationService::releaseCartHolds($token);
        Database::run('UPDATE orders SET status = "pending_payment" WHERE id = ?', [$orderId]);

        Database::insert('payments', [
            'order_id'     => $orderId,
            'provider'     => 'payfast',
            'amount_cents' => $subtotal,
            'status'       => 'initiated',
            'created_at'   => $placedAt,
        ]);

        $statuses['pending'] = ($statuses['pending'] ?? 0) + 1;
    }

    $created++;

    if ($created % 10 === 0) {
        printf("  %d/%d placed…\n", $created, $target);
    }
}

/* ------------------------------------------------------------- refunds */

// A handful of cancellations after payment, so the refund ledger is not empty.
$refundable = Database::select(
    'SELECT * FROM orders WHERE is_mock = 1 AND status = "paid" ORDER BY RAND() LIMIT ?',
    [max(1, (int) round($created * 0.05))]
);

$reasons = [
    'Could not travel — family emergency',
    'Duplicate registration, paid twice',
    'Downgraded from full weekend to day pass',
    'Cancelled within the free-cancellation window',
];

$refunded = 0;

foreach ($refundable as $order) {
    $full   = random_int(1, 2) === 1;
    $amount = $full ? (int) $order['total_cents'] : (int) round(((int) $order['total_cents']) * 0.5);

    Database::insert('refunds', [
        'reference'          => reference_code('REF'),
        'order_id'           => (int) $order['id'],
        'payment_id'         => Database::scalar('SELECT id FROM payments WHERE order_id = ? AND status = "complete" LIMIT 1', [(int) $order['id']]),
        'amount_cents'       => $amount,
        'reason'             => $reasons[array_rand($reasons)],
        'category'           => $full ? 'mixed' : 'accommodation',
        'method'             => 'payfast',
        'provider_reference' => 'PF-' . random_int(100000, 999999),
        'status'             => 'completed',
        'refunded_on'        => date('Y-m-d', strtotime((string) $order['paid_at']) + 86400 * random_int(1, 14)),
        'created_at'         => date('Y-m-d H:i:s', strtotime((string) $order['paid_at']) + 86400 * random_int(1, 14)),
    ]);

    if ($full) {
        OrderService::markRefunded($order, 'Demo data: refunded in full.');
    }

    $refunded += $amount;
}

/* -------------------------------------------------------------- summary */

printf("\nDone.\n");
printf("  Orders placed        %d\n", $created);

foreach ($statuses as $status => $count) {
    printf("    %-18s %d\n", $status, $count);
}

printf("  Gross paid           %s\n", money($revenue));
printf("  Refunds recorded     %s across %d orders\n", money($refunded), count($refundable));
printf("  Beds now booked      %d\n", (int) Database::scalar('SELECT COUNT(*) FROM bookings WHERE status IN ("confirmed","checked_in")'));
printf("\nRemove all of it again with:  php tools/seed-demo-orders.php --purge\n\n");

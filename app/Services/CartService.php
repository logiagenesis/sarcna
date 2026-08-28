<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Session;

/**
 * One cart holds every kind of line: registration, beds, merchandise,
 * transport and donations. The cart is keyed by a token in the session so a
 * guest can shop before signing in, and it is merged onto the account at login.
 */
final class CartService
{
    private const SESSION_KEY = 'cart_token';

    public static function token(): string
    {
        $token = Session::get(self::SESSION_KEY);

        if (!is_string($token) || strlen($token) !== 64) {
            $token = hash('sha256', bin2hex(random_bytes(32)) . microtime(true));
            Session::put(self::SESSION_KEY, $token);
        }

        return $token;
    }

    public static function cart(bool $createIfMissing = true): ?array
    {
        $token = self::token();
        $cart  = Database::first('SELECT * FROM carts WHERE token = ? LIMIT 1', [$token]);

        if ($cart === null && $createIfMissing) {
            $id   = Database::insert('carts', ['token' => $token, 'user_id' => AuthService::id()]);
            $cart = Database::first('SELECT * FROM carts WHERE id = ?', [$id]);
        }

        if ($cart !== null && AuthService::id() !== null && $cart['user_id'] === null) {
            Database::update('carts', ['user_id' => AuthService::id()], 'id = :id', ['id' => $cart['id']]);
            $cart['user_id'] = AuthService::id();
        }

        return $cart;
    }

    public static function items(): array
    {
        $cart = self::cart(false);

        if ($cart === null) {
            return [];
        }

        AccommodationService::purgeExpiredHolds();
        self::dropOrphanedAccommodationItems((string) $cart['token'], (int) $cart['id']);

        $items = Database::select(
            'SELECT ci.*, p.slug AS product_slug, p.image AS product_image, p.type AS product_type
               FROM cart_items ci
          LEFT JOIN products p ON p.id = ci.product_id
              WHERE ci.cart_id = ?
           ORDER BY FIELD(ci.item_type, "registration","accommodation","transport","merchandise","donation"), ci.id',
            [(int) $cart['id']]
        );

        foreach ($items as &$item) {
            $item['meta']        = self::decodeMeta($item['meta']);
            $item['total_cents'] = (int) $item['unit_price_cents'] * (int) $item['quantity'];
        }

        return $items;
    }

    public static function count(): int
    {
        $cart = self::cart(false);

        if ($cart === null) {
            return 0;
        }

        return (int) Database::scalar('SELECT COALESCE(SUM(quantity), 0) FROM cart_items WHERE cart_id = ?', [(int) $cart['id']]);
    }

    /* -------------------------------------------------------------- writes */

    public static function add(array $line): int
    {
        $cart = self::cart();

        $data = [
            'cart_id'           => (int) $cart['id'],
            'item_type'         => $line['item_type'],
            'product_id'        => $line['product_id'] ?? null,
            'variant_id'        => $line['variant_id'] ?? null,
            'bed_id'            => $line['bed_id'] ?? null,
            'room_type_id'      => $line['room_type_id'] ?? null,
            'night'             => $line['night'] ?? null,
            'transport_slot_id' => $line['transport_slot_id'] ?? null,
            'description'       => $line['description'],
            'unit_price_cents'  => (int) $line['unit_price_cents'],
            'quantity'          => max(1, (int) ($line['quantity'] ?? 1)),
            'meta'              => isset($line['meta']) ? json_encode($line['meta'], JSON_UNESCAPED_UNICODE) : null,
        ];

        // Merchandise of the same product/variant stacks instead of repeating.
        if ($data['item_type'] === 'merchandise' && $data['product_id'] !== null) {
            $existing = Database::first(
                'SELECT * FROM cart_items
                  WHERE cart_id = ? AND product_id = ? AND item_type = "merchandise"
                    AND (variant_id <=> ?) LIMIT 1',
                [$data['cart_id'], $data['product_id'], $data['variant_id']]
            );

            if ($existing !== null) {
                Database::update(
                    'cart_items',
                    ['quantity' => (int) $existing['quantity'] + $data['quantity']],
                    'id = :id',
                    ['id' => $existing['id']]
                );

                self::touch();

                return (int) $existing['id'];
            }
        }

        $id = Database::insert('cart_items', $data);

        self::touch();

        return $id;
    }

    public static function updateQuantity(int $itemId, int $quantity): void
    {
        $item = self::findItem($itemId);

        if ($item === null) {
            return;
        }

        // Beds and transport seats are allocated individually — never multiplied.
        if (in_array($item['item_type'], ['accommodation'], true)) {
            return;
        }

        if ($quantity < 1) {
            self::remove($itemId);

            return;
        }

        Database::update('cart_items', ['quantity' => min(50, $quantity)], 'id = :id', ['id' => $itemId]);
        self::touch();
    }

    public static function remove(int $itemId): void
    {
        $item = self::findItem($itemId);

        if ($item === null) {
            return;
        }

        if ($item['item_type'] === 'accommodation') {
            $meta   = self::decodeMeta($item['meta']);
            $bedIds = $meta['bed_ids'] ?? array_filter([$item['bed_id']]);

            foreach ($bedIds as $bedId) {
                AccommodationService::releaseHold(self::token(), (int) $bedId, (string) $item['night']);
            }
        }

        Database::delete('cart_items', 'id = ?', [$itemId]);
        self::touch();
    }

    public static function clear(): void
    {
        $cart = self::cart(false);

        if ($cart === null) {
            return;
        }

        AccommodationService::releaseCartHolds((string) $cart['token']);
        Database::delete('cart_items', 'cart_id = ?', [(int) $cart['id']]);
        Database::update('carts', ['coupon_id' => null], 'id = :id', ['id' => (int) $cart['id']]);
    }

    /** Called after a successful order so the next visit starts fresh. */
    public static function reset(): void
    {
        $cart = self::cart(false);

        if ($cart !== null) {
            Database::delete('cart_items', 'cart_id = ?', [(int) $cart['id']]);
            Database::delete('carts', 'id = ?', [(int) $cart['id']]);
        }

        Session::forget(self::SESSION_KEY);
    }

    public static function findItem(int $itemId): ?array
    {
        $cart = self::cart(false);

        if ($cart === null) {
            return null;
        }

        return Database::first('SELECT * FROM cart_items WHERE id = ? AND cart_id = ? LIMIT 1', [$itemId, (int) $cart['id']]);
    }

    public static function hasType(string $type): bool
    {
        $cart = self::cart(false);

        if ($cart === null) {
            return false;
        }

        return (int) Database::scalar(
            'SELECT COUNT(*) FROM cart_items WHERE cart_id = ? AND item_type = ?',
            [(int) $cart['id'], $type]
        ) > 0;
    }

    private static function touch(): void
    {
        $cart = self::cart(false);

        if ($cart !== null) {
            Database::update('carts', ['updated_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => (int) $cart['id']]);
            AccommodationService::refreshHolds((string) $cart['token']);
        }
    }

    /**
     * If a hold expired while the visitor was away, the matching cart line has
     * to go too — otherwise checkout would sell a bed that is no longer held.
     */
    private static function dropOrphanedAccommodationItems(string $token, int $cartId): void
    {
        $items = Database::select(
            'SELECT id, bed_id, night FROM cart_items WHERE cart_id = ? AND item_type = "accommodation"',
            [$cartId]
        );

        foreach ($items as $item) {
            $stillHeld = (int) Database::scalar(
                'SELECT COUNT(*) FROM booking_holds WHERE cart_token = ? AND bed_id = ? AND night = ? AND expires_at > NOW()',
                [$token, (int) $item['bed_id'], (string) $item['night']]
            );

            if ($stillHeld === 0) {
                Database::delete('cart_items', 'id = ?', [(int) $item['id']]);
                Session::flash('warning', 'A held bed expired and was removed from your cart. Please re-select it.');
            }
        }
    }

    /* -------------------------------------------------------------- totals */

    public static function totals(): array
    {
        $items    = self::items();
        $subtotal = 0;
        $byType   = [];

        foreach ($items as $item) {
            $lineTotal = (int) $item['total_cents'];
            $subtotal += $lineTotal;
            $byType[$item['item_type']] = ($byType[$item['item_type']] ?? 0) + $lineTotal;
        }

        $coupon   = self::coupon();
        $discount = $coupon === null ? 0 : self::discountFor($coupon, $subtotal, $byType);

        return [
            'items'          => $items,
            'count'          => array_sum(array_map(static fn (array $i): int => (int) $i['quantity'], $items)),
            'subtotal_cents' => $subtotal,
            'discount_cents' => $discount,
            'total_cents'    => max(0, $subtotal - $discount),
            'by_type'        => $byType,
            'coupon'         => $coupon,
        ];
    }

    public static function coupon(): ?array
    {
        $cart = self::cart(false);

        if ($cart === null || $cart['coupon_id'] === null) {
            return null;
        }

        $coupon = Database::first('SELECT * FROM coupons WHERE id = ? AND is_active = 1 LIMIT 1', [(int) $cart['coupon_id']]);

        if ($coupon === null || !self::couponIsValid($coupon)) {
            Database::update('carts', ['coupon_id' => null], 'id = :id', ['id' => (int) $cart['id']]);

            return null;
        }

        return $coupon;
    }

    public static function applyCoupon(string $code): array
    {
        $coupon = Database::first('SELECT * FROM coupons WHERE code = ? LIMIT 1', [strtoupper(trim($code))]);

        if ($coupon === null || (int) $coupon['is_active'] !== 1 || !self::couponIsValid($coupon)) {
            return ['ok' => false, 'message' => 'That coupon code is not valid.'];
        }

        $totals = self::totals();

        if ((int) $coupon['min_subtotal_cents'] > $totals['subtotal_cents']) {
            return [
                'ok'      => false,
                'message' => 'This coupon needs a minimum order of ' . money((int) $coupon['min_subtotal_cents']) . '.',
            ];
        }

        $cart = self::cart();
        Database::update('carts', ['coupon_id' => (int) $coupon['id']], 'id = :id', ['id' => (int) $cart['id']]);

        return ['ok' => true, 'message' => 'Coupon ' . $coupon['code'] . ' applied.'];
    }

    public static function removeCoupon(): void
    {
        $cart = self::cart(false);

        if ($cart !== null) {
            Database::update('carts', ['coupon_id' => null], 'id = :id', ['id' => (int) $cart['id']]);
        }
    }

    private static function couponIsValid(array $coupon): bool
    {
        $now = time();

        if ($coupon['starts_at'] !== null && strtotime((string) $coupon['starts_at']) > $now) {
            return false;
        }

        if ($coupon['ends_at'] !== null && strtotime((string) $coupon['ends_at']) < $now) {
            return false;
        }

        if ($coupon['max_uses'] !== null && (int) $coupon['used_count'] >= (int) $coupon['max_uses']) {
            return false;
        }

        return true;
    }

    public static function discountFor(array $coupon, int $subtotal, array $byType): int
    {
        $base = $coupon['applies_to'] === 'all'
            ? $subtotal
            : (int) ($byType[$coupon['applies_to']] ?? 0);

        if ($base <= 0) {
            return 0;
        }

        $discount = $coupon['discount_type'] === 'percent'
            ? (int) round($base * ((int) $coupon['discount_value'] / 100))
            : (int) $coupon['discount_value'];

        return min($discount, $subtotal);
    }

    public static function decodeMeta(mixed $meta): array
    {
        if (is_array($meta)) {
            return $meta;
        }

        if (!is_string($meta) || trim($meta) === '') {
            return [];
        }

        $decoded = json_decode($meta, true);

        return is_array($decoded) ? $decoded : [];
    }

    /** Move a guest cart onto the account after login. */
    public static function attachToUser(int $userId): void
    {
        $cart = self::cart(false);

        if ($cart !== null) {
            Database::update('carts', ['user_id' => $userId], 'id = :id', ['id' => (int) $cart['id']]);
            Database::update('booking_holds', ['user_id' => $userId], 'cart_token = :token', ['token' => (string) $cart['token']]);
        }
    }
}

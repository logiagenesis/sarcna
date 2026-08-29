<?php
declare(strict_types=1);

/**
 * Shared purge routine.
 *
 * Two tools create real orders in a real database — tools/seed-demo-orders.php
 * and tools/audit.php — and both have to be able to take them out again
 * leaving the books exactly as they found them. Removing an order is not a
 * single DELETE: fulfilment has already moved stock and taken shuttle seats,
 * and half a dozen tables point back at the order. Get one of them wrong and
 * the finance screens quietly stop agreeing with the orders table.
 *
 * There is therefore one implementation of it, here, and both tools call it.
 *
 * This file defines functions only. It is never executed on its own.
 */

use App\Core\Database;

if (!function_exists('purge_orders')) {
    /**
     * Remove a set of orders and everything fulfilment did on their behalf.
     *
     * Stock and shuttle seats are given back BEFORE the order items are
     * deleted, because the items are the only record of how many to give back.
     *
     * @param int[] $orderIds
     * @return int the number of orders removed
     */
    function purge_orders(array $orderIds): int
    {
        $orderIds = array_values(array_unique(array_map('intval', $orderIds)));

        if ($orderIds === []) {
            return 0;
        }

        $list = implode(',', $orderIds);

        // Put stock back first — the order items are the only record of it.
        foreach (Database::select("SELECT product_id, variant_id, quantity FROM order_items
                                    WHERE order_id IN ({$list}) AND item_type IN ('merchandise','registration')
                                      AND product_id IS NOT NULL") as $item) {
            if ($item['variant_id'] !== null) {
                Database::run(
                    'UPDATE product_variants SET stock = stock + ? WHERE id = ?',
                    [(int) $item['quantity'], (int) $item['variant_id']]
                );
            }

            Database::run(
                'UPDATE products SET stock = stock + ? WHERE id = ? AND track_stock = 1',
                [(int) $item['quantity'], (int) $item['product_id']]
            );
        }

        // Give the shuttle seats back.
        foreach (Database::select("SELECT transport_slot_id, quantity FROM order_items
                                    WHERE order_id IN ({$list}) AND item_type = 'transport'
                                      AND transport_slot_id IS NOT NULL") as $item) {
            Database::run(
                'UPDATE transport_slots SET seats_taken = GREATEST(0, seats_taken - ?) WHERE id = ?',
                [(int) $item['quantity'], (int) $item['transport_slot_id']]
            );
        }

        // Anything carrying an order_id. Checked against the schema rather than
        // assumed, so a new table added later cannot silently be missed here
        // without also being missed by the leftover count below.
        foreach (purge_order_child_tables() as $table) {
            Database::run("DELETE FROM {$table} WHERE order_id IN ({$list})");
        }

        // The carts and holds those orders came from.
        foreach (Database::select("SELECT cart_token FROM orders WHERE id IN ({$list}) AND cart_token IS NOT NULL") as $row) {
            Database::run('DELETE FROM booking_holds WHERE cart_token = ?', [$row['cart_token']]);
            Database::run('DELETE FROM cart_items WHERE cart_id IN (SELECT id FROM carts WHERE token = ?)', [$row['cart_token']]);
            Database::run('DELETE FROM carts WHERE token = ?', [$row['cart_token']]);
        }

        Database::run("DELETE FROM orders WHERE id IN ({$list})");

        return count($orderIds);
    }
}

if (!function_exists('purge_order_child_tables')) {
    /**
     * Every table that points back at an order, discovered from the schema.
     *
     * @return string[]
     */
    function purge_order_child_tables(): array
    {
        $rows = Database::select(
            "SELECT table_name AS t FROM information_schema.columns
              WHERE table_schema = DATABASE() AND column_name = 'order_id'
                AND table_name <> 'orders'
              ORDER BY table_name"
        );

        return array_map(static fn (array $r): string => (string) $r['t'], $rows);
    }
}

if (!function_exists('purge_users')) {
    /**
     * Remove user accounts and every order attached to them.
     *
     * @param string $emailPattern a SQL LIKE pattern, e.g. 'audit-%@example.invalid'
     * @return array{users:int,orders:int} what was removed
     */
    function purge_users(string $emailPattern): array
    {
        $userIds = array_map(
            static fn (array $r): int => (int) $r['id'],
            Database::select('SELECT id FROM users WHERE email LIKE ? AND is_admin = 0', [$emailPattern])
        );

        if ($userIds === []) {
            // The account may already be gone while its orders are not — the
            // orders are matched on their own stored email as well.
            $orderIds = array_map(
                static fn (array $r): int => (int) $r['id'],
                Database::select('SELECT id FROM orders WHERE email LIKE ?', [$emailPattern])
            );

            return ['users' => 0, 'orders' => purge_orders($orderIds)];
        }

        $list = implode(',', $userIds);

        $orderIds = array_map(
            static fn (array $r): int => (int) $r['id'],
            Database::select("SELECT id FROM orders WHERE user_id IN ({$list}) OR email LIKE ?", [$emailPattern])
        );

        $removed = purge_orders($orderIds);

        // Bookings and holds made by the account but never paid for have no
        // order to hang from, so they are cleared by user instead.
        Database::run("DELETE FROM bookings WHERE user_id IN ({$list})");
        Database::run("DELETE FROM transport_bookings WHERE user_id IN ({$list})");
        Database::run("DELETE FROM cart_items WHERE cart_id IN (SELECT id FROM carts WHERE user_id IN ({$list}))");
        Database::run("DELETE FROM carts WHERE user_id IN ({$list})");
        Database::run("DELETE FROM user_roles WHERE user_id IN ({$list})");
        Database::run("DELETE FROM email_verifications WHERE user_id IN ({$list})");
        Database::run("DELETE FROM password_resets WHERE user_id IN ({$list})");
        Database::run("DELETE FROM users WHERE id IN ({$list})");

        return ['users' => count($userIds), 'orders' => $removed];
    }
}

if (!function_exists('purge_users_leftovers')) {
    /**
     * Count anything still referring to those accounts after a purge.
     *
     * Deliberately counts by joining back through the schema rather than
     * trusting purge_users() to have been complete — a cleanup check that only
     * looks where the cleanup already looked proves nothing.
     */
    function purge_users_leftovers(string $emailPattern): int
    {
        $left = (int) Database::scalar('SELECT COUNT(*) FROM users WHERE email LIKE ?', [$emailPattern]);
        $left += (int) Database::scalar('SELECT COUNT(*) FROM orders WHERE email LIKE ?', [$emailPattern]);

        $left += (int) Database::scalar(
            'SELECT COUNT(*) FROM bookings b LEFT JOIN orders o ON o.id = b.order_id
              WHERE b.guest_email LIKE ? OR o.email LIKE ?',
            [$emailPattern, $emailPattern]
        );

        $left += (int) Database::scalar(
            'SELECT COUNT(*) FROM transport_bookings t LEFT JOIN orders o ON o.id = t.order_id
              WHERE t.email LIKE ? OR o.email LIKE ?',
            [$emailPattern, $emailPattern]
        );

        foreach (purge_order_child_tables() as $table) {
            $left += (int) Database::scalar(
                "SELECT COUNT(*) FROM {$table} c JOIN orders o ON o.id = c.order_id WHERE o.email LIKE ?",
                [$emailPattern]
            );
        }

        return $left;
    }
}

<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class ProductService
{
    public static function categories(): array
    {
        return Database::select('SELECT * FROM product_categories WHERE is_active = 1 ORDER BY sort_order, id');
    }

    public static function all(array $filters = []): array
    {
        $sql    = 'SELECT p.*, c.name AS category_name, c.slug AS category_slug
                     FROM products p
                LEFT JOIN product_categories c ON c.id = p.category_id
                    WHERE 1 = 1';
        $params = [];

        if (($filters['active'] ?? true) === true) {
            $sql .= ' AND p.is_active = 1';
        }

        if (isset($filters['type'])) {
            $types = (array) $filters['type'];
            $sql  .= ' AND p.type IN (' . implode(',', array_fill(0, count($types), '?')) . ')';
            $params = array_merge($params, $types);
        }

        if (isset($filters['category_slug'])) {
            $sql     .= ' AND c.slug = ?';
            $params[] = $filters['category_slug'];
        }

        if (isset($filters['featured'])) {
            $sql .= ' AND p.is_featured = 1';
        }

        if (isset($filters['search']) && trim((string) $filters['search']) !== '') {
            $sql     .= ' AND (p.name LIKE ? OR p.short_description LIKE ?)';
            $like     = '%' . trim((string) $filters['search']) . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $sql .= ' ORDER BY p.sort_order, p.name';

        if (isset($filters['limit'])) {
            $sql .= ' LIMIT ' . (int) $filters['limit'];
        }

        return Database::select($sql, $params);
    }

    public static function find(int|string $identifier): ?array
    {
        $column = is_int($identifier) || ctype_digit((string) $identifier) ? 'id' : 'slug';

        return Database::first(
            "SELECT p.*, c.name AS category_name, c.slug AS category_slug
               FROM products p
          LEFT JOIN product_categories c ON c.id = p.category_id
              WHERE p.{$column} = ? LIMIT 1",
            [$identifier]
        );
    }

    public static function variants(int $productId, bool $activeOnly = true): array
    {
        $sql = 'SELECT * FROM product_variants WHERE product_id = ?';

        if ($activeOnly) {
            $sql .= ' AND is_active = 1';
        }

        return Database::select($sql . ' ORDER BY sort_order, id', [$productId]);
    }

    public static function images(int $productId): array
    {
        return Database::select('SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order, id', [$productId]);
    }

    /** Current price in cents, honouring a live sale price. */
    public static function priceFor(array $product, ?array $variant = null): int
    {
        $price = (int) $product['price_cents'];

        $saleActive = $product['sale_price_cents'] !== null
            && (int) $product['sale_price_cents'] > 0
            && ($product['sale_ends_at'] === null || strtotime((string) $product['sale_ends_at']) >= time());

        if ($saleActive) {
            $price = (int) $product['sale_price_cents'];
        }

        if ($variant !== null) {
            $price += (int) $variant['price_delta_cents'];
        }

        return max(0, $price);
    }

    public static function isOnSale(array $product): bool
    {
        return $product['sale_price_cents'] !== null
            && (int) $product['sale_price_cents'] > 0
            && (int) $product['sale_price_cents'] < (int) $product['price_cents']
            && ($product['sale_ends_at'] === null || strtotime((string) $product['sale_ends_at']) >= time());
    }

    public static function stockFor(array $product, ?array $variant = null): int
    {
        if ((int) $product['track_stock'] === 0) {
            return PHP_INT_MAX;
        }

        return $variant !== null ? (int) $variant['stock'] : (int) $product['stock'];
    }

    public static function inStock(array $product, ?array $variant = null): bool
    {
        return self::stockFor($product, $variant) > 0;
    }

    /** Reduce stock atomically after payment; returns false if it would go negative. */
    public static function decrementStock(int $productId, ?int $variantId, int $quantity, ?int $orderId = null): bool
    {
        $product = Database::first('SELECT * FROM products WHERE id = ?', [$productId]);

        if ($product === null || (int) $product['track_stock'] === 0) {
            return true;
        }

        if ($variantId !== null) {
            $affected = Database::run(
                'UPDATE product_variants SET stock = stock - :qty WHERE id = :id AND stock >= :qty2',
                ['qty' => $quantity, 'id' => $variantId, 'qty2' => $quantity]
            )->rowCount();
        } else {
            $affected = Database::run(
                'UPDATE products SET stock = stock - :qty WHERE id = :id AND stock >= :qty2',
                ['qty' => $quantity, 'id' => $productId, 'qty2' => $quantity]
            )->rowCount();
        }

        Database::insert('inventory_movements', [
            'product_id' => $productId,
            'variant_id' => $variantId,
            'change'     => $affected === 1 ? -$quantity : 0,
            'reason'     => $affected === 1 ? 'Order paid' : 'Order paid — insufficient stock, needs attention',
            'order_id'   => $orderId,
            'user_id'    => AuthService::id(),
        ]);

        return $affected === 1;
    }

    public static function lowStock(int $limit = 10): array
    {
        return Database::select(
            'SELECT * FROM products
              WHERE is_active = 1 AND track_stock = 1 AND stock <= low_stock_threshold
           ORDER BY stock ASC LIMIT ' . (int) $limit
        );
    }

    public static function adjustStock(int $productId, ?int $variantId, int $change, string $reason): void
    {
        if ($variantId !== null) {
            Database::run('UPDATE product_variants SET stock = GREATEST(0, stock + :change) WHERE id = :id', ['change' => $change, 'id' => $variantId]);
        } else {
            Database::run('UPDATE products SET stock = GREATEST(0, stock + :change) WHERE id = :id', ['change' => $change, 'id' => $productId]);
        }

        Database::insert('inventory_movements', [
            'product_id' => $productId,
            'variant_id' => $variantId,
            'change'     => $change,
            'reason'     => $reason,
            'user_id'    => AuthService::id(),
        ]);
    }
}

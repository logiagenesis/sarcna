<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Validator;
use App\Services\ProductService;

final class ProductController extends AdminController
{
    public function index(): string
    {
        $type   = (string) $this->request->input('type', '');
        $search = trim((string) $this->request->input('q', ''));

        $where  = ['1 = 1'];
        $params = [];

        if ($type !== '') {
            $where[]        = 'p.type = :type';
            $params['type'] = $type;
        }

        if ($search !== '') {
            $where[]          = '(p.name LIKE :search OR p.sku LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        $clause = implode(' AND ', $where);

        $result = $this->paginate(
            "SELECT COUNT(*) FROM products p WHERE {$clause}",
            "SELECT p.*, c.name AS category_name,
                    (SELECT COUNT(*) FROM product_variants v WHERE v.product_id = p.id) AS variant_count,
                    (SELECT COALESCE(SUM(oi.quantity), 0) FROM order_items oi JOIN orders o ON o.id = oi.order_id
                      WHERE oi.product_id = p.id AND o.status = 'paid') AS sold
               FROM products p LEFT JOIN product_categories c ON c.id = p.category_id
              WHERE {$clause} ORDER BY p.type, p.sort_order, p.name",
            $params,
            50
        );

        return $this->render('admin.products', 'Products & stock', [
            'result'   => $result,
            'type'     => $type,
            'search'   => $search,
            'lowStock' => ProductService::lowStock(10),
        ]);
    }

    public function create(): string
    {
        return $this->render('admin.product-edit', 'New product', [
            'product'    => null,
            'variants'   => [],
            'categories' => ProductService::categories(),
        ]);
    }

    public function edit(string $id): string
    {
        $product = ProductService::find((int) $id);

        if ($product === null) {
            $this->abort(404);
        }

        return $this->render('admin.product-edit', $product['name'], [
            'product'    => $product,
            'variants'   => ProductService::variants((int) $product['id'], false),
            'categories' => ProductService::categories(),
            'movements'  => Database::select('SELECT * FROM inventory_movements WHERE product_id = ? ORDER BY created_at DESC LIMIT 20', [(int) $product['id']]),
            'sold'       => (int) Database::scalar(
                'SELECT COALESCE(SUM(oi.quantity), 0) FROM order_items oi JOIN orders o ON o.id = oi.order_id
                  WHERE oi.product_id = ? AND o.status = "paid"',
                [(int) $product['id']]
            ),
        ]);
    }

    public function store(): never
    {
        $data         = $this->validated();
        $data['slug'] = $this->uniqueSlug($data['slug'] ?: $data['name']);

        $image = $this->storeImage('image_file', 'products');
        if ($image !== null) {
            $data['image'] = $image;
        }

        $id = Database::insert('products', $data);

        $this->audit('created a product', 'product', $id, ['name' => $data['name']]);
        $this->flashSuccess('Product created.');
        $this->redirect(url('/admin/products/' . $id));
    }

    public function update(string $id): never
    {
        $product = ProductService::find((int) $id);

        if ($product === null) {
            $this->abort(404);
        }

        $data         = $this->validated();
        $data['slug'] = $this->uniqueSlug($data['slug'] ?: $data['name'], (int) $product['id']);

        $image = $this->storeImage('image_file', 'products');
        if ($image !== null) {
            $data['image'] = $image;
        }

        Database::update('products', $data, 'id = :id', ['id' => (int) $product['id']]);

        $this->audit('updated a product', 'product', (int) $product['id']);
        $this->flashSuccess('Product saved.');
        $this->back(url('/admin/products/' . $product['id']));
    }

    public function destroy(string $id): never
    {
        $sold = (int) Database::scalar('SELECT COUNT(*) FROM order_items WHERE product_id = ?', [(int) $id]);

        if ($sold > 0) {
            Database::update('products', ['is_active' => 0], 'id = :id', ['id' => (int) $id]);

            $this->flashSuccess('That product has been sold before, so it was deactivated rather than deleted. Order history stays intact.');
            $this->redirect(url('/admin/products'));
        }

        Database::delete('products', 'id = ?', [(int) $id]);

        $this->audit('deleted a product', 'product', (int) $id);
        $this->flashSuccess('Product deleted.');
        $this->redirect(url('/admin/products'));
    }

    public function saveVariant(string $id): never
    {
        $product = ProductService::find((int) $id);

        if ($product === null) {
            $this->abort(404);
        }

        $variantId = $this->request->int('variant_id', 0);

        $data = [
            'product_id'        => (int) $product['id'],
            'size'              => (string) $this->request->input('size', ''),
            'colour'            => (string) $this->request->input('colour', ''),
            'sku'               => (string) $this->request->input('sku', ''),
            'price_delta_cents' => rands($this->request->input('price_delta', 0)),
            'stock'             => $this->request->int('stock', 0),
            'is_active'         => $this->request->bool('is_active') ? 1 : 0,
            'sort_order'        => $this->request->int('sort_order', 0),
        ];

        if ($variantId > 0) {
            Database::update('product_variants', $data, 'id = :id AND product_id = :product', [
                'id' => $variantId, 'product' => (int) $product['id'],
            ]);
            $this->flashSuccess('Variant saved.');
        } else {
            Database::insert('product_variants', $data);
            $this->flashSuccess('Variant added.');
        }

        $this->audit('saved a product variant', 'product', (int) $product['id']);
        $this->back(url('/admin/products/' . $product['id']));
    }

    public function deleteVariant(string $id, string $variantId): never
    {
        Database::delete('product_variants', 'id = ? AND product_id = ?', [(int) $variantId, (int) $id]);

        $this->audit('deleted a product variant', 'product', (int) $id);
        $this->flashSuccess('Variant removed.');
        $this->back(url('/admin/products/' . $id));
    }

    public function adjustStock(string $id): never
    {
        $product = ProductService::find((int) $id);

        if ($product === null) {
            $this->abort(404);
        }

        $change    = $this->request->int('change', 0);
        $variantId = $this->request->int('variant_id', 0) ?: null;
        $reason    = (string) $this->request->input('reason', 'Manual stock adjustment');

        if ($change === 0) {
            $this->flashError('Enter a positive or negative number to adjust stock.');
            $this->back();
        }

        ProductService::adjustStock((int) $product['id'], $variantId, $change, $reason);

        $this->audit('adjusted stock by ' . $change, 'product', (int) $product['id'], ['reason' => $reason]);
        $this->flashSuccess('Stock adjusted by ' . $change . '.');
        $this->back(url('/admin/products/' . $product['id']));
    }

    private function validated(): array
    {
        $validator = Validator::make($this->request->all(), [
            'name'  => 'required|max:180',
            'type'  => 'required|in:registration,day_pass,merchandise,transport,donation,other',
            'price' => 'required|numeric|gte:0',
        ]);

        if ($validator->fails()) {
            $this->withErrors($validator->errors());
        }

        $salePrice = rands($this->request->input('sale_price', 0));

        return [
            'category_id'          => $this->request->int('category_id', 0) ?: null,
            'type'                 => (string) $this->request->input('type'),
            'name'                 => (string) $this->request->input('name'),
            'slug'                 => slugify((string) $this->request->input('slug', '')),
            'sku'                  => (string) $this->request->input('sku', ''),
            'short_description'    => (string) $this->request->input('short_description', ''),
            'description'          => (string) $this->request->input('description', ''),
            'price_cents'          => rands($this->request->input('price', 0)),
            'sale_price_cents'     => $salePrice > 0 ? $salePrice : null,
            'sale_ends_at'         => ($ends = (string) $this->request->input('sale_ends_at', '')) !== ''
                                        ? date('Y-m-d H:i:s', (int) strtotime($ends)) : null,
            'allows_custom_amount' => $this->request->bool('allows_custom_amount') ? 1 : 0,
            'min_amount_cents'     => rands($this->request->input('min_amount', 0)),
            'track_stock'          => $this->request->bool('track_stock') ? 1 : 0,
            'stock'                => $this->request->int('stock', 0),
            'low_stock_threshold'  => $this->request->int('low_stock_threshold', 5),
            'max_per_order'        => max(1, $this->request->int('max_per_order', 10)),
            'requires_attendee'    => $this->request->bool('requires_attendee') ? 1 : 0,
            'pickup_only'          => $this->request->bool('pickup_only') ? 1 : 0,
            'delivery_enabled'     => $this->request->bool('delivery_enabled') ? 1 : 0,
            'image'                => (string) $this->request->input('image', ''),
            'meta_title'           => (string) $this->request->input('meta_title', ''),
            'meta_description'     => (string) $this->request->input('meta_description', ''),
            'is_featured'          => $this->request->bool('is_featured') ? 1 : 0,
            'is_active'            => $this->request->bool('is_active') ? 1 : 0,
            'is_mock'              => $this->request->bool('is_mock') ? 1 : 0,
            'sort_order'           => $this->request->int('sort_order', 0),
        ];
    }

    private function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $slug = slugify($value);
        $base = $slug;
        $i    = 2;

        while (true) {
            $sql    = 'SELECT COUNT(*) FROM products WHERE slug = ?';
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

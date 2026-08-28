<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Validator;

final class CouponController extends AdminController
{
    public function index(): string
    {
        return $this->render('admin.coupons', 'Coupons', [
            'coupons' => Database::select('SELECT * FROM coupons ORDER BY is_active DESC, created_at DESC'),
        ]);
    }

    public function store(): never
    {
        $validator = Validator::make($this->request->all(), [
            'code'           => 'required|max:40',
            'discount_type'  => 'required|in:percent,fixed',
            'discount_value' => 'required|numeric|gt:0',
            'applies_to'     => 'required|in:all,registration,accommodation,merchandise,transport',
        ]);

        if ($validator->fails()) {
            $this->withErrors($validator->errors());
        }

        $code = strtoupper(preg_replace('/[^A-Za-z0-9\-]/', '', (string) $this->request->input('code')) ?? '');
        $type = (string) $this->request->input('discount_type');

        if ($code === '') {
            $this->flashError('Coupon codes may only contain letters, numbers and hyphens.');
            $this->back();
        }

        if ((int) Database::scalar('SELECT COUNT(*) FROM coupons WHERE code = ?', [$code]) > 0) {
            $this->flashError('That coupon code already exists.');
            $this->back();
        }

        $value = $type === 'percent'
            ? min(100, max(1, $this->request->int('discount_value')))
            : rands($this->request->input('discount_value'));

        $id = Database::insert('coupons', [
            'code'               => $code,
            'description'        => (string) $this->request->input('description', ''),
            'discount_type'      => $type,
            'discount_value'     => $value,
            'min_subtotal_cents' => rands($this->request->input('min_subtotal', 0)),
            'applies_to'         => (string) $this->request->input('applies_to'),
            'max_uses'           => $this->request->int('max_uses', 0) ?: null,
            'starts_at'          => ($s = (string) $this->request->input('starts_at', '')) !== '' ? date('Y-m-d H:i:s', (int) strtotime($s)) : null,
            'ends_at'            => ($e = (string) $this->request->input('ends_at', '')) !== '' ? date('Y-m-d H:i:s', (int) strtotime($e)) : null,
            'is_active'          => 1,
        ]);

        $this->audit('created a coupon', 'coupon', $id, ['code' => $code]);
        $this->flashSuccess('Coupon ' . $code . ' created.');
        $this->back(url('/admin/coupons'));
    }

    public function destroy(string $id): never
    {
        $coupon = Database::first('SELECT * FROM coupons WHERE id = ?', [(int) $id]);

        if ($coupon === null) {
            $this->abort(404);
        }

        if ((int) $coupon['used_count'] > 0) {
            Database::update('coupons', ['is_active' => 0], 'id = :id', ['id' => (int) $id]);
            $this->flashSuccess('That coupon has been used, so it was deactivated rather than deleted.');
        } else {
            Database::delete('coupons', 'id = ?', [(int) $id]);
            $this->flashSuccess('Coupon deleted.');
        }

        $this->audit('removed a coupon', 'coupon', (int) $id);
        $this->back(url('/admin/coupons'));
    }
}

<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;

final class DonationController extends AdminController
{
    public function index(): string
    {
        $status = (string) $this->request->input('status', 'paid');

        $where  = $status === '' ? '1 = 1' : 'd.status = :status';
        $params = $status === '' ? [] : ['status' => $status];

        $result = $this->paginate(
            "SELECT COUNT(*) FROM donations d WHERE {$where}",
            "SELECT d.*, o.reference AS order_reference FROM donations d
          LEFT JOIN orders o ON o.id = d.order_id
              WHERE {$where} ORDER BY d.created_at DESC",
            $params,
            50
        );

        return $this->render('admin.donations', 'Donations', [
            'result'  => $result,
            'status'  => $status,
            'summary' => Database::select(
                'SELECT donation_type, COUNT(*) AS count, COALESCE(SUM(amount_cents), 0) AS total
                   FROM donations WHERE status = "paid" GROUP BY donation_type ORDER BY total DESC'
            ),
            'total'   => (int) Database::scalar('SELECT COALESCE(SUM(amount_cents), 0) FROM donations WHERE status = "paid"'),
        ]);
    }
}

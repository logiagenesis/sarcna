<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Services\AccommodationService;
use App\Services\OrderService;
use App\Services\TransportService;

/**
 * The registration desk view: look someone up by check-in code, order
 * reference, name or email, then mark them arrived.
 */
final class CheckinController extends AdminController
{
    public function index(): string
    {
        return $this->render('admin.checkin', 'Check-in desk', [
            'results' => [],
            'query'   => '',
            'stats'   => $this->stats(),
        ]);
    }

    public function lookup(): string
    {
        $query = trim((string) $this->request->input('q', ''));

        if ($query === '') {
            $this->flashError('Type a check-in code, order reference, name or email.');
            $this->redirect(url('/admin/checkin'));
        }

        $like = '%' . $query . '%';

        $orders = Database::select(
            'SELECT o.* FROM orders o
              WHERE o.status = "paid"
                AND (o.checkin_code = :exact OR o.reference = :exact2
                     OR o.email LIKE :like OR CONCAT(o.first_name, " ", o.last_name) LIKE :like2)
           ORDER BY o.created_at DESC LIMIT 20',
            ['exact' => $query, 'exact2' => $query, 'like' => $like, 'like2' => $like]
        );

        $results = [];

        foreach ($orders as $order) {
            $results[] = [
                'order'     => $order,
                'items'     => OrderService::items((int) $order['id']),
                'bookings'  => AccommodationService::bookingsForOrder((int) $order['id']),
                'transport' => TransportService::bookingsForOrder((int) $order['id']),
            ];
        }

        return $this->render('admin.checkin', 'Check-in desk', [
            'results' => $results,
            'query'   => $query,
            'stats'   => $this->stats(),
        ]);
    }

    public function confirm(string $orderId): never
    {
        $order = OrderService::find((int) $orderId);

        if ($order === null) {
            $this->abort(404);
        }

        if ($order['status'] !== 'paid') {
            $this->flashError('That order has not been paid, so it cannot be checked in.');
            $this->back();
        }

        $arriving = $order['checked_in_at'] === null;

        Database::update('orders', [
            'checked_in_at' => $arriving ? date('Y-m-d H:i:s') : null,
        ], 'id = :id', ['id' => (int) $order['id']]);

        if ($arriving) {
            Database::update('bookings', [
                'status'        => 'checked_in',
                'checked_in_at' => date('Y-m-d H:i:s'),
            ], 'order_id = :order AND status = "confirmed"', ['order' => (int) $order['id']]);
        }

        $this->audit($arriving ? 'checked in an order' : 'undid a check-in', 'order', (int) $order['id']);
        $this->flashSuccess($arriving
            ? $order['first_name'] . ' ' . $order['last_name'] . ' is checked in. Hand over the badge and room key.'
            : 'Check-in undone.');

        $this->back(url('/admin/checkin'));
    }

    private function stats(): array
    {
        return [
            'paid'       => (int) Database::scalar('SELECT COUNT(*) FROM orders WHERE status = "paid"'),
            'checkedIn'  => (int) Database::scalar('SELECT COUNT(*) FROM orders WHERE status = "paid" AND checked_in_at IS NOT NULL'),
            'bedsTonight' => (int) Database::scalar('SELECT COUNT(*) FROM bookings WHERE active_night = CURDATE()'),
        ];
    }
}

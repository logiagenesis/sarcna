<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Services\AccommodationService;
use App\Services\AuthService;
use App\Services\FinanceService;
use App\Services\MailService;
use App\Services\OrderService;
use App\Services\TransportService;

final class OrderController extends AdminController
{
    public function index(): string
    {
        $status = (string) $this->request->input('status', '');
        $search = trim((string) $this->request->input('q', ''));

        $where  = ['1 = 1'];
        $params = [];

        if ($status !== '' && in_array($status, ['pending_payment', 'paid', 'failed', 'cancelled', 'refunded'], true)) {
            $where[]          = 'o.status = :status';
            $params['status'] = $status;
        }

        if ($search !== '') {
            $where[]          = '(o.reference LIKE :search OR o.email LIKE :search OR CONCAT(o.first_name, " ", o.last_name) LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        $clause = implode(' AND ', $where);

        $result = $this->paginate(
            "SELECT COUNT(*) FROM orders o WHERE {$clause}",
            "SELECT o.*, (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS item_count
               FROM orders o WHERE {$clause} ORDER BY o.created_at DESC",
            $params
        );

        return $this->render('admin.orders', 'Orders', [
            'result'  => $result,
            'status'  => $status,
            'search'  => $search,
            'totals'  => Database::first(
                "SELECT COALESCE(SUM(o.total_cents), 0) AS value, COUNT(*) AS count FROM orders o WHERE {$clause}",
                $params
            ),
        ]);
    }

    public function show(string $id): string
    {
        $order = OrderService::find((int) $id);

        if ($order === null) {
            $this->abort(404);
        }

        return $this->render('admin.order-show', 'Order ' . $order['reference'], [
            'order'             => $order,
            'items'             => OrderService::items((int) $order['id']),
            'bookings'          => AccommodationService::bookingsForOrder((int) $order['id']),
            'transportBookings' => TransportService::bookingsForOrder((int) $order['id']),
            'payments'          => Database::select('SELECT * FROM payments WHERE order_id = ? ORDER BY created_at DESC', [(int) $order['id']]),
            'logs'              => Database::select('SELECT * FROM payment_logs WHERE order_id = ? ORDER BY created_at DESC', [(int) $order['id']]),
            'customer'          => $order['user_id'] === null ? null : Database::first('SELECT * FROM users WHERE id = ?', [(int) $order['user_id']]),
            'refunds'           => Database::select(
                'SELECT r.*, u.email AS recorded_by FROM refunds r
              LEFT JOIN users u ON u.id = r.created_by
                  WHERE r.order_id = ? ORDER BY r.created_at DESC',
                [(int) $order['id']]
            ),
            'refundedTotal'     => FinanceService::refundedTotal((int) $order['id']),
            'canRefund'         => AuthService::can('finance'),
        ]);
    }

    public function updateStatus(string $id): never
    {
        $order  = OrderService::find((int) $id);
        $status = (string) $this->request->input('status', '');

        if ($order === null) {
            $this->abort(404);
        }

        if (!in_array($status, ['paid', 'failed', 'cancelled', 'refunded'], true)) {
            $this->flashError('That is not a status an administrator can set.');
            $this->back();
        }

        $reason = 'Set to ' . $status . ' manually by an administrator.';

        match ($status) {
            'paid'      => OrderService::markPaid($order),
            'failed'    => OrderService::markFailed($order, $reason),
            'cancelled' => OrderService::markCancelled($order, $reason),
            'refunded'  => OrderService::markRefunded($order, $reason),
        };

        $this->audit('changed order status to ' . $status, 'order', (int) $order['id'], ['from' => $order['status']]);
        $this->flashSuccess('Order ' . $order['reference'] . ' is now ' . str_replace('_', ' ', $status) . '.');
        $this->back(url('/admin/orders/' . $order['id']));
    }

    public function saveNote(string $id): never
    {
        $order = OrderService::find((int) $id);

        if ($order === null) {
            $this->abort(404);
        }

        Database::update('orders', [
            'admin_note' => mb_substr((string) $this->request->input('admin_note', ''), 0, 500),
        ], 'id = :id', ['id' => (int) $order['id']]);

        $this->audit('updated the note on an order', 'order', (int) $order['id']);
        $this->flashSuccess('Note saved.');
        $this->back(url('/admin/orders/' . $order['id']));
    }

    public function resendConfirmation(string $id): never
    {
        $order = OrderService::find((int) $id);

        if ($order === null) {
            $this->abort(404);
        }

        $sent = $order['status'] === 'paid'
            ? MailService::orderPaid($order)
            : MailService::orderCreated($order);

        $this->audit('resent an order confirmation', 'order', (int) $order['id']);

        if ($sent) {
            $this->flashSuccess('Confirmation resent to ' . $order['email'] . '.');
        } else {
            $this->flashError('The email could not be sent. Check Settings → Diagnostics and the mail log.');
        }

        $this->back(url('/admin/orders/' . $order['id']));
    }
}

<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Services\PayFastService;

final class PaymentController extends AdminController
{
    public function index(): string
    {
        $status = (string) $this->request->input('status', '');

        $where  = $status === '' ? '1 = 1' : 'p.status = :status';
        $params = $status === '' ? [] : ['status' => $status];

        $result = $this->paginate(
            "SELECT COUNT(*) FROM payments p WHERE {$where}",
            "SELECT p.*, o.reference, o.email, o.status AS order_status
               FROM payments p LEFT JOIN orders o ON o.id = p.order_id
              WHERE {$where} ORDER BY p.created_at DESC",
            $params
        );

        return $this->render('admin.payments', 'Payments', [
            'result'    => $result,
            'status'    => $status,
            'summary'   => Database::first(
                'SELECT COALESCE(SUM(amount_cents), 0) AS gross, COALESCE(SUM(fee_cents), 0) AS fees, COUNT(*) AS count
                   FROM payments WHERE status = "complete"'
            ),
            'sandbox'   => PayFastService::isSandbox(),
            'configured'=> PayFastService::isConfigured(),
        ]);
    }

    public function logs(): string
    {
        $event = (string) $this->request->input('event', '');

        $where  = $event === '' ? '1 = 1' : 'l.event = :event';
        $params = $event === '' ? [] : ['event' => $event];

        $result = $this->paginate(
            "SELECT COUNT(*) FROM payment_logs l WHERE {$where}",
            "SELECT l.*, o.reference FROM payment_logs l
          LEFT JOIN orders o ON o.id = l.order_id
              WHERE {$where} ORDER BY l.created_at DESC",
            $params,
            60
        );

        return $this->render('admin.payment-logs', 'Payment logs', [
            'result' => $result,
            'event'  => $event,
            'events' => array_column(Database::select('SELECT DISTINCT event FROM payment_logs ORDER BY event'), 'event'),
        ]);
    }
}

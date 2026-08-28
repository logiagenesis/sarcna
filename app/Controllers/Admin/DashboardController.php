<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Services\AccommodationService;
use App\Services\AuditService;
use App\Services\OrderService;
use App\Services\PayFastService;
use App\Services\ProductService;
use App\Services\TransportService;

final class DashboardController extends AdminController
{
    public function index(): string
    {
        // Housekeeping on every dashboard load: release stale holds and orders.
        AccommodationService::purgeExpiredHolds();
        OrderService::expireStalePendingOrders(180);

        $paidTotals = Database::first(
            'SELECT COUNT(*) AS orders, COALESCE(SUM(total_cents), 0) AS revenue FROM orders WHERE status = "paid"'
        );

        return $this->render('admin.dashboard', 'Dashboard', [
            'revenue'      => (int) $paidTotals['revenue'],
            'paidOrders'   => (int) $paidTotals['orders'],
            'pending'      => (int) Database::scalar('SELECT COUNT(*) FROM orders WHERE status = "pending_payment"'),
            'failed'       => (int) Database::scalar('SELECT COUNT(*) FROM orders WHERE status = "failed"'),
            'registrations' => (int) Database::scalar(
                'SELECT COALESCE(SUM(oi.quantity), 0) FROM order_items oi
                   JOIN orders o ON o.id = oi.order_id
                  WHERE o.status = "paid" AND oi.item_type = "registration"'
            ),
            'donationTotal' => (int) Database::scalar('SELECT COALESCE(SUM(amount_cents), 0) FROM donations WHERE status = "paid"'),
            'occupancy'     => AccommodationService::occupancySummary(),
            'transport'     => TransportService::summary(),
            'lowStock'      => ProductService::lowStock(8),
            'recentOrders'  => Database::select(
                'SELECT o.*, (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS item_count
                   FROM orders o ORDER BY o.created_at DESC LIMIT 8'
            ),
            'needsAttention' => Database::select(
                'SELECT * FROM orders WHERE admin_note LIKE "NEEDS ATTENTION%" ORDER BY created_at DESC LIMIT 5'
            ),
            'applications'  => (int) Database::scalar('SELECT COUNT(*) FROM service_applications WHERE status = "new"'),
            'messages'      => (int) Database::scalar('SELECT COUNT(*) FROM contact_messages WHERE status = "new"'),
            'customers'     => (int) Database::scalar('SELECT COUNT(*) FROM users WHERE is_admin = 0'),
            'salesByType'   => Database::select(
                'SELECT oi.item_type, COUNT(*) AS line_count, COALESCE(SUM(oi.total_cents), 0) AS revenue
                   FROM order_items oi JOIN orders o ON o.id = oi.order_id
                  WHERE o.status = "paid"
               GROUP BY oi.item_type ORDER BY revenue DESC'
            ),
            'recentPayments' => Database::select(
                'SELECT p.*, o.reference FROM payments p
              LEFT JOIN orders o ON o.id = p.order_id
              ORDER BY p.created_at DESC LIMIT 6'
            ),
            'audit'    => AuditService::recent(6),
            'sandbox'  => PayFastService::isSandbox(),
            'payfastConfigured' => PayFastService::isConfigured(),
        ]);
    }
}

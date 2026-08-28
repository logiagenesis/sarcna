<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Everything the finance chair needs to answer "where does the convention
 * stand?" without exporting anything to a spreadsheet first.
 *
 * Two rules run through all of it:
 *
 *   1. Only money that PayFast has confirmed counts as income. Orders awaiting
 *      payment are shown separately as pipeline, never mixed into revenue.
 *   2. Every figure is integer cents, and every total ties back to a list of
 *      orders or expenses the treasurer can open and check line by line.
 */
final class FinanceService
{
    public const CATEGORIES = ['registration', 'accommodation', 'transport', 'merchandise', 'donation'];

    /* ----------------------------------------------------------- periods */

    /** @return array{from: string, to: string, label: string, key: string} */
    public static function period(?string $key = null, ?string $from = null, ?string $to = null): array
    {
        $key ??= 'all';

        $yearStart = (string) SettingsService::get('financial_year_start', '2026-09-01');

        [$start, $end, $label] = match ($key) {
            'today'      => [date('Y-m-d'), date('Y-m-d'), 'Today'],
            'week'       => [date('Y-m-d', strtotime('monday this week')), date('Y-m-d'), 'This week'],
            'month'      => [date('Y-m-01'), date('Y-m-d'), 'This month'],
            'last_month' => [date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('last day of last month')), 'Last month'],
            'quarter'    => [date('Y-m-01', strtotime('-2 months')), date('Y-m-d'), 'Last three months'],
            'year'       => [$yearStart, date('Y-m-d'), 'Financial year to date'],
            'custom'     => [$from ?: date('Y-m-01'), $to ?: date('Y-m-d'), 'Custom range'],
            default      => ['2000-01-01', '2099-12-31', 'All time'],
        };

        return ['from' => $start, 'to' => $end, 'label' => $label, 'key' => $key];
    }

    /** Bounds as full datetimes, so a day range includes the whole last day. */
    private static function bounds(array $period): array
    {
        return ['from' => $period['from'] . ' 00:00:00', 'to' => $period['to'] . ' 23:59:59'];
    }

    /* ------------------------------------------------------------ income */

    /** Confirmed income, split by what it was for. */
    public static function incomeByCategory(array $period): array
    {
        $rows = Database::select(
            'SELECT oi.item_type AS category,
                    COUNT(DISTINCT o.id) AS orders,
                    COALESCE(SUM(oi.quantity), 0) AS units,
                    COALESCE(SUM(oi.total_cents), 0) AS gross_cents
               FROM order_items oi
               JOIN orders o ON o.id = oi.order_id
              WHERE o.status = "paid" AND o.paid_at BETWEEN :from AND :to
           GROUP BY oi.item_type',
            self::bounds($period)
        );

        $byCategory = [];

        foreach (self::CATEGORIES as $category) {
            $byCategory[$category] = ['category' => $category, 'orders' => 0, 'units' => 0, 'gross_cents' => 0];
        }

        foreach ($rows as $row) {
            $byCategory[$row['category']] = [
                'category'    => $row['category'],
                'orders'      => (int) $row['orders'],
                'units'       => (int) $row['units'],
                'gross_cents' => (int) $row['gross_cents'],
            ];
        }

        return array_values($byCategory);
    }

    /**
     * The headline set. Everything the treasurer is asked in a committee
     * meeting, in one query set.
     */
    public static function summary(array $period): array
    {
        $bounds = self::bounds($period);

        $paid = Database::first(
            'SELECT COUNT(*) AS orders,
                    COALESCE(SUM(subtotal_cents), 0) AS subtotal_cents,
                    COALESCE(SUM(discount_cents), 0) AS discount_cents,
                    COALESCE(SUM(total_cents), 0) AS gross_cents
               FROM orders WHERE status = "paid" AND paid_at BETWEEN :from AND :to',
            $bounds
        );

        $pending = Database::first(
            'SELECT COUNT(*) AS orders, COALESCE(SUM(total_cents), 0) AS cents
               FROM orders WHERE status = "pending_payment" AND created_at BETWEEN :from AND :to',
            $bounds
        );

        $lost = Database::first(
            'SELECT COUNT(*) AS orders, COALESCE(SUM(total_cents), 0) AS cents
               FROM orders WHERE status IN ("failed","cancelled") AND created_at BETWEEN :from AND :to',
            $bounds
        );

        $fees = self::fees($period);

        $refunded = (int) Database::scalar(
            'SELECT COALESCE(SUM(amount_cents), 0) FROM refunds
              WHERE status = "completed" AND COALESCE(refunded_on, DATE(created_at)) BETWEEN :from AND :to',
            ['from' => $period['from'], 'to' => $period['to']]
        );

        $expenses = self::expenseTotals($period);

        $gross = (int) $paid['gross_cents'];
        $net   = $gross - $refunded - $fees['total_cents'];

        return [
            'orders_paid'      => (int) $paid['orders'],
            'gross_cents'      => $gross,
            'subtotal_cents'   => (int) $paid['subtotal_cents'],
            'discount_cents'   => (int) $paid['discount_cents'],
            'refunded_cents'   => $refunded,
            'fees_cents'       => $fees['total_cents'],
            'fees_are_estimated' => $fees['estimated'],
            'net_income_cents' => $net,
            'pending_orders'   => (int) $pending['orders'],
            'pending_cents'    => (int) $pending['cents'],
            'lost_orders'      => (int) $lost['orders'],
            'lost_cents'       => (int) $lost['cents'],
            'expenses_paid_cents'      => $expenses['paid_cents'],
            'expenses_committed_cents' => $expenses['committed_cents'],
            'expenses_total_cents'     => $expenses['total_cents'],
            'surplus_cents'    => $net - $expenses['total_cents'],
            'cash_surplus_cents' => $net - $expenses['paid_cents'],
            'average_order_cents' => (int) $paid['orders'] > 0 ? (int) round($gross / (int) $paid['orders']) : 0,
        ];
    }

    /**
     * PayFast fees. Uses the fee PayFast actually reported where we have it and
     * estimates the rest from the configured rate, saying which is which — a
     * treasurer should never be shown an estimate dressed up as a fact.
     */
    public static function fees(array $period): array
    {
        $bounds = self::bounds($period);

        $reported = Database::first(
            'SELECT COALESCE(SUM(p.fee_cents), 0) AS fee_cents,
                    COUNT(*) AS payments,
                    SUM(CASE WHEN p.fee_cents > 0 THEN 1 ELSE 0 END) AS with_fee,
                    COALESCE(SUM(CASE WHEN p.fee_cents = 0 THEN p.amount_cents ELSE 0 END), 0) AS unfeed_cents
               FROM payments p
               JOIN orders o ON o.id = p.order_id
              WHERE p.status = "complete" AND o.paid_at BETWEEN :from AND :to',
            $bounds
        );

        $percent = (float) SettingsService::get('payfast_fee_percent', '3.5');
        $fixed   = rands(SettingsService::get('payfast_fee_fixed', '2.00'));

        $missing   = (int) $reported['payments'] - (int) $reported['with_fee'];
        $estimated = $missing > 0
            ? (int) round(((int) $reported['unfeed_cents']) * ($percent / 100)) + ($fixed * $missing)
            : 0;

        return [
            'reported_cents'  => (int) $reported['fee_cents'],
            'estimated_cents' => $estimated,
            'total_cents'     => (int) $reported['fee_cents'] + $estimated,
            'estimated'       => $missing > 0,
            'payments'        => (int) $reported['payments'],
            'without_fee'     => $missing,
        ];
    }

    /** Income per day, for the trend chart and for spotting a stalled week. */
    public static function dailyIncome(array $period, int $limit = 90): array
    {
        return Database::select(
            'SELECT DATE(paid_at) AS day, COUNT(*) AS orders, COALESCE(SUM(total_cents), 0) AS cents
               FROM orders
              WHERE status = "paid" AND paid_at BETWEEN :from AND :to
           GROUP BY DATE(paid_at)
           ORDER BY day DESC
              LIMIT ' . max(1, min(365, $limit)),
            self::bounds($period)
        );
    }

    /* ---------------------------------------------------------- products */

    public static function productPerformance(array $period): array
    {
        return Database::select(
            'SELECT p.id, p.name, p.type, p.sku,
                    COALESCE(SUM(oi.quantity), 0) AS units,
                    COALESCE(SUM(oi.total_cents), 0) AS gross_cents,
                    p.stock, p.track_stock, p.price_cents
               FROM order_items oi
               JOIN orders o ON o.id = oi.order_id
               JOIN products p ON p.id = oi.product_id
              WHERE o.status = "paid" AND o.paid_at BETWEEN :from AND :to
           GROUP BY p.id, p.name, p.type, p.sku, p.stock, p.track_stock, p.price_cents
           ORDER BY gross_cents DESC',
            self::bounds($period)
        );
    }

    /** Stock still on the shelf, at cost-free valuation (selling price). */
    public static function stockOnHand(): array
    {
        $rows = Database::select(
            'SELECT p.id, p.name, p.stock, p.price_cents,
                    (SELECT COALESCE(SUM(v.stock), 0) FROM product_variants v WHERE v.product_id = p.id) AS variant_stock,
                    (SELECT COUNT(*) FROM product_variants v WHERE v.product_id = p.id) AS variant_count
               FROM products p
              WHERE p.track_stock = 1 AND p.is_active = 1
           ORDER BY p.name'
        );

        $value = 0;

        foreach ($rows as &$row) {
            $units          = (int) $row['variant_count'] > 0 ? (int) $row['variant_stock'] : (int) $row['stock'];
            $row['units']   = $units;
            $row['value_cents'] = $units * (int) $row['price_cents'];
            $value         += $row['value_cents'];
        }
        unset($row);

        return ['rows' => $rows, 'value_cents' => $value];
    }

    /* ----------------------------------------------------- accommodation */

    public static function accommodationRevenue(array $period): array
    {
        return Database::select(
            'SELECT rt.name AS room_type,
                    COUNT(*) AS bed_nights,
                    COALESCE(SUM(bk.price_cents), 0) AS gross_cents,
                    SUM(bk.is_private_unit) AS private_rooms
               FROM bookings bk
               JOIN room_types rt ON rt.id = bk.room_type_id
               JOIN orders o ON o.id = bk.order_id
              WHERE bk.status IN ("confirmed","checked_in")
                AND o.status = "paid" AND o.paid_at BETWEEN :from AND :to
           GROUP BY rt.id, rt.name
           ORDER BY gross_cents DESC',
            self::bounds($period)
        );
    }

    public static function transportRevenue(array $period): array
    {
        return Database::select(
            'SELECT r.name AS route,
                    COUNT(*) AS passengers,
                    COALESCE(SUM(tb.price_cents), 0) AS gross_cents
               FROM transport_bookings tb
               JOIN transport_routes r ON r.id = tb.route_id
               JOIN orders o ON o.id = tb.order_id
              WHERE tb.status <> "cancelled" AND o.status = "paid" AND o.paid_at BETWEEN :from AND :to
           GROUP BY r.id, r.name
           ORDER BY gross_cents DESC',
            self::bounds($period)
        );
    }

    public static function donationBreakdown(array $period): array
    {
        return Database::select(
            'SELECT donation_type, COUNT(*) AS count,
                    COALESCE(SUM(amount_cents), 0) AS gross_cents,
                    SUM(is_anonymous) AS anonymous
               FROM donations
              WHERE status = "paid" AND created_at BETWEEN :from AND :to
           GROUP BY donation_type
           ORDER BY gross_cents DESC',
            self::bounds($period)
        );
    }

    /** What discounts and coupons actually cost the convention. */
    public static function discountCost(array $period): array
    {
        return Database::select(
            'SELECT COALESCE(coupon_code, "Manual discount") AS code,
                    COUNT(*) AS orders,
                    COALESCE(SUM(discount_cents), 0) AS cents
               FROM orders
              WHERE status = "paid" AND discount_cents > 0 AND paid_at BETWEEN :from AND :to
           GROUP BY coupon_code
           ORDER BY cents DESC',
            self::bounds($period)
        );
    }

    /* ---------------------------------------------------------- expenses */

    public static function expenseTotals(array $period): array
    {
        $row = Database::first(
            'SELECT COALESCE(SUM(CASE WHEN status = "paid" THEN amount_cents ELSE 0 END), 0) AS paid_cents,
                    COALESCE(SUM(CASE WHEN status IN ("committed","invoiced") THEN amount_cents ELSE 0 END), 0) AS committed_cents,
                    COALESCE(SUM(CASE WHEN status = "planned" THEN amount_cents ELSE 0 END), 0) AS planned_cents,
                    COUNT(*) AS count
               FROM expenses
              WHERE status <> "cancelled" AND incurred_on BETWEEN :from AND :to',
            ['from' => $period['from'], 'to' => $period['to']]
        );

        return [
            'paid_cents'      => (int) $row['paid_cents'],
            'committed_cents' => (int) $row['committed_cents'],
            'planned_cents'   => (int) $row['planned_cents'],
            // What the convention is on the hook for: paid plus committed.
            'total_cents'     => (int) $row['paid_cents'] + (int) $row['committed_cents'],
            'count'           => (int) $row['count'],
        ];
    }

    public static function expensesByCategory(array $period): array
    {
        return Database::select(
            'SELECT COALESCE(c.name, "Uncategorised") AS category,
                    COUNT(*) AS count,
                    COALESCE(SUM(CASE WHEN e.status = "paid" THEN e.amount_cents ELSE 0 END), 0) AS paid_cents,
                    COALESCE(SUM(CASE WHEN e.status IN ("committed","invoiced") THEN e.amount_cents ELSE 0 END), 0) AS committed_cents,
                    COALESCE(SUM(CASE WHEN e.status = "planned" THEN e.amount_cents ELSE 0 END), 0) AS planned_cents
               FROM expenses e
          LEFT JOIN expense_categories c ON c.id = e.category_id
              WHERE e.status <> "cancelled" AND e.incurred_on BETWEEN :from AND :to
           GROUP BY c.id, c.name
           ORDER BY (COALESCE(SUM(CASE WHEN e.status IN ("paid","committed","invoiced") THEN e.amount_cents ELSE 0 END), 0)) DESC',
            ['from' => $period['from'], 'to' => $period['to']]
        );
    }

    /** Bills that are due and not yet paid — the treasurer's diary. */
    public static function upcomingPayments(int $limit = 20): array
    {
        return Database::select(
            'SELECT e.*, c.name AS category_name
               FROM expenses e
          LEFT JOIN expense_categories c ON c.id = e.category_id
              WHERE e.paid_on IS NULL AND e.status IN ("committed","invoiced")
           ORDER BY COALESCE(e.due_on, "2099-12-31"), e.amount_cents DESC
              LIMIT ' . max(1, min(100, $limit))
        );
    }

    /* ------------------------------------------------------ budget vs actual */

    public static function budgetVsActual(array $period): array
    {
        $lines  = Database::select('SELECT * FROM budget_lines ORDER BY kind, sort_order, id');
        $income = [];

        foreach (self::incomeByCategory($period) as $row) {
            $income[strtolower($row['category'])] = (int) $row['gross_cents'];
        }

        $expenses = [];

        foreach (self::expensesByCategory($period) as $row) {
            $expenses[strtolower($row['category'])] = (int) $row['paid_cents'] + (int) $row['committed_cents'];
        }

        $result = ['income' => [], 'expense' => [], 'totals' => [
            'income_budget' => 0, 'income_actual' => 0,
            'expense_budget' => 0, 'expense_actual' => 0,
        ]];

        foreach ($lines as $line) {
            $key = strtolower((string) $line['category']);

            // Budget categories are named for people; income actuals are keyed
            // by item type. Map the obvious ones.
            $actual = $line['kind'] === 'income'
                ? ($income[$key] ?? $income[rtrim($key, 's')] ?? 0)
                : ($expenses[$key] ?? 0);

            $budget = (int) $line['budgeted_cents'];

            $result[$line['kind']][] = [
                'category'       => $line['category'],
                'description'    => $line['description'],
                'budgeted_cents' => $budget,
                'actual_cents'   => $actual,
                'variance_cents' => $line['kind'] === 'income' ? $actual - $budget : $budget - $actual,
                'percent'        => $budget > 0 ? (int) round(($actual / $budget) * 100) : null,
                'notes'          => $line['notes'],
            ];

            $result['totals'][$line['kind'] . '_budget'] += $budget;
            $result['totals'][$line['kind'] . '_actual'] += $actual;
        }

        $result['totals']['budget_surplus'] = $result['totals']['income_budget'] - $result['totals']['expense_budget'];
        $result['totals']['actual_surplus'] = $result['totals']['income_actual'] - $result['totals']['expense_actual'];

        return $result;
    }

    /* -------------------------------------------------------- reconciliation */

    /** Every confirmed payment, for ticking off against the PayFast statement. */
    public static function reconciliationRows(array $period): array
    {
        return Database::select(
            'SELECT p.id, p.created_at, p.provider_reference, p.amount_cents, p.fee_cents,
                    (p.amount_cents - p.fee_cents) AS net_cents,
                    o.id AS order_id, o.reference AS order_reference, o.email, o.status AS order_status
               FROM payments p
               JOIN orders o ON o.id = p.order_id
              WHERE p.status = "complete" AND p.created_at BETWEEN :from AND :to
           ORDER BY p.created_at DESC',
            self::bounds($period)
        );
    }

    /** Totals across every confirmed payment in the period, not just this page. */
    public static function reconciliationTotals(array $period): array
    {
        $row = Database::first(
            'SELECT COUNT(*) AS payments,
                    COALESCE(SUM(p.amount_cents), 0) AS gross_cents,
                    COALESCE(SUM(p.fee_cents), 0) AS fee_cents,
                    COALESCE(SUM(p.amount_cents - p.fee_cents), 0) AS net_cents
               FROM payments p
               JOIN orders o ON o.id = p.order_id
              WHERE p.status = "complete" AND p.created_at BETWEEN :from AND :to',
            self::bounds($period)
        );

        return [
            'payments'    => (int) $row['payments'],
            'gross_cents' => (int) $row['gross_cents'],
            'fee_cents'   => (int) $row['fee_cents'],
            'net_cents'   => (int) $row['net_cents'],
        ];
    }

    /**
     * Payments that need a human. A completed payment whose order was properly
     * refunded is not an exception — that is the refund working — so only an
     * order that never reached paid, or one still sitting in limbo, is flagged.
     */
    public static function reconciliationExceptions(): array
    {
        return Database::select(
            'SELECT p.*, o.reference AS order_reference, o.status AS order_status
               FROM payments p
          LEFT JOIN orders o ON o.id = p.order_id
              WHERE (p.status = "complete" AND (o.id IS NULL OR o.status NOT IN ("paid", "refunded")))
                 OR (p.status = "complete" AND p.signature_valid = 0)
                 OR (p.status = "initiated" AND p.created_at < DATE_SUB(NOW(), INTERVAL 2 HOUR))
           ORDER BY p.created_at DESC
              LIMIT 100'
        );
    }

    /* --------------------------------------------------------------- VAT */

    /**
     * VAT split, only meaningful if the committee is registered. Prices are
     * treated as VAT-inclusive, which is the South African norm.
     */
    public static function vat(array $period): ?array
    {
        if (!SettingsService::bool('vat_registered', false)) {
            return null;
        }

        $rate    = (float) SettingsService::get('vat_rate', '15');
        $summary = self::summary($period);
        $gross   = $summary['gross_cents'] - $summary['refunded_cents'];

        $exclusive = (int) round($gross / (1 + ($rate / 100)));

        return [
            'rate'            => $rate,
            'gross_cents'     => $gross,
            'exclusive_cents' => $exclusive,
            'vat_cents'       => $gross - $exclusive,
        ];
    }

    /* ------------------------------------------------------------ refunds */

    public static function refunds(array $period): array
    {
        return Database::select(
            'SELECT r.*, o.reference AS order_reference, o.email, u.email AS refunded_by
               FROM refunds r
               JOIN orders o ON o.id = r.order_id
          LEFT JOIN users u ON u.id = r.created_by
              WHERE COALESCE(r.refunded_on, DATE(r.created_at)) BETWEEN :from AND :to
           ORDER BY r.created_at DESC',
            ['from' => $period['from'], 'to' => $period['to']]
        );
    }

    /** Total already refunded against an order, so we never over-refund. */
    public static function refundedTotal(int $orderId): int
    {
        return (int) Database::scalar(
            'SELECT COALESCE(SUM(amount_cents), 0) FROM refunds WHERE order_id = ? AND status = "completed"',
            [$orderId]
        );
    }
}

<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Response;
use App\Services\AuthService;
use App\Services\CsvService;
use App\Services\FinanceService;

/** CSV exports of everything the committee needs off-site. */
final class ExportController extends AdminController
{
    /**
     * Which capability each dataset belongs to.
     *
     * The route only asks for "exports", which every exporting role holds —
     * so on its own it let a transport admin download the finance pack and the
     * customer list, screens the same person is refused at the door. A CSV is
     * the same data as the screen it comes from, so it answers to the same
     * capability: whoever may not open /admin/finance may not export it either.
     *
     * A dataset that is not listed here is refused outright, so adding an
     * export without deciding who may run it fails closed.
     *
     * @var array<string, string>
     */
    private const DATASET_CAPABILITIES = [
        'orders'       => 'orders',
        'order-items'  => 'orders',
        'attendees'    => 'orders',
        'bookings'     => 'bookings',
        'rooming-list' => 'bookings',
        'transport'    => 'transport',
        'donations'    => 'donations',
        'applications' => 'applications',
        'messages'     => 'messages',
        'customers'    => 'customers',
        'stock'        => 'products',
        'payments'     => 'payments',
        'expenses'     => 'finance',
        'refunds'      => 'finance',
        'budget'       => 'finance',
        'finance-pack' => 'finance',
    ];

    public function download(string $dataset): never
    {
        $capability = self::DATASET_CAPABILITIES[$dataset] ?? null;

        if ($capability === null) {
            $this->abort(404, 'That export does not exist.');
        }

        if (!AuthService::can($capability)) {
            $this->abort(403, 'Your admin role does not include access to that export.');
        }

        [$rows, $columns] = match ($dataset) {
            'orders'       => $this->orders(),
            'order-items'  => $this->orderItems(),
            'attendees'    => $this->attendees(),
            'bookings'     => $this->bookings(),
            'rooming-list' => $this->roomingList(),
            'transport'    => $this->transport(),
            'donations'    => $this->donations(),
            'applications' => $this->applications(),
            'messages'     => $this->messages(),
            'customers'    => $this->customers(),
            'stock'        => $this->stock(),
            'payments'     => $this->payments(),
            'expenses'     => $this->expenses(),
            'refunds'      => $this->refunds(),
            'budget'       => $this->budget(),
            'finance-pack' => $this->financePack(),
            default        => [[], []],
        };

        if ($columns === []) {
            $this->abort(404, 'That export does not exist.');
        }

        $this->audit('exported ' . $dataset, 'export', null, ['rows' => count($rows)]);

        Response::download(CsvService::filename($dataset), CsvService::build($rows, $columns));
    }

    private function orders(): array
    {
        return [
            Database::select(
                'SELECT o.reference, o.created_at, o.paid_at, o.status, o.first_name, o.last_name, o.email, o.phone,
                        o.subtotal_cents / 100 AS subtotal, o.discount_cents / 100 AS discount, o.total_cents / 100 AS total,
                        o.coupon_code, o.checkin_code, o.checked_in_at, o.customer_note, o.admin_note
                   FROM orders o ORDER BY o.created_at DESC'
            ),
            [
                'reference' => 'Order reference', 'created_at' => 'Placed', 'paid_at' => 'Paid',
                'status' => 'Status', 'first_name' => 'First name', 'last_name' => 'Last name',
                'email' => 'Email', 'phone' => 'Phone', 'subtotal' => 'Subtotal (R)',
                'discount' => 'Discount (R)', 'total' => 'Total (R)', 'coupon_code' => 'Coupon',
                'checkin_code' => 'Check-in code', 'checked_in_at' => 'Checked in',
                'customer_note' => 'Customer note', 'admin_note' => 'Admin note',
            ],
        ];
    }

    private function orderItems(): array
    {
        return [
            Database::select(
                'SELECT o.reference, o.status, oi.item_type, oi.description, oi.quantity,
                        oi.unit_price_cents / 100 AS unit_price, oi.total_cents / 100 AS total, oi.night, oi.meta
                   FROM order_items oi JOIN orders o ON o.id = oi.order_id
               ORDER BY o.created_at DESC, oi.id'
            ),
            [
                'reference' => 'Order', 'status' => 'Order status', 'item_type' => 'Type',
                'description' => 'Description', 'quantity' => 'Qty', 'unit_price' => 'Unit (R)',
                'total' => 'Total (R)', 'night' => 'Night', 'meta' => 'Details',
            ],
        ];
    }

    private function attendees(): array
    {
        return [
            Database::select(
                'SELECT o.reference, o.checkin_code, oi.description AS registration, oi.quantity,
                        o.first_name, o.last_name, o.email, o.phone, oi.meta, o.checked_in_at
                   FROM order_items oi JOIN orders o ON o.id = oi.order_id
                  WHERE oi.item_type = "registration" AND o.status = "paid"
               ORDER BY o.last_name, o.first_name'
            ),
            [
                'reference' => 'Order', 'checkin_code' => 'Check-in code', 'registration' => 'Registration',
                'quantity' => 'Qty', 'first_name' => 'First name', 'last_name' => 'Last name',
                'email' => 'Email', 'phone' => 'Phone', 'meta' => 'Attendee details', 'checked_in_at' => 'Checked in',
            ],
        ];
    }

    private function bookings(): array
    {
        return [
            Database::select(
                'SELECT bk.reference, bk.night, rt.name AS room_type, ru.name AS unit, b.label AS bed,
                        bk.guest_name, bk.guest_email, bk.guest_phone, bk.roommate_request,
                        bk.accessibility_needs, bk.notes, bk.is_private_unit, bk.status,
                        bk.price_cents / 100 AS price, o.reference AS order_reference
                   FROM bookings bk
                   JOIN room_types rt ON rt.id = bk.room_type_id
                   JOIN room_units ru ON ru.id = bk.room_unit_id
                   JOIN beds b ON b.id = bk.bed_id
              LEFT JOIN orders o ON o.id = bk.order_id
               ORDER BY bk.night, rt.sort_order, ru.name, b.label'
            ),
            [
                'reference' => 'Booking', 'night' => 'Night', 'room_type' => 'Room type', 'unit' => 'Unit',
                'bed' => 'Bed', 'guest_name' => 'Guest', 'guest_email' => 'Email', 'guest_phone' => 'Phone',
                'roommate_request' => 'Roommate request', 'accessibility_needs' => 'Accessibility',
                'notes' => 'Notes', 'is_private_unit' => 'Private unit', 'status' => 'Status',
                'price' => 'Price (R)', 'order_reference' => 'Order',
            ],
        ];
    }

    /** One row per unit per night — what the venue actually wants. */
    private function roomingList(): array
    {
        return [
            Database::select(
                'SELECT bk.night, rt.name AS room_type, ru.name AS unit,
                        GROUP_CONCAT(CONCAT(b.label, ": ", COALESCE(bk.guest_name, "unnamed")) ORDER BY b.sort_order SEPARATOR " | ") AS occupants,
                        COUNT(*) AS beds_used,
                        MAX(bk.is_private_unit) AS private_unit,
                        GROUP_CONCAT(DISTINCT NULLIF(bk.accessibility_needs, "") SEPARATOR "; ") AS accessibility
                   FROM bookings bk
                   JOIN room_types rt ON rt.id = bk.room_type_id
                   JOIN room_units ru ON ru.id = bk.room_unit_id
                   JOIN beds b ON b.id = bk.bed_id
                  WHERE bk.status IN ("confirmed", "checked_in")
               GROUP BY bk.night, rt.name, ru.name, ru.id
               ORDER BY bk.night, rt.sort_order, ru.name'
            ),
            [
                'night' => 'Night', 'room_type' => 'Room type', 'unit' => 'Unit',
                'occupants' => 'Occupants', 'beds_used' => 'Beds used',
                'private_unit' => 'Private unit', 'accessibility' => 'Accessibility notes',
            ],
        ];
    }

    private function transport(): array
    {
        return [
            Database::select(
                'SELECT tb.reference, r.name AS route, s.departs_at, s.pickup_point, s.dropoff_point,
                        tb.passenger_name, tb.phone, tb.email, tb.flight_number, tb.luggage_count,
                        tb.accessibility_needs, tb.notes, tb.status, tb.checked_in_at, o.reference AS order_reference
                   FROM transport_bookings tb
                   JOIN transport_slots s ON s.id = tb.slot_id
                   JOIN transport_routes r ON r.id = tb.route_id
              LEFT JOIN orders o ON o.id = tb.order_id
               ORDER BY s.departs_at, tb.passenger_name'
            ),
            [
                'reference' => 'Booking', 'route' => 'Route', 'departs_at' => 'Departs',
                'pickup_point' => 'Pick-up', 'dropoff_point' => 'Drop-off', 'passenger_name' => 'Passenger',
                'phone' => 'Phone', 'email' => 'Email', 'flight_number' => 'Flight', 'luggage_count' => 'Bags',
                'accessibility_needs' => 'Accessibility', 'notes' => 'Notes', 'status' => 'Status',
                'checked_in_at' => 'Checked in', 'order_reference' => 'Order',
            ],
        ];
    }

    private function donations(): array
    {
        return [
            Database::select(
                'SELECT d.reference, d.created_at, d.donation_type, d.amount_cents / 100 AS amount,
                        d.name, d.email, d.is_anonymous, d.message, d.status, o.reference AS order_reference
                   FROM donations d LEFT JOIN orders o ON o.id = d.order_id
               ORDER BY d.created_at DESC'
            ),
            [
                'reference' => 'Reference', 'created_at' => 'Date', 'donation_type' => 'Type',
                'amount' => 'Amount (R)', 'name' => 'Name', 'email' => 'Email',
                'is_anonymous' => 'Anonymous', 'message' => 'Message', 'status' => 'Status',
                'order_reference' => 'Order',
            ],
        ];
    }

    private function applications(): array
    {
        return [
            Database::select('SELECT reference, created_at, name, email, phone, region, home_group, clean_time, service_areas, availability, skills, notes, status, admin_notes FROM service_applications ORDER BY created_at DESC'),
            [
                'reference' => 'Reference', 'created_at' => 'Submitted', 'name' => 'Name', 'email' => 'Email',
                'phone' => 'Phone', 'region' => 'Region', 'home_group' => 'Home group', 'clean_time' => 'Clean time',
                'service_areas' => 'Service areas', 'availability' => 'Availability', 'skills' => 'Skills',
                'notes' => 'Notes', 'status' => 'Status', 'admin_notes' => 'Admin notes',
            ],
        ];
    }

    private function messages(): array
    {
        return [
            Database::select('SELECT created_at, name, email, phone, subject, message, status, admin_notes FROM contact_messages ORDER BY created_at DESC'),
            [
                'created_at' => 'Received', 'name' => 'Name', 'email' => 'Email', 'phone' => 'Phone',
                'subject' => 'Subject', 'message' => 'Message', 'status' => 'Status', 'admin_notes' => 'Notes',
            ],
        ];
    }

    private function customers(): array
    {
        return [
            Database::select(
                'SELECT u.created_at, u.first_name, u.last_name, u.email, u.phone, u.home_group, u.region,
                        u.dietary_notes, u.accessibility_notes, u.marketing_opt_in, u.status,
                        (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id AND o.status = "paid") AS paid_orders,
                        (SELECT COALESCE(SUM(o.total_cents), 0) / 100 FROM orders o WHERE o.user_id = u.id AND o.status = "paid") AS spent
                   FROM users u WHERE u.is_admin = 0 ORDER BY u.created_at DESC'
            ),
            [
                'created_at' => 'Registered', 'first_name' => 'First name', 'last_name' => 'Last name',
                'email' => 'Email', 'phone' => 'Phone', 'home_group' => 'Home group', 'region' => 'Region',
                'dietary_notes' => 'Dietary', 'accessibility_notes' => 'Accessibility',
                'marketing_opt_in' => 'Opted in', 'status' => 'Status', 'paid_orders' => 'Paid orders', 'spent' => 'Spent (R)',
            ],
        ];
    }

    private function stock(): array
    {
        return [
            Database::select(
                'SELECT p.name, p.sku, p.type, p.price_cents / 100 AS price, p.track_stock, p.stock,
                        v.size, v.colour, v.sku AS variant_sku, v.stock AS variant_stock,
                        (SELECT COALESCE(SUM(oi.quantity), 0) FROM order_items oi JOIN orders o ON o.id = oi.order_id
                          WHERE oi.product_id = p.id AND o.status = "paid") AS sold
                   FROM products p LEFT JOIN product_variants v ON v.product_id = p.id
               ORDER BY p.type, p.name, v.sort_order'
            ),
            [
                'name' => 'Product', 'sku' => 'SKU', 'type' => 'Type', 'price' => 'Price (R)',
                'track_stock' => 'Tracks stock', 'stock' => 'Product stock', 'size' => 'Size',
                'colour' => 'Colour', 'variant_sku' => 'Variant SKU', 'variant_stock' => 'Variant stock', 'sold' => 'Sold',
            ],
        ];
    }

    private function payments(): array
    {
        return [
            Database::select(
                'SELECT p.created_at, o.reference, p.provider, p.provider_reference,
                        p.amount_cents / 100 AS amount, p.fee_cents / 100 AS fee, p.status, p.signature_valid, p.source_ip
                   FROM payments p LEFT JOIN orders o ON o.id = p.order_id ORDER BY p.created_at DESC'
            ),
            [
                'created_at' => 'Date', 'reference' => 'Order', 'provider' => 'Provider',
                'provider_reference' => 'PayFast ID', 'amount' => 'Amount (R)', 'fee' => 'Fee (R)',
                'status' => 'Status', 'signature_valid' => 'Signature valid', 'source_ip' => 'Source IP',
            ],
        ];
    }

    /* --------------------------------------------------------------- finance */

    /** The reporting period the finance screens are currently showing. */
    private function financePeriod(): array
    {
        return FinanceService::period(
            (string) $this->request->input('period', 'all'),
            (string) $this->request->input('from', ''),
            (string) $this->request->input('to', '')
        );
    }

    private function expenses(): array
    {
        $period = $this->financePeriod();

        return [
            Database::select(
                'SELECT e.reference, e.incurred_on, e.due_on, e.paid_on, e.status, c.name AS category,
                        e.supplier, e.description, e.invoice_number, e.payment_method,
                        e.amount_cents / 100 AS amount, e.vat_cents / 100 AS vat, e.notes,
                        u.email AS captured_by
                   FROM expenses e
              LEFT JOIN expense_categories c ON c.id = e.category_id
              LEFT JOIN users u ON u.id = e.created_by
                  WHERE e.incurred_on BETWEEN ? AND ?
               ORDER BY e.incurred_on DESC, e.id DESC',
                [$period['from'], $period['to']]
            ),
            [
                'reference' => 'Reference', 'incurred_on' => 'Date incurred', 'due_on' => 'Due',
                'paid_on' => 'Paid on', 'status' => 'Status', 'category' => 'Category',
                'supplier' => 'Supplier', 'description' => 'Description', 'invoice_number' => 'Invoice',
                'payment_method' => 'Method', 'amount' => 'Amount (R)', 'vat' => 'VAT (R)',
                'notes' => 'Notes', 'captured_by' => 'Captured by',
            ],
        ];
    }

    private function refunds(): array
    {
        $period = $this->financePeriod();

        return [
            Database::select(
                'SELECT r.reference, COALESCE(r.refunded_on, DATE(r.created_at)) AS refunded_on,
                        o.reference AS order_reference, o.email, r.amount_cents / 100 AS amount,
                        r.category, r.method, r.provider_reference, r.status, r.reason,
                        u.email AS recorded_by
                   FROM refunds r
                   JOIN orders o ON o.id = r.order_id
              LEFT JOIN users u ON u.id = r.created_by
                  WHERE COALESCE(r.refunded_on, DATE(r.created_at)) BETWEEN ? AND ?
               ORDER BY r.created_at DESC',
                [$period['from'], $period['to']]
            ),
            [
                'reference' => 'Refund reference', 'refunded_on' => 'Refunded on',
                'order_reference' => 'Order', 'email' => 'Delegate', 'amount' => 'Amount (R)',
                'category' => 'Category', 'method' => 'Method', 'provider_reference' => 'Provider reference',
                'status' => 'Status', 'reason' => 'Reason', 'recorded_by' => 'Recorded by',
            ],
        ];
    }

    private function budget(): array
    {
        $period = $this->financePeriod();
        $budget = FinanceService::budgetVsActual($period);
        $rows   = [];

        foreach (['income', 'expense'] as $kind) {
            foreach ($budget[$kind] as $line) {
                $rows[] = [
                    'kind'     => $kind,
                    'category' => $line['category'],
                    'description' => $line['description'],
                    'budgeted' => number_format(((int) $line['budgeted_cents']) / 100, 2, '.', ''),
                    'actual'   => number_format(((int) $line['actual_cents']) / 100, 2, '.', ''),
                    'variance' => number_format(((int) $line['variance_cents']) / 100, 2, '.', ''),
                    'percent'  => $line['percent'] === null ? '' : $line['percent'] . '%',
                    'notes'    => $line['notes'],
                ];
            }
        }

        return [
            $rows,
            [
                'kind' => 'Side', 'category' => 'Category', 'description' => 'Description',
                'budgeted' => 'Budgeted (R)', 'actual' => 'Actual (R)', 'variance' => 'Variance (R)',
                'percent' => 'Used', 'notes' => 'Notes',
            ],
        ];
    }

    /**
     * The whole financial position on one sheet: the thing the finance chair
     * takes to a committee meeting or hands to an auditor.
     */
    private function financePack(): array
    {
        $period  = $this->financePeriod();
        $summary = FinanceService::summary($period);
        $fees    = FinanceService::fees($period);
        $budget  = FinanceService::budgetVsActual($period);
        $vat     = FinanceService::vat($period);
        $stock   = FinanceService::stockOnHand();

        $rows = [];

        $line = static function (string $section, string $label, $amount, string $note = '') use (&$rows): void {
            $rows[] = [
                'section' => $section,
                'line'    => $label,
                'amount'  => is_int($amount) ? number_format($amount / 100, 2, '.', '') : (string) $amount,
                'note'    => $note,
            ];
        };

        $line('Period', 'Reporting period', $period['label'], $period['from'] . ' to ' . $period['to']);
        $line('Period', 'Generated', date('Y-m-d H:i'), 'Africa/Johannesburg');

        $line('Income', 'Gross confirmed income', $summary['gross_cents'], $summary['orders_paid'] . ' paid orders');
        $line('Income', 'Discounts given', $summary['discount_cents'], 'Coupons and concessions');
        $line('Income', 'Refunds', -$summary['refunded_cents']);
        $line('Income', 'Gateway fees', -$summary['fees_cents'], $fees['estimated']
            ? 'Includes an estimate on ' . $fees['without_fee'] . ' payments PayFast has not reported a fee for'
            : 'All fees reported by PayFast');
        $line('Income', 'Net income', $summary['net_income_cents']);
        $line('Income', 'Average order value', $summary['average_order_cents']);

        foreach (FinanceService::incomeByCategory($period) as $row) {
            $line('Income by category', ucfirst((string) $row['category']), (int) $row['gross_cents'], $row['units'] . ' items');
        }

        $line('Pipeline', 'Awaiting payment', $summary['pending_cents'], $summary['pending_orders'] . ' orders — not income yet');
        $line('Pipeline', 'Failed or cancelled', $summary['lost_cents'], $summary['lost_orders'] . ' orders');

        $line('Expenditure', 'Paid', $summary['expenses_paid_cents']);
        $line('Expenditure', 'Committed, not yet paid', $summary['expenses_committed_cents']);
        $line('Expenditure', 'Total on the hook for', $summary['expenses_total_cents']);

        foreach (FinanceService::expensesByCategory($period) as $row) {
            $line('Expenditure by category', (string) $row['category'], (int) $row['paid_cents'] + (int) $row['committed_cents']);
        }

        $line('Position', 'Surplus after all commitments', $summary['surplus_cents']);
        $line('Position', 'Cash surplus (paid expenses only)', $summary['cash_surplus_cents']);
        $line('Position', 'Merchandise stock on hand', $stock['value_cents'], 'At cost, not yet sold');

        $line('Budget', 'Budgeted income', (int) $budget['totals']['income_budget']);
        $line('Budget', 'Actual income', (int) $budget['totals']['income_actual']);
        $line('Budget', 'Budgeted expenditure', (int) $budget['totals']['expense_budget']);
        $line('Budget', 'Actual expenditure', (int) $budget['totals']['expense_actual']);
        $line('Budget', 'Budgeted surplus', (int) $budget['totals']['budget_surplus']);
        $line('Budget', 'Actual surplus', (int) $budget['totals']['actual_surplus']);

        if ($vat !== null) {
            $line('VAT', 'VAT-inclusive turnover', $vat['gross_cents']);
            $line('VAT', 'Excluding VAT', $vat['exclusive_cents']);
            $line('VAT', 'VAT at ' . $vat['rate'] . '%', $vat['vat_cents']);
        }

        $exceptions = FinanceService::reconciliationExceptions();
        $line('Control', 'Reconciliation exceptions', (string) count($exceptions), $exceptions === [] ? 'Nothing outstanding' : 'Needs a human');

        return [
            $rows,
            ['section' => 'Section', 'line' => 'Line', 'amount' => 'Amount (R)', 'note' => 'Note'],
        ];
    }
}

<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Response;
use App\Services\CsvService;

/** CSV exports of everything the committee needs off-site. */
final class ExportController extends AdminController
{
    public function download(string $dataset): never
    {
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
}

<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Transport sells like a product but carries route-specific seat inventory,
 * so a shuttle can never be oversold.
 */
final class TransportService
{
    public static function routes(bool $activeOnly = true): array
    {
        $sql = 'SELECT r.*,
                       (SELECT COUNT(*) FROM transport_slots s WHERE s.route_id = r.id AND s.is_active = 1) AS slot_count,
                       (SELECT COALESCE(SUM(s.capacity - s.seats_taken), 0) FROM transport_slots s WHERE s.route_id = r.id AND s.is_active = 1) AS seats_available
                  FROM transport_routes r';

        if ($activeOnly) {
            $sql .= ' WHERE r.is_active = 1';
        }

        return Database::select($sql . ' ORDER BY r.sort_order, r.id');
    }

    public static function findRoute(int|string $identifier): ?array
    {
        $column = is_int($identifier) || ctype_digit((string) $identifier) ? 'id' : 'slug';

        return Database::first("SELECT * FROM transport_routes WHERE {$column} = ? LIMIT 1", [$identifier]);
    }

    public static function slots(int $routeId, bool $availableOnly = true): array
    {
        $sql = 'SELECT * FROM transport_slots WHERE route_id = ? AND is_active = 1';

        if ($availableOnly) {
            $sql .= ' AND seats_taken < capacity';
        }

        return Database::select($sql . ' ORDER BY departs_at', [$routeId]);
    }

    public static function findSlot(int $slotId): ?array
    {
        return Database::first(
            'SELECT s.*, r.name AS route_name, r.slug AS route_slug, r.price_cents, r.direction, r.requires_flight_number
               FROM transport_slots s
               JOIN transport_routes r ON r.id = s.route_id
              WHERE s.id = ? LIMIT 1',
            [$slotId]
        );
    }

    public static function seatsLeft(int $slotId): int
    {
        $slot = Database::first('SELECT capacity, seats_taken FROM transport_slots WHERE id = ?', [$slotId]);

        if ($slot === null) {
            return 0;
        }

        return max(0, (int) $slot['capacity'] - (int) $slot['seats_taken']);
    }

    /**
     * Reserve seats atomically. The conditional UPDATE is what stops two
     * checkouts from taking the last seat at the same moment.
     */
    public static function reserveSeats(int $slotId, int $seats): bool
    {
        $affected = Database::run(
            'UPDATE transport_slots
                SET seats_taken = seats_taken + :seats
              WHERE id = :id AND is_active = 1 AND seats_taken + :seats2 <= capacity',
            ['seats' => $seats, 'id' => $slotId, 'seats2' => $seats]
        )->rowCount();

        return $affected === 1;
    }

    public static function releaseSeats(int $slotId, int $seats): void
    {
        Database::run(
            'UPDATE transport_slots SET seats_taken = GREATEST(0, seats_taken - :seats) WHERE id = :id',
            ['seats' => $seats, 'id' => $slotId]
        );
    }

    public static function bookingsForOrder(int $orderId): array
    {
        return Database::select(
            'SELECT tb.*, r.name AS route_name, s.departs_at, s.pickup_point, s.dropoff_point
               FROM transport_bookings tb
               JOIN transport_slots s ON s.id = tb.slot_id
               JOIN transport_routes r ON r.id = tb.route_id
              WHERE tb.order_id = ?
           ORDER BY s.departs_at',
            [$orderId]
        );
    }

    public static function bookingsForUser(int $userId): array
    {
        return Database::select(
            'SELECT tb.*, r.name AS route_name, s.departs_at, s.pickup_point, s.dropoff_point, o.reference AS order_reference
               FROM transport_bookings tb
               JOIN transport_slots s ON s.id = tb.slot_id
               JOIN transport_routes r ON r.id = tb.route_id
          LEFT JOIN orders o ON o.id = tb.order_id
              WHERE tb.user_id = ? AND tb.status <> "cancelled"
           ORDER BY s.departs_at',
            [$userId]
        );
    }

    public static function manifest(int $slotId): array
    {
        return Database::select(
            'SELECT tb.*, o.reference AS order_reference
               FROM transport_bookings tb
          LEFT JOIN orders o ON o.id = tb.order_id
              WHERE tb.slot_id = ? AND tb.status <> "cancelled"
           ORDER BY tb.passenger_name',
            [$slotId]
        );
    }

    public static function summary(): array
    {
        $rows = Database::select(
            'SELECT r.name, r.id,
                    COALESCE(SUM(s.capacity), 0) AS capacity,
                    COALESCE(SUM(s.seats_taken), 0) AS taken
               FROM transport_routes r
          LEFT JOIN transport_slots s ON s.route_id = r.id AND s.is_active = 1
              WHERE r.is_active = 1
           GROUP BY r.id, r.name
           ORDER BY r.sort_order, r.id'
        );

        $capacity = 0;
        $taken    = 0;

        foreach ($rows as $row) {
            $capacity += (int) $row['capacity'];
            $taken    += (int) $row['taken'];
        }

        return ['routes' => $rows, 'capacity' => $capacity, 'taken' => $taken, 'available' => max(0, $capacity - $taken)];
    }
}

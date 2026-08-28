<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Database;

/**
 * Runtime settings live in the database so the committee can edit them without
 * touching .env. Secrets (PayFast keys, SMTP password) stay in .env only.
 */
final class SettingsService
{
    private static array $cache = [];
    private static bool $loaded = false;

    public static function preload(): void
    {
        if (self::$loaded) {
            return;
        }

        self::$loaded = true;

        try {
            foreach (Database::select('SELECT key_name, value FROM settings') as $row) {
                self::$cache[$row['key_name']] = $row['value'];
            }
        } catch (\Throwable) {
            // Before installation, or if the table is missing, fall back to config.
            self::$cache = [];
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::preload();

        $value = self::$cache[$key] ?? null;

        if ($value === null || $value === '') {
            return $default ?? self::configFallback($key);
        }

        return match ($value) {
            '1', 'true'  => in_array($key, self::booleanKeys(), true) ? true : $value,
            '0', 'false' => in_array($key, self::booleanKeys(), true) ? false : $value,
            default      => $value,
        };
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::get($key, $default ? '1' : '0');

        return in_array($value, [true, 1, '1', 'true', 'on', 'yes'], true);
    }

    public static function int(string $key, int $default = 0): int
    {
        $value = self::get($key, (string) $default);

        return is_numeric($value) ? (int) $value : $default;
    }

    public static function set(string $key, mixed $value): void
    {
        self::preload();

        if (is_bool($value)) {
            $value = $value ? '1' : '0';
        }

        $exists = Database::scalar('SELECT id FROM settings WHERE key_name = ?', [$key]);

        if ($exists === null) {
            Database::insert('settings', [
                'key_name'   => $key,
                'value'      => (string) $value,
                'label'      => ucwords(str_replace('_', ' ', $key)),
                'group_name' => 'general',
            ]);
        } else {
            Database::update('settings', ['value' => (string) $value], 'key_name = :key', ['key' => $key]);
        }

        self::$cache[$key] = (string) $value;
    }

    public static function group(string $group): array
    {
        return Database::select('SELECT * FROM settings WHERE group_name = ? ORDER BY sort_order, id', [$group]);
    }

    public static function groups(): array
    {
        $grouped = [];

        foreach (Database::select('SELECT * FROM settings ORDER BY group_name, sort_order, id') as $row) {
            $grouped[$row['group_name']][] = $row;
        }

        return $grouped;
    }

    public static function flush(): void
    {
        self::$cache  = [];
        self::$loaded = false;
    }

    private static function booleanKeys(): array
    {
        return [
            'whatsapp_enabled', 'whatsapp_hide_on_checkout', 'shop_enabled', 'accommodation_enabled',
            'transport_enabled', 'donations_enabled', 'registration_open', 'cookie_notice_enabled',
            'show_countdown', 'maintenance_mode', 'mock_data_banner',
        ];
    }

    /** Fall back to .env-backed config for the keys that mirror it. */
    private static function configFallback(string $key): mixed
    {
        return match ($key) {
            'site_name'                => Config::get('app.name'),
            'contact_email'            => Config::get('contact.email'),
            'whatsapp_number'          => Config::get('contact.whatsapp_number'),
            'ga_measurement_id'        => Config::get('analytics.ga_measurement_id'),
            'google_site_verification' => Config::get('analytics.google_site_verification'),
            'event_dates_label'        => Config::get('event.dates_label'),
            'event_venue_name'         => Config::get('event.venue_name'),
            'event_slogan'             => Config::get('event.slogan'),
            default                    => null,
        };
    }
}

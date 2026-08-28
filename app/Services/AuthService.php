<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Core\Session;

/**
 * Session-based authentication. Passwords use PHP's native hashing; no token
 * is ever handed to JavaScript or stored in localStorage.
 */
final class AuthService
{
    private static ?array $user = null;
    private static bool $resolved = false;

    /** Admin capabilities per role. */
    public const ROLE_PERMISSIONS = [
        'super_admin' => ['*'],
        'finance_admin' => ['dashboard', 'orders', 'payments', 'donations', 'coupons', 'exports', 'customers'],
        'accommodation_admin' => ['dashboard', 'rooms', 'bookings', 'exports'],
        'transport_admin' => ['dashboard', 'transport', 'exports', 'checkin'],
        'merch_admin' => ['dashboard', 'products', 'orders', 'exports'],
        'content_editor' => ['dashboard', 'content', 'gallery', 'pages', 'programme', 'faqs', 'events', 'banners'],
        'checkin_volunteer' => ['dashboard', 'checkin'],
    ];

    public const ROLE_LABELS = [
        'super_admin'         => 'Super Admin',
        'finance_admin'       => 'Finance Admin',
        'accommodation_admin' => 'Accommodation Admin',
        'transport_admin'     => 'Transport Admin',
        'merch_admin'         => 'Merch Admin',
        'content_editor'      => 'Content Editor',
        'checkin_volunteer'   => 'Check-in Volunteer',
    ];

    public static function attempt(string $email, string $password): ?array
    {
        $user = Database::first('SELECT * FROM users WHERE email = ? LIMIT 1', [strtolower(trim($email))]);

        if ($user === null || !password_verify($password, (string) $user['password_hash'])) {
            return null;
        }

        if ($user['status'] !== 'active') {
            return null;
        }

        if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
            Database::update('users', ['password_hash' => password_hash($password, PASSWORD_DEFAULT)], 'id = :id', ['id' => $user['id']]);
        }

        self::login($user);

        return $user;
    }

    public static function login(array $user): void
    {
        Session::regenerate();
        Session::put('user_id', (int) $user['id']);
        Session::put('logged_in_at', time());

        self::$user     = null;
        self::$resolved = false;

        Database::update('users', ['last_login_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $user['id']]);
    }

    public static function logout(): void
    {
        Session::destroy();
        self::$user     = null;
        self::$resolved = true;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        $user = self::user();

        return $user === null ? null : (int) $user['id'];
    }

    public static function user(): ?array
    {
        if (self::$resolved) {
            return self::$user;
        }

        self::$resolved = true;

        $id = Session::get('user_id');

        if ($id === null) {
            return self::$user = null;
        }

        try {
            $user = Database::first('SELECT * FROM users WHERE id = ? AND status = "active" LIMIT 1', [(int) $id]);
        } catch (\Throwable $e) {
            Logger::error('Failed to resolve session user: ' . $e->getMessage());

            return self::$user = null;
        }

        if ($user === null) {
            Session::forget('user_id');

            return self::$user = null;
        }

        $user['roles'] = array_column(
            Database::select('SELECT role FROM user_roles WHERE user_id = ?', [(int) $user['id']]),
            'role'
        );

        return self::$user = $user;
    }

    public static function isAdmin(): bool
    {
        $user = self::user();

        return $user !== null && (int) $user['is_admin'] === 1 && $user['roles'] !== [];
    }

    public static function roles(): array
    {
        return self::user()['roles'] ?? [];
    }

    public static function can(string $permission): bool
    {
        if (!self::isAdmin()) {
            return false;
        }

        foreach (self::roles() as $role) {
            $permissions = self::ROLE_PERMISSIONS[$role] ?? [];

            if (in_array('*', $permissions, true) || in_array($permission, $permissions, true)) {
                return true;
            }
        }

        return false;
    }

    public static function isVerified(): bool
    {
        $user = self::user();

        return $user !== null && $user['email_verified_at'] !== null;
    }

    public static function refresh(): void
    {
        self::$user     = null;
        self::$resolved = false;
    }

    public static function hash(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }
}

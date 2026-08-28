<?php
declare(strict_types=1);

namespace App\Core;

/** Per-session CSRF token. Every state-changing POST is checked in Kernel. */
final class Csrf
{
    public static function token(): string
    {
        if (!Session::has('_csrf_token')) {
            Session::put('_csrf_token', bin2hex(random_bytes(32)));
        }

        return (string) Session::get('_csrf_token');
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_token" value="' . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function check(?string $token): bool
    {
        if ($token === null || $token === '') {
            return false;
        }

        return hash_equals(self::token(), $token);
    }

    public static function rotate(): void
    {
        Session::put('_csrf_token', bin2hex(random_bytes(32)));
    }
}

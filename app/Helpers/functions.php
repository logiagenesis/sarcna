<?php
declare(strict_types=1);

use App\Core\Config;
use App\Core\Csrf;
use App\Core\Session;
use App\Services\AuthService;
use App\Services\SettingsService;

if (!function_exists('e')) {
    /** Escape for HTML output. Every dynamic value in a view goes through this. */
    function e(mixed $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('attr')) {
    function attr(mixed $value): string
    {
        return e($value);
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        return Config::get($key, $default);
    }
}

if (!function_exists('setting')) {
    /** Runtime setting from the database, editable in the admin. */
    function setting(string $key, mixed $default = null): mixed
    {
        return SettingsService::get($key, $default);
    }
}

if (!function_exists('url')) {
    function url(string $path = '/'): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return rtrim((string) Config::get('app.url'), '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        $relative = '/assets/' . ltrim($path, '/');
        $absolute = Config::get('paths.public') . $relative;
        $version  = is_file($absolute) ? (string) filemtime($absolute) : (string) Config::get('app.version', '1');

        return url($relative) . '?v=' . $version;
    }
}

if (!function_exists('uploaded')) {
    function uploaded(?string $path, string $fallback = 'img/backgrounds/placeholder.svg'): string
    {
        if ($path === null || trim($path) === '') {
            return asset($fallback);
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (str_starts_with($path, '/assets/')) {
            return asset(substr($path, 8));
        }

        return url('/uploads/' . ltrim($path, '/'));
    }
}

if (!function_exists('money')) {
    /** Format cents as South African Rand. All money is stored in cents. */
    function money(int|float|string|null $cents, bool $withSymbol = true): string
    {
        $amount = ((int) $cents) / 100;

        // A non-breaking space as the thousands separator so amounts never
        // wrap mid-number in a card or a table cell.
        $value = number_format($amount, 2, '.', "\u{00A0}");

        return $withSymbol ? 'R' . $value : $value;
    }
}

if (!function_exists('rands')) {
    /** Convert a rand amount typed by an admin into integer cents. */
    function rands(int|float|string|null $rands): int
    {
        $value = is_string($rands) ? str_replace([' ', ','], ['', '.'], $rands) : $rands;

        return (int) round(((float) $value) * 100);
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return Csrf::field();
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return Csrf::token();
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = ''): mixed
    {
        static $values = null;

        if ($values === null) {
            $values = Session::getFlash('old', []) ?: [];
        }

        return $values[$key] ?? $default;
    }
}

if (!function_exists('errors')) {
    function errors(): array
    {
        static $values = null;

        if ($values === null) {
            $values = Session::getFlash('errors', []) ?: [];
        }

        return $values;
    }
}

if (!function_exists('error_for')) {
    function error_for(string $field): ?string
    {
        $messages = errors()[$field] ?? null;

        return is_array($messages) ? (string) reset($messages) : $messages;
    }
}

if (!function_exists('auth')) {
    function auth(): ?array
    {
        return AuthService::user();
    }
}

if (!function_exists('auth_id')) {
    function auth_id(): ?int
    {
        return AuthService::id();
    }
}

if (!function_exists('is_admin')) {
    function is_admin(): bool
    {
        return AuthService::isAdmin();
    }
}

if (!function_exists('can')) {
    function can(string $permission): bool
    {
        return AuthService::can($permission);
    }
}

if (!function_exists('slugify')) {
    function slugify(string $value): string
    {
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $value = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $value) ?? '');

        return trim($value, '-') ?: 'item';
    }
}

if (!function_exists('excerpt')) {
    function excerpt(?string $text, int $length = 160): string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags((string) $text)) ?? '');

        if (mb_strlen($text) <= $length) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $length), " ,.;:-") . '…';
    }
}

if (!function_exists('za_date')) {
    function za_date(?string $datetime, string $format = 'j F Y'): string
    {
        if ($datetime === null || $datetime === '' || $datetime === '0000-00-00 00:00:00') {
            return '';
        }

        $timestamp = strtotime($datetime);

        return $timestamp === false ? '' : date($format, $timestamp);
    }
}

if (!function_exists('night_label')) {
    function night_label(string $date): string
    {
        return za_date($date, 'D j M');
    }
}

if (!function_exists('is_active')) {
    function is_active(string $path, bool $exact = false): bool
    {
        $current = rtrim((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH), '/') ?: '/';
        $path    = rtrim($path, '/') ?: '/';

        return $exact ? $current === $path : ($current === $path || str_starts_with($current . '/', $path . '/'));
    }
}

if (!function_exists('nav_class')) {
    function nav_class(string $path, string $activeClass = 'is-active'): string
    {
        return is_active($path) ? ' ' . $activeClass : '';
    }
}

if (!function_exists('picture')) {
    /**
     * Emit a <picture> that prefers WebP and falls back to the original file.
     * Every image on the site is served locally — nothing is hot-linked.
     */
    function picture(string $src, string $alt, array $options = []): string
    {
        $class   = $options['class'] ?? '';
        $loading = $options['loading'] ?? 'lazy';
        $sizes   = $options['sizes'] ?? null;
        $width   = $options['width'] ?? null;
        $height  = $options['height'] ?? null;

        $url  = str_starts_with($src, '/') || str_starts_with($src, 'http') ? uploaded($src) : asset($src);
        $webp = preg_replace('/\.(jpe?g|png)(\?.*)?$/i', '.webp$2', $url);

        $attributes = sprintf(
            'src="%s" alt="%s" loading="%s" decoding="async"%s%s%s',
            e($url),
            e($alt),
            e($loading),
            $class !== '' ? ' class="' . e($class) . '"' : '',
            $width !== null ? ' width="' . (int) $width . '"' : '',
            $height !== null ? ' height="' . (int) $height . '"' : ''
        );

        if ($webp === $url) {
            return '<img ' . $attributes . '>';
        }

        return sprintf(
            '<picture><source type="image/webp" srcset="%s"%s><img %s></picture>',
            e($webp),
            $sizes !== null ? ' sizes="' . e($sizes) . '"' : '',
            $attributes
        );
    }
}

if (!function_exists('whatsapp_link')) {
    function whatsapp_link(?string $message = null): string
    {
        $number  = preg_replace('/\D+/', '', (string) setting('whatsapp_number', config('contact.whatsapp_number', '')));
        $message ??= (string) setting('whatsapp_message', 'Hi SARCNA 2027, I need help with registration/accommodation/transport.');

        return 'https://wa.me/' . $number . '?text=' . rawurlencode($message);
    }
}

if (!function_exists('array_get')) {
    function array_get(array $array, string $key, mixed $default = null): mixed
    {
        return $array[$key] ?? $default;
    }
}

if (!function_exists('str_random')) {
    function str_random(int $length = 32): string
    {
        return substr(bin2hex(random_bytes((int) ceil($length / 2))), 0, $length);
    }
}

if (!function_exists('reference_code')) {
    /** Human-friendly reference, e.g. SAR-7QF3-2K9D. */
    function reference_code(string $prefix = 'SAR'): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $segments = [];

        for ($segment = 0; $segment < 2; $segment++) {
            $chunk = '';
            for ($i = 0; $i < 4; $i++) {
                $chunk .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $segments[] = $chunk;
        }

        return $prefix . '-' . implode('-', $segments);
    }
}

if (!function_exists('mock_badge')) {
    function mock_badge(bool $isMock, string $label = 'Mock data'): string
    {
        return $isMock ? '<span class="badge badge--mock" title="Placeholder content for the committee preview">' . e($label) . '</span>' : '';
    }
}

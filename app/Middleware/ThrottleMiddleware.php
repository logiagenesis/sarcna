<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\HttpException;
use App\Core\Request;
use App\Services\RateLimiter;

/** Usage: 'throttle:10,300' — 10 requests per 300 seconds per IP and path. */
final class ThrottleMiddleware
{
    public function handle(Request $request, ?string $parameter = null): void
    {
        if (!$request->isWriting()) {
            return;
        }

        [$max, $decay] = array_pad(explode(',', (string) ($parameter ?? '20,300')), 2, '300');

        $key = 'throttle:' . $request->path() . ':' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, (int) $max, (int) $decay)) {
            throw new HttpException(429, 'Too many attempts. Please wait ' . ceil(RateLimiter::secondsRemaining($key) / 60) . ' minute(s) and try again.');
        }

        RateLimiter::hit($key, (int) $decay);
    }
}

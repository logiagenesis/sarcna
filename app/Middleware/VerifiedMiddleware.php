<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\AuthService;
use App\Services\SettingsService;

/** Optional email-verification gate, switched on in Admin > Settings. */
final class VerifiedMiddleware
{
    public function handle(Request $request, ?string $parameter = null): void
    {
        if (!SettingsService::bool('require_email_verification', false)) {
            return;
        }

        if (AuthService::check() && !AuthService::isVerified()) {
            Session::flash('warning', 'Please confirm your email address before continuing.');
            Response::redirect(url('/account?verify=1'));
        }
    }
}

<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\AuthService;

/** Usage: 'admin' for any admin, 'admin:orders' to require a capability. */
final class AdminMiddleware
{
    public function handle(Request $request, ?string $permission = null): void
    {
        if (!AuthService::check()) {
            Session::put('intended_url', $request->path());
            Response::redirect(url('/login'));
        }

        if (!AuthService::isAdmin()) {
            throw new HttpException(403, 'This area is for convention committee administrators.');
        }

        if ($permission !== null && !AuthService::can($permission)) {
            throw new HttpException(403, 'Your admin role does not include access to this section.');
        }
    }
}

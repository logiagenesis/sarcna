<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;

final class GuestMiddleware
{
    public function handle(Request $request, ?string $parameter = null): void
    {
        if (AuthService::check()) {
            Response::redirect(url(AuthService::isAdmin() ? '/admin' : '/account'));
        }
    }
}

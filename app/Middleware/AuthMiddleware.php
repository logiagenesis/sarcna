<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\AuthService;

final class AuthMiddleware
{
    public function handle(Request $request, ?string $parameter = null): void
    {
        if (AuthService::check()) {
            return;
        }

        Session::put('intended_url', $request->path());
        Session::flash('error', 'Please sign in to continue.');

        Response::redirect(url('/login'));
    }
}

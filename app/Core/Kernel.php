<?php
declare(strict_types=1);

namespace App\Core;

use App\Services\SettingsService;

/**
 * Front controller: boots the session, applies security headers, resolves the
 * route, runs middleware, and renders errors without leaking server detail.
 */
final class Kernel
{
    public function handle(): void
    {
        $request = new Request();

        try {
            $this->guardInstallation($request);

            Session::start();
            $this->sendSecurityHeaders();

            if ($request->isWriting() && !$this->isCsrfExempt($request->path())) {
                if (!Csrf::check((string) $request->input('_token', ''))) {
                    throw new HttpException(419);
                }
            }

            /** @var Router $router */
            $router = require Config::get('paths.app') . '/Config/routes.php';

            $match = $router->match($request->method(), $request->path());

            if ($match === null) {
                $allowed = $router->methodsFor($request->path());
                throw new HttpException($allowed === [] ? 404 : 405);
            }

            View::share('router', $router);

            foreach ($match['middleware'] as $middleware) {
                $this->runMiddleware($middleware, $request);
            }

            echo $this->dispatch($match['handler'], $match['params']);
        } catch (HttpException $e) {
            $this->renderError($e->status(), $e->getMessage(), $request);
        } catch (\Throwable $e) {
            Logger::error($e->getMessage(), [
                'file'  => $e->getFile() . ':' . $e->getLine(),
                'path'  => $request->path(),
                'trace' => array_slice(explode("\n", $e->getTraceAsString()), 0, 8),
            ]);

            if (Config::get('app.debug')) {
                http_response_code(500);
                echo '<pre style="padding:2rem;font:14px/1.6 monospace;background:#111815;color:#fff6e7;white-space:pre-wrap">';
                echo e($e::class . ': ' . $e->getMessage()) . "\n\n";
                echo e($e->getFile() . ':' . $e->getLine()) . "\n\n";
                echo e($e->getTraceAsString());
                echo '</pre>';

                return;
            }

            $this->renderError(500, '', $request);
        }
    }

    private function dispatch(mixed $handler, array $params): string
    {
        if (is_callable($handler)) {
            return (string) $handler(...array_values($params));
        }

        [$class, $method] = $handler;

        if (!class_exists($class)) {
            throw new \RuntimeException('Controller not found: ' . $class);
        }

        $controller = new $class();

        if (!method_exists($controller, $method)) {
            throw new \RuntimeException('Action not found: ' . $class . '::' . $method);
        }

        return (string) $controller->{$method}(...array_values($params));
    }

    private function runMiddleware(string $middleware, Request $request): void
    {
        [$name, $parameter] = array_pad(explode(':', $middleware, 2), 2, null);

        $class = 'App\\Middleware\\' . ucfirst($name) . 'Middleware';

        if (!class_exists($class)) {
            throw new \RuntimeException('Middleware not found: ' . $middleware);
        }

        (new $class())->handle($request, $parameter);
    }

    /**
     * Until the installer has run, every request is sent to /install so a fresh
     * cPanel upload never shows a database error to the public.
     */
    private function guardInstallation(Request $request): void
    {
        $installed = is_file((string) Config::get('paths.lock'));
        $path      = $request->path();

        if ($installed && str_starts_with($path, '/install')) {
            http_response_code(410);
            exit('The installer has already been run and is locked. Delete app/Config/installed.lock to run it again.');
        }

        if (!$installed && !str_starts_with($path, '/install') && !str_starts_with($path, '/assets')) {
            Response::redirect('/install');
        }
    }

    private function isCsrfExempt(string $path): bool
    {
        // PayFast posts its IPN server-to-server; it is verified by signature
        // and by a callback to PayFast instead of by a session token.
        return in_array($path, ['/payment/notify'], true);
    }

    private function sendSecurityHeaders(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=()');
        header_remove('X-Powered-By');

        if (Session::isHttps()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }

    private function renderError(int $status, string $message, Request $request): void
    {
        http_response_code($status);

        if ($request->isAjax()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => $message !== '' ? $message : HttpException::defaultMessage($status)]);

            return;
        }

        try {
            if ($status >= 500 || !Database::isConnected()) {
                echo View::partial('errors.minimal', [
                    'status'  => $status,
                    'message' => $message !== '' ? $message : HttpException::defaultMessage($status),
                ]);

                return;
            }

            SettingsService::preload();

            echo View::render('errors.error', [
                'status'  => $status,
                'message' => $message !== '' ? $message : HttpException::defaultMessage($status),
            ]);
        } catch (\Throwable $e) {
            Logger::error('Error page failed to render: ' . $e->getMessage());
            echo '<h1>' . $status . '</h1><p>' . e(HttpException::defaultMessage($status)) . '</p>';
        }
    }
}

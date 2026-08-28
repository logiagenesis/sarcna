<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Small regex router. Routes are declared in app/Config/routes.php as
 *   $router->get('/accommodation/{slug}', [AccommodationController::class, 'show']);
 */
final class Router
{
    /** @var array<string, array<int, array{pattern:string, params:string[], handler:mixed, middleware:string[], name:?string}>> */
    private array $routes = [];

    /** @var array<string, string> */
    private array $names = [];

    private array $groupMiddleware = [];
    private string $groupPrefix    = '';
    private ?string $lastMethod    = null;
    private ?int $lastIndex        = null;

    public function get(string $uri, array|callable $handler, array $middleware = []): self
    {
        return $this->add('GET', $uri, $handler, $middleware);
    }

    public function post(string $uri, array|callable $handler, array $middleware = []): self
    {
        return $this->add('POST', $uri, $handler, $middleware);
    }

    public function any(array $methods, string $uri, array|callable $handler, array $middleware = []): self
    {
        foreach ($methods as $method) {
            $this->add($method, $uri, $handler, $middleware);
        }

        return $this;
    }

    public function group(string $prefix, array $middleware, callable $callback): void
    {
        $previousPrefix     = $this->groupPrefix;
        $previousMiddleware = $this->groupMiddleware;

        $this->groupPrefix     = $previousPrefix . $prefix;
        $this->groupMiddleware = array_merge($previousMiddleware, $middleware);

        $callback($this);

        $this->groupPrefix     = $previousPrefix;
        $this->groupMiddleware = $previousMiddleware;
    }

    public function name(string $name): self
    {
        if ($this->lastMethod === null || $this->lastIndex === null) {
            return $this;
        }

        $this->routes[$this->lastMethod][$this->lastIndex]['name'] = $name;
        $this->names[$name] = $this->routes[$this->lastMethod][$this->lastIndex]['uri'];

        return $this;
    }

    public function uriFor(string $name): ?string
    {
        return $this->names[$name] ?? null;
    }

    private function add(string $method, string $uri, array|callable $handler, array $middleware): self
    {
        $uri  = $this->groupPrefix . $uri;
        $uri  = $uri === '' ? '/' : $uri;
        $path = $uri === '/' ? '/' : rtrim($uri, '/');

        $params  = [];
        $pattern = preg_replace_callback(
            '#\{([a-zA-Z_][a-zA-Z0-9_]*)(?::([^}]+))?\}#',
            static function (array $matches) use (&$params): string {
                $params[] = $matches[1];

                return '(' . ($matches[2] ?? '[^/]+') . ')';
            },
            $path
        );

        $this->routes[$method][] = [
            'uri'        => $path,
            'pattern'    => '#^' . $pattern . '$#',
            'params'     => $params,
            'handler'    => $handler,
            'middleware' => array_merge($this->groupMiddleware, $middleware),
            'name'       => null,
        ];

        $this->lastMethod = $method;
        $this->lastIndex  = array_key_last($this->routes[$method]);

        return $this;
    }

    /** @return array{handler:mixed, params:array, middleware:string[]}|null */
    public function match(string $method, string $path): ?array
    {
        foreach ($this->routes[$method] ?? [] as $route) {
            if (preg_match($route['pattern'], $path, $matches) === 1) {
                array_shift($matches);

                $params = [];
                foreach ($route['params'] as $index => $name) {
                    $params[$name] = $matches[$index] ?? null;
                }

                return [
                    'handler'    => $route['handler'],
                    'params'     => $params,
                    'middleware' => $route['middleware'],
                ];
            }
        }

        return null;
    }

    /** Methods a path would match under, used to answer 405 correctly. */
    public function methodsFor(string $path): array
    {
        $methods = [];

        foreach ($this->routes as $method => $routes) {
            foreach ($routes as $route) {
                if (preg_match($route['pattern'], $path) === 1) {
                    $methods[] = $method;
                    break;
                }
            }
        }

        return $methods;
    }

    /** All GET routes without dynamic parameters — the sitemap builds from these. */
    public function staticGetRoutes(): array
    {
        $uris = [];

        foreach ($this->routes['GET'] ?? [] as $route) {
            if ($route['params'] === []) {
                $uris[] = $route['uri'];
            }
        }

        return $uris;
    }
}

<?php

namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $path, callable|array|string $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, callable|array|string $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function add(string $method, string $path, callable|array|string $handler, array $middleware = []): void
    {
        $pattern = preg_replace('#\{([a-zA-Z_]+)\}#', '(?P<$1>[^/]+)', $path);
        $pattern = '#^' . rtrim($pattern, '/') . '/?$#';
        $this->routes[] = compact('method', 'path', 'pattern', 'handler', 'middleware');
    }

    public function dispatch(string $method, string $uri): void
    {
        $uriPath = parse_url($uri, PHP_URL_PATH) ?: '/';
        $basePath = parse_url(config('app')['base_url'], PHP_URL_PATH) ?: '';

        if ($basePath && str_starts_with($uriPath, $basePath)) {
            $uriPath = substr($uriPath, strlen($basePath));
        }
        $uriPath = '/' . trim($uriPath, '/');
        if ($uriPath === '//') {
            $uriPath = '/';
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $uriPath, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                foreach ($route['middleware'] as $mw) {
                    $result = $this->runMiddleware($mw);
                    if ($result === false) {
                        return;
                    }
                }
                $this->runHandler($route['handler'], $params);
                return;
            }
        }

        http_response_code(404);
        View::render('errors/404', [], 'layouts/guest');
    }

    private function runMiddleware(string $middleware): bool
    {
        if ($middleware === 'auth') {
            if (!\App\Services\AuthService::check()) {
                header('Location: ' . url('/login'));
                return false;
            }
        }

        if (str_starts_with($middleware, 'permission:')) {
            $permission = explode(':', $middleware, 2)[1];
            if (!\App\Services\AuthService::can($permission)) {
                http_response_code(403);
                View::render('errors/403', ['permission' => $permission], 'layouts/app');
                return false;
            }
        }

        return true;
    }

    private function runHandler(callable|array|string $handler, array $params): void
    {
        if (is_callable($handler)) {
            call_user_func_array($handler, $params);
            return;
        }

        [$controller, $method] = $handler;
        $instance = new $controller();
        call_user_func_array([$instance, $method], $params);
    }
}

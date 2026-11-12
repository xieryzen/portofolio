<?php

declare(strict_types=1);

namespace App;

/**
 * Router sederhana dengan pola {param}.
 */
final class Router
{
    /** @var list<array{method: string, pattern: string, regex: string, params: list<string>, handler: callable|array}> */
    private array $routes = [];

    /**
     * @param callable|array{0: class-string, 1: string} $handler
     */
    public function add(string $method, string $pattern, callable|array $handler): void
    {
        [$regex, $params] = self::compile($pattern);

        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'regex' => $regex,
            'params' => $params,
            'handler' => $handler,
        ];
    }

    /**
     * @param callable|array{0: class-string, 1: string} $handler
     */
    public function get(string $pattern, callable|array $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    /**
     * @param callable|array{0: class-string, 1: string} $handler
     */
    public function post(string $pattern, callable|array $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    public function dispatch(string $method, string $uri): void
    {
        $method = strtoupper($method);
        $path = self::normalizePath($uri);

        $methodMismatch = false;

        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }

            if ($route['method'] !== $method && !($method === 'HEAD' && $route['method'] === 'GET')) {
                $methodMismatch = true;
                continue;
            }

            $params = [];
            foreach ($route['params'] as $index => $name) {
                $params[$name] = $matches[$index + 1] ?? null;
            }

            $this->invoke($route['handler'], $params);

            return;
        }

        if ($methodMismatch) {
            http_response_code(405);
            header('Allow: GET, POST');
            View::render('errors/405', ['title' => 'Metode tidak diizinkan'], 'layouts/app');

            return;
        }

        http_response_code(404);
        View::render('errors/404', ['title' => 'Halaman tidak ditemukan'], 'layouts/app');
    }

    /**
     * @param callable|array{0: class-string, 1: string} $handler
     * @param array<string, string|null> $params
     */
    private function invoke(callable|array $handler, array $params): void
    {
        if (is_callable($handler) && !is_array($handler)) {
            $handler(...array_values($params));

            return;
        }

        [$class, $method] = $handler;
        $controller = new $class();
        $controller->{$method}(...array_values($params));
    }

    /**
     * @return array{0: string, 1: list<string>}
     */
    private static function compile(string $pattern): array
    {
        $params = [];
        $regex = preg_replace_callback(
            '~\{([a-zA-Z_][a-zA-Z0-9_]*)(?::([^}]+))?\}~',
            static function (array $matches) use (&$params): string {
                $params[] = $matches[1];
                $custom = $matches[2] ?? '';
                $inner = $custom !== '' ? $custom : ($matches[1] === 'id' ? '[0-9]+' : '[^/]+');

                return '(' . $inner . ')';
            },
            $pattern
        ) ?? $pattern;

        return ['~^' . $regex . '$~', $params];
    }

    private static function normalizePath(string $uri): string
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = rawurldecode($path);
        $base = app_base_url();

        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }

        $path = '/' . ltrim($path, '/');
        $path = rtrim($path, '/');

        return $path === '' ? '/' : $path;
    }
}
